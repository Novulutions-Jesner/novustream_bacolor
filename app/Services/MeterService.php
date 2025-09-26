<?php

namespace App\Services;

use App\Models\Zones;
use App\Models\BaseRate;
use App\Models\User;
use App\Models\Bill;
use App\Models\BillBreakdown;
use App\Models\BillDiscount;
use App\Models\Rates;
use App\Models\Reading;
use App\Models\UserAccounts;
use App\Models\SeniorDiscount;
use App\Models\Ruling;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PDO;

class MeterService {

    public $paymentBreakdownService;
    public $paymentServiceFee;

    public function __construct(PaymentBreakdownService $paymentBreakdownService) {
        $this->paymentBreakdownService = $paymentBreakdownService;
    }

    public function getReadUnread(string $monthYear)
    {
        $date = Carbon::parse($monthYear);

        $accounts = UserAccounts::with('user')->get();

        $readings = Reading::with('concessionaire.user')
            ->whereYear('created_at', $date->year)
            ->whereMonth('created_at', $date->month)
            ->get();

        $readData = $readings->map(function ($reading) {
            return [
                'account_no' => $reading->account_no,
                'name'       => $reading->concessionaire->user->name ?? 'N/A',
                'address'    => $reading->concessionaire->address ?? 'N/A',
                'meter_no'   => $reading->concessionaire->meter_serial_no ?? 'N/A',
            ];
        })->toArray();

        $readAccountNos = array_column($readData, 'account_no');

        $unreadData = $accounts->filter(function ($account) use ($readAccountNos) {
            return !in_array($account->account_no, $readAccountNos);
        })->map(function ($account) {
            return [
                'account_no' => $account->account_no,
                'name'       => $account->user->name ?? 'N/A',
                'address'    => $account->address ?? 'N/A',
                'meter_no'   => $account->meter_serial_no ?? 'N/A',
            ];
        })->values()->toArray();

        return [
            'read' => array_values($readData),
            'unread' => $unreadData,
        ];
    }

    public function getZones() {
        return Zones::select('zone', 'area')->get();
    }


    public function filterAccount(array $filter) {
        $query = UserAccounts::with('user');

        if (!empty($filter['zones']) && is_array($filter['zones'])) {
            $query->where(function ($q) use ($filter) {
                foreach ($filter['zones'] as $zone) {
                    $q->orWhere('account_no', 'like', $zone . '%');
                }
            });
        }
        elseif (!empty($filter['zone']) && strtolower($filter['zone']) !== 'all') {
            $query->where('account_no', 'like', $filter['zone'] . '%');
        }

        if (!empty($filter['search_by'])) {
            switch ($filter['search_by']) {
                case 'all':
                    if (!empty($filter['search'])) {
                        $query->where(function ($q) use ($filter) {
                            $q->where('account_no', 'like', '%' . $filter['search'] . '%')
                            ->orWhere('meter_serial_no', 'like', '%' . $filter['search'] . '%')
                            ->orWhereHas('user', function ($uq) use ($filter) {
                                $uq->where('name', 'like', '%' . $filter['search'] . '%');
                            });
                        });
                    }
                    break;

                case 'account_no':
                    if (!empty($filter['search'])) {
                        $query->where('account_no', 'like', '%' . $filter['search'] . '%');
                    }
                    break;

                case 'meter_serial_no':
                    if (!empty($filter['search'])) {
                        $query->where('meter_serial_no', 'like', '%' . $filter['search'] . '%');
                    }
                    break;

                case 'name':
                    if (!empty($filter['search'])) {
                        $query->whereHas('user', function ($q) use ($filter) {
                            $q->where('name', 'like', '%' . $filter['search'] . '%');
                        });
                    }
                    break;

                case 'read':
                    $query->whereHas('readings');
                    break;

                case 'unread':
                    $query->whereDoesntHave('readings');
                    break;
            }
        }

        $total = $query->count();

        $limit = (isset($filter['filter']) && is_numeric($filter['filter']))
            ? (int) $filter['filter']
            : 50;

        $data = $query->limit($limit)->get();

        return [
            'total' => $total,
            'data' => $data
        ];
    }


    public function getPreviousReading($account_no) {

        $previous_reading = Reading::with('sc_discount', 'bill')
            ->where('account_no', $account_no)
            ->latest()
            ->first();

        if ($previous_reading) {

            $suggestNextMonth = optional($previous_reading->bill)->bill_period_to;

            if ($suggestNextMonth) {
                $suggestNextMonth = Carbon::parse($suggestNextMonth)
                    ->addMonth(1)
                    ->format('Y-m-d');
            } else {
                $suggestNextMonth = null;
            }

            $expired_date = null;

            $sc_discount_start = $previous_reading->sc_discount->effective_date ?? null;
            $sc_discount_end = $previous_reading->sc_discount->expired_date ?? null;

            if ($sc_discount_start && $sc_discount_end) {
                $billDate = Carbon::parse($suggestNextMonth);
                $scStartDate = Carbon::parse($sc_discount_start);
                $scEndDate = Carbon::parse($sc_discount_end);

                if($billDate->between($scStartDate, $scEndDate) && $billDate->diffInMonths($scEndDate, false) <= 1) {
                    $expired_date = Carbon::parse($scEndDate)->format('F d, Y');
                }
            }

            return [
                'previous_reading' => $previous_reading->present_reading ?? null,
                'suggestedNextMonth' => $suggestNextMonth,
                'sc_expired_date' => $expired_date
            ];
        }

        return [
            'previous_reading' => null,
            'suggestedNextMonth' => Carbon::now()->format('Y-m-d'),
            'sc_expired_date' => null
        ];
    }

    public function getReRead(string $reference_no) {

        $data = $this->getBill($reference_no);

        $client = $data['client'];
        $reading = $data['current_bill']['reading'];

        $expired_date = null;

        $sc_discount_start = $data['current_bill']['reading']['sc_discount']['effective_date'] ?? null;
        $sc_discount_end = $data['current_bill']['reading']['sc_discount']['expired_date'] ?? null;


        if ($sc_discount_start && $sc_discount_end) {
            $billDate = Carbon::parse($reading['created_at']);
            $scStartDate = Carbon::parse($sc_discount_start);
            $scEndDate = Carbon::parse($sc_discount_end);

            if($billDate->between($scStartDate, $scEndDate) && $billDate->diffInMonths($scEndDate, false) <= 1) {
                $expired_date = Carbon::parse($scEndDate)->format('F d, Y');
            }
        }

        $suggestedNextMonth = Carbon::parse($reading['created_at'])
            ->timezone('Asia/Manila')
            ->format('Y-m-d');

        $data = [
            'account_no' => $client['account_no'],
            'address' => $client['address'],
            'name' => $client['name'],
            'isHighConsumption' => $data['current_bill']['isHighConsumption'],
            'suggestedNextMonth' => $suggestedNextMonth,
            'sc_expired_date' => $expired_date
        ];

        $data = array_merge($data, $reading);

        return $data;
    }

    public function getAccount($meter_no) {
        return UserAccounts::with('user')->where('account_no', $meter_no ?? '')
        ->orWhere('meter_serial_no', $meter_no ?? '')
        ->first();
    }


    public static function getReport(string $zone = null, string $date = null, string $search = null)
    {
        $isAll = $zone === 'all';

        if (empty($zone) && empty($date) && empty($search)) {
            return Reading::with(['concessionaire.user', 'bill'])
                ->where('isReRead', false)
                ->get();
        }

        $readings = Reading::with(['concessionaire.user', 'bill'])
            ->where('isReRead', false)
            ->when(!empty($zone) && !$isAll, fn($q) =>
                $q->where('zone', 'like', "%$zone%")
            )
            ->when(!empty($date), function ($q) use ($date) {
                if (preg_match('/^\d{4}-\d{2}$/', $date)) {
                    [$year, $month] = explode('-', $date);
                    $q->whereYear('created_at', $year)
                    ->whereMonth('created_at', $month);
                }
            })
            ->when(!empty($search), function ($q) use ($search) {
                $q->where(function ($q) use ($search) {
                    $q->where('account_no', 'like', "%$search%")
                    ->orWhereHas('concessionaire.user', fn($cq) =>
                        $cq->where('name', 'like', "%$search%")
                    );
                });
            })
            ->get();

        if ($isAll) {
            return $readings->values();
        }

        $grouped = $readings->groupBy(fn($r) => $r->zone ?? 'Unknown')
                            ->map(fn($zoneGroup) => $zoneGroup->values());

        return $grouped->values()->all();
    }



    public static function getData(?int $id = null) {

        if(!is_null($id)) {
            return Rates::with('property_types')->where('id', $id)
                ->first() ?? null;
        }

        return Rates::with('property_types')->get();

    }

    public static function getPayments(string $filter, string $zone = null, string $date = null, string $search = null)
    {
        $isPaid = $filter === 'paid';

        $bills = Bill::with('reading')
            ->whereHas('reading', function ($query) use ($zone, $date, $search) {
                $query->where('isReRead', false);

                if (!empty($zone) && $zone !== 'all') {
                    $query->where('zone', 'like', "%$zone%");
                }

                if (!empty($date)) {
                    [$year, $month] = explode('-', $date);
                    $query->whereYear('created_at', $year)
                        ->whereMonth('created_at', $month);
                }

                if (!empty($search)) {
                    $query->where('account_no', 'like', "%$search%");
                }
            })
            ->where('isPaid', $isPaid)
            ->get();

        if ($zone === 'all') {
            if (!empty($date)) {
                return $bills->groupBy(fn($bill) => $bill->created_at->toDateString())
                            ->map(fn($group) => $group->values())
                            ->values()
                            ->all();
            }

            return $bills->values();
        }

        $grouped = $bills->groupBy(fn($bill) => $bill->reading->zone ?? 'Unknown')
            ->map(function ($groupedByZone) use ($date) {
                if (!empty($date)) {
                    return array_values(
                        $groupedByZone
                            ->groupBy(fn($bill) => $bill->created_at->toDateString())
                            ->map(fn($groupedByDate) => $groupedByDate->values())
                            ->values()
                            ->all()
                    );
                }

                return $groupedByZone->values();
            })->values()->all();

        return $grouped[0] ?? [];
    }



    public function locate(array $payload) {
        $account = $this->getAccount($payload['meter_no']);

        if (!$account) {
            return [
                'status' => 'error',
                'message' => 'No client found'
            ];
        }

        return [
            'status' => 'success',
            'account' => $account,
            'reading' => $this->getPreviousReading($account->account_no)
        ];

    }

    public static function getBill(string $reference_no) {
        $current_bill = Bill::with('reading.sc_discount', 'breakdown', 'discount')
            ->where('reference_no', $reference_no)
            ->first();

        if (!$current_bill) {
            return [
                'status' => 'error',
                'message' => 'No bill found'
            ];
        }

        // Get meter number from current bill
        $account_no = optional($current_bill->reading)->account_no;
        $client = User::with(['accounts.sc_discount', 'accounts.property_types'])
                ->whereHas('accounts', function ($query) use ($account_no) {
                    $query->where('account_no', $account_no);
                })
                ->first();
        $previous_payment = DB::table('bill')
            ->leftJoin('readings', 'bill.reading_id', 'readings.id')
            ->where('readings.account_no', $account_no)
            ->where('bill.isPaid', true)
            ->select('bill.*')
            ->orderBy('bill.created_at', 'desc')
            ->first();
        // Prepare base query for unpaid bills
        $unpaidQuery = Bill::with('reading')
            ->where('isPaid', false)
            ->whereHas('reading', function ($query) use ($account_no) {
                $query->where('account_no', $account_no);
            });
        // Fetch the latest unpaid payment (active payment)
        $active_payment = (clone $unpaidQuery)
            ->latest()
            ->select('reference_no')
            ->first();
        // Fetch other unpaid bills excluding the current reference number
        $unpaid_bills = (clone $unpaidQuery)
            ->where('reference_no', '!=', $reference_no)
            ->get();
        // Ensure active_payment is null if it matches the current reference_no
        if ($active_payment && $active_payment->reference_no == $reference_no) {
            $active_payment = null;
        }
        if (is_null($client)) {
            return [
                'status' => 'error',
                'message' => 'No Concessionaire found for this transaction'
            ];
        }

        $filteredAccounts = collect($client->accounts)
            ->where('account_no', $account_no)
            ->values();

        $filteredAccountArray = optional($filteredAccounts->first())->toArray() ?? [];
        $client = array_merge($filteredAccountArray, $client->toArray());
        $bill_period_from = $current_bill->bill_period_from;
        $previousConsumption = self::previousConsumption($account_no, $bill_period_from);
        unset($client['accounts']);

        return [
            'client' => $client,
            'current_bill' => $current_bill->toArray() ?? [],
            'previous_payment' => $previous_payment,
            'active_payment' => $active_payment ? $active_payment->toArray() : null,
            'unpaid_bills' => $unpaid_bills->toArray() ?? [],
            'previousConsumption' => $previousConsumption
        ];
    }

    public static function getBills(?string $number = null, bool $isAll = false, bool $isPaid = false) {

        $query = Bill::with(['reading', 'breakdown'])
            ->where('isPaid', $isPaid);

        if ($number) {

            $account = UserAccounts::where('account_no', $number)
                ->first();

            if ($account) {
                $query->whereHas('reading', function ($q) use ($account) {
                    $q->where('account_no', $account->account_no);
                });
            }
        }
        return $isAll ? $query->get()->toArray() : optional($query->first())->toArray();
    }

    public static function create(array $payload) {
        DB::beginTransaction();
        try {
            Rates::create([
                'property_types_id' => $payload['property_type'],
                'cubic_from' => $payload['cubic_from'],
                'cubic_to' => $payload['cubic_to'],
                'rates' => $payload['rate']
            ]);
            DB::commit();
            return [
                'status' => 'success',
                'message' => 'Rate added.'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'status' => 'error',
                'message' => 'Error occured: ' . $e->getMessage()
            ];
        }
    }

    public static function update(int $id, array $payload) {
        DB::beginTransaction();
        try {
            $updateData = [
                'property_types_id' => $payload['property_types_id'],
                'cubic_from' => $payload['cubic_from'],
                'cubic_to' => $payload['cubic_to'],
                'rates' => $payload['rate']
            ];
            Rates::where('id', $id)->update($updateData);
            DB::commit();
            return [
                'status' => 'success',
                'message' => 'Rate  updated.'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'status' => 'error',
                'message' => 'Error occured: ' . $e->getMessage()
            ];
        }
    }

    public static function delete(int $id) {
        DB::beginTransaction();
        try {
            $data = Rates::where('id', $id)->first();
            $data->delete();
            DB::commit();
            return [
                'status' => 'success',
                'message' => 'Rate deleted.'
            ];
        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'status' => 'error',
                'message' => 'Error occured: ' . $e->getMessage()
            ];
        }
    }

    public function create_breakdown(array $payload) {
        $ruling = Ruling::first();
        $concessionaire = UserAccounts::with('user')->where('account_no', $payload['account_no'])->first();
        if(is_null($ruling)) {
            return [
                'status' => 'error',
                'message' => "We've noticed that there's no ruling set. Please add first."
            ];
        }
        if(is_null($concessionaire)) {
             return [
                'status' => 'error',
                'message' => "We've noticed that there's no concessionaire with this account no."
            ];
        }
        $reference_no = $payload['reference_no'];
        $reread_bill = Bill::with('reading')->where('reference_no', $reference_no)->first();
        if (!empty($payload['isReRead'])) {
            if ($reread_bill && $reread_bill->reading) {
                $reread_bill->reading->isReRead = true;
                $reread_bill->reading->save();
            }
        }
        $latest_reading = Reading::with('concessionaire.user', 'bill')
            ->where('isReRead', false)
            ->where('account_no', $payload['account_no'])
            ->latest()
            ->first();
        $previous_reading = optional($latest_reading)->present_reading ?? 0;
        $isChangeSaved = optional($latest_reading)->bill->isChangeForAdvancePayment ?? false;
        $advances = 0;
        if($isChangeSaved) {
            $advances = (float) $latest_reading->bill->change ?? 0;
        }
        $consumption = (float) $payload['present_reading'] - (float) $previous_reading;
        $base_rate = null;
        if(config('app.product') === 'novustream') {
            # novustream
            $rate = Rates::where('property_types_id', $payload['property_types_id'])
            ->where('cu_m', '<=', $consumption)
            ->orderByDesc('cu_m')
            ->value('amount');
        } else {
            # novusurge
            $base_rate = BaseRate::where('property_types_id', $payload['property_types_id'])
                ->value('rate') ?? 0;
            $rate = $base_rate  *  $consumption;
        }
        if ($rate == 0 || $base_rate && $base_rate == 0) {
            return [
                'status' => 'error',
                'message' => "We've noticed that there's no rate for this consumption"
            ];
        }
        $unpaidAmount = Bill::with('reading')
            ->where('isPaid', false)
            ->whereNotNull('amount')
            ->whereHas('reading', function ($query) use ($payload) {
                $query->where('account_no', $payload['account_no'])
                    ->where('isReRead', false);
            })->whereNotNull('amount')
            ->sum('amount') ?? 0;
        $total_amount = $unpaidAmount + $rate;
        $other_deductions = $this->paymentBreakdownService::getData();
        $discounts = $this->paymentBreakdownService::getDiscounts();
        $deductions = [
            [
                'name' => 'Previous Balance',
                'amount' => $unpaidAmount,
                'description' => ''
            ],
            [
                'name' => 'Basic Charge',
                'amount' => $rate,
                'description' => '',
            ],
        ];
        // deductions
        foreach ($other_deductions as $deduction) {
            if ($deduction->type == 'percentage') {
                $base_amount = ($deduction->percentage_of == 'basic_charge') ? $rate : $total_amount;
                $amount = $base_amount * ($deduction->amount);

                $deductions[] = [
                    'name' => $deduction->name,
                    'description' => $deduction->amount . '%',
                    'amount' => $amount
                ];
            } else {
                $deductions[] = [
                    'name' => $deduction->name,
                    'description' => '',
                    'amount' => $deduction->amount
                ];
            }
        }
        $sc_discount = SeniorDiscount::where('account_no', $payload['account_no'])
            ->first();
        $isSeniorCitizen = !is_null($sc_discount) ? true : false;
        $sc_discount_start = $sc_discount->effective_date ?? null;
        $sc_discount_end = $sc_discount->expired_date ?? null;

        $total = collect($deductions)->sum('amount');
        $basic_charge = collect($deductions)
            ->where('name', 'Basic Charge')
            ->sum('amount');

        $appliedDiscounts = [];
        $discountAmount = 0;

        // start with total (before discounts)
        $overall_total = $total;

        foreach ($discounts as $discount) {
            $isEligible = false;

            if ($discount->eligible === 'senior') {
                if ($isSeniorCitizen && $sc_discount_start && $sc_discount_end) {
                    $billDate = Carbon::parse($payload['date']);
                    $scStartDate = Carbon::parse($sc_discount_start);
                    $scEndDate = Carbon::parse($sc_discount_end);

                    $isEligible = $billDate->between($scStartDate, $scEndDate);
                }
            }

            if (!$isEligible || $consumption > $ruling->snr_dc_rule) {
                continue;
            }

            if (strtolower($discount->type) === 'percentage') {
                $base_amount = ($discount->percentage_of === 'basic_charge')
                    ? $basic_charge
                    : $total_amount;

                $discountAmount = round($base_amount * floatval($discount->amount), 2);
            } else {
                $discountAmount = $discount->amount;
            }

            //Prevent duplicate Senior Discount
            if ($discount->eligible !== 'senior' && $discountAmount > 0) {
                $appliedDiscounts[] = [
                    'name' => $discount->name,
                    'amount' => $discountAmount,
                    'description' => '',
                ];
            }
        }

        // subtract advances last
        $overall_total -= $advances;
        $arrears = collect($deductions)
            ->firstWhere('name', 'Previous Balance')['amount'] ?? 0;

        // penalty
        $penaltyAmount = 0;
        $amount_after_due = 0;
        $hasPenalty = false;

        if($unpaidAmount != 0) {

            $penalties = $this->paymentBreakdownService::getPenalty();
            $amountPayable = $total - $arrears - $discountAmount;

            foreach ($penalties as $penalty) {

                if (strtolower($penalty->amount_type) === 'percentage') {
                    $penaltyAmount = $amountPayable * ($penalty->amount);
                } else {
                    $penaltyAmount = $penalty->amount;
                }

                $amount_after_due = $overall_total + $penaltyAmount;
                $hasPenalty = true;
            }
        }

        $date = Carbon::parse($payload['date']);
        $days_due = $ruling->due_date;

        if($latest_reading) {
            $lastReading = Carbon::parse($latest_reading->bill->bill_period_to);
            $nextReading = $lastReading->addDays(1);
            $bill_period_from = $nextReading->format('Y-m-d H:i:s');
            $bill_period_to = Carbon::parse($payload['date'])->format('Y-m-d H:i:s');
        } else {
            $bill_period_from = $date->copy()->subDays($days_due)->format('Y-m-d H:i:s');
            $bill_period_to = $date->copy()->format('Y-m-d H:i:s');
        }

        $due_date = Carbon::parse($payload['date'])->addDays($days_due)->format('Y-m-d H:i:s');

        $isHighConsumption = $payload['is_high_consumption'] == 'yes' ? true : false;

        $reading = [
            'zone' => explode('-', $payload['account_no'])[0] ?? null,
            'account_no' => $payload['account_no'],
            'previous_reading' => $previous_reading,
            'present_reading' => $payload['present_reading'],
            'consumption' => $consumption,
            'reader_name' => Auth::user()->name,
            'created_at' => $bill_period_to,
            'updated_at' => $bill_period_to,
        ];

        $generatedReferenceNo = $this->generateReferenceNo();

        $bill = [
            'reference_no' => $generatedReferenceNo,
            'bill_period_from' => $bill_period_from,
            'bill_period_to' => $bill_period_to,
            'previous_unpaid' => $unpaidAmount,
            'total' => $total,
            'discount' => $discountAmount,
            'penalty' => $penaltyAmount,
            'hasPenalty' => $hasPenalty,
            'advances' => $advances,
            'isChangeForAdvancePayment' => $isChangeSaved,
            'amount' => $overall_total,
            'amount_after_due' => $amount_after_due,
            'due_date' => $due_date,
            'isHighConsumption' => $isHighConsumption,
            'created_at' => $bill_period_to,
            'updated_at' => $bill_period_to,
        ];

        try {

            $readingID = Reading::insertGetId($reading);

            $bill['reading_id'] = $readingID;

            $billID = Bill::insertGetId($bill);

            foreach($deductions as $deduction) {
                BillBreakdown::insert([
                    'bill_id' => $billID,
                    'name' => $deduction['name'],
                    'description' => $deduction['description'],
                    'amount' => $deduction['amount'],
                    'created_at' => $bill_period_to,
                    'updated_at' => $bill_period_to,
                ]);
            }

            foreach($appliedDiscounts as $discount) {
                BillDiscount::insert([
                    'bill_id' => $billID,
                    'name' => $discount['name'],
                    'description' => $discount['description'],
                    'amount' => $discount['amount'],
                    'created_at' => $bill_period_to,
                    'updated_at' => $bill_period_to,
                ]);
            }

            if (!empty($payload['isReRead'])) {
                $reference_no = $payload['reference_no'];
                if ($reread_bill && $reread_bill->reading) {
                    $reread_bill->reading->reread_reference_no = $generatedReferenceNo;
                    $reread_bill->reading->save();
                }
            }

        } catch (\Exception $e) {
            return [
                'status' => 'error',
                'message' => $e->getMessage
            ];
        }

        return [
            'status' => 'success',
            'bill' => $bill,
        ];
    }

    private static function previousConsumption(string $account_no, string $bill_period_from) {

        $billDate = Carbon::parse($bill_period_from);

        $targetMonths = collect();
        for ($i = 1; $i <= 6; $i++) {
            $date = $billDate->copy()->subMonths($i);
            $targetMonths->push([
                'month' => $date->format('M'),
                'month_number' => $date->month,
                'year' => $date->year,
                'value' => 0
            ]);
        }

        $start = $billDate->copy()->subMonths(6)->startOfMonth();
        $end = $billDate->copy()->subMonth()->endOfMonth();

        $readings = Reading::select(
                DB::raw('MONTH(created_at) as month_number'),
                DB::raw('YEAR(created_at) as year_number'),
                'consumption'
            )
            ->where('account_no', $account_no)
            ->whereBetween('created_at', [$start, $end])
            ->get()
            ->unique(fn($item) => $item->year_number . '-' . $item->month_number);

        $result = $targetMonths->map(function ($month) use ($readings) {
            $reading = $readings->first(function ($r) use ($month) {
                return $r->month_number == $month['month_number'] &&
                    $r->year_number == $month['year'];
            });

            return [
                'month' => $month['month'],
                'year' => $month['year'],
                'value' => $reading ? (int) $reading->consumption : 0
            ];
        });

        return $result->toArray();
    }

    private function generateReferenceNo() {

        $prefix = env('REF_PREFIX');

        do {
            $time = time();
            $combined = $prefix . '-' . $time;
            $exists = Bill::where('reference_no', $combined)
                ->exists();

            if ($exists) {
                sleep(1);
            }

        } while ($exists);

        return $combined;
    }

    public function getLatestReadingMonth()
    {
        return Reading::where('isReRead', false)
            ->latest('created_at')
            ->value('created_at')?->format('Y-m');
    }


}
