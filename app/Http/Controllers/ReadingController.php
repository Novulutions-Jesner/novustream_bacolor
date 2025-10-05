<?php

namespace App\Http\Controllers;

use App\Services\PaymentBreakdownService;
use App\Models\PaymentServiceFee;
use App\Models\User;
use App\Models\Bill;
use App\Models\BillBreakdown;
use App\Models\Rates;
use App\Models\Reading;
use App\Services\GenerateService;
use App\Services\MeterService;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\Log;
use App\Models\Zone; // make sure this is at the top
use App\Models\PaymentDiscount;
use App\Models\BillDiscount;
use App\Models\Discount;
use App\Models\DiscountType;


class ReadingController extends Controller
{

    public $meterService;
    public $paymentBreakdownService;
    public $paymentServiceFee;
    public $generateService;
    public $isTesting = false;

    public function __construct(MeterService $meterService,
        PaymentBreakdownService $paymentBreakdownService,
        PaymentServiceFee $paymentServiceFee,
        GenerateService $generateService)
    {

        $this->middleware(function ($request, $next) {
            $method = $request->route()->getActionMethod();

            if (!in_array($method, ['show'])) {
                if (!Gate::any(['admin', 'technician'])) {
                    abort(403, 'Unauthorized');
                }
            }

            return $next($request);
        });

        $this->meterService = $meterService;
        $this->paymentBreakdownService = $paymentBreakdownService;
        $this->paymentServiceFee = $paymentServiceFee;
        $this->generateService = $generateService;

        $this->isTesting = env('IS_TEST_READING');
    }

    public function index(Request $request) {
    if ($request->ajax()) {
        $payload = $request->all();

        $user = auth()->user();
        if ($user->user_type === 'technician') {
            $assignedZones = explode(',', $user->zone_assigned);
            $payload['zones'] = $assignedZones;

            if (!empty($payload['zone']) && strtolower($payload['zone']) !== 'all') {
                if (in_array($payload['zone'], $assignedZones)) {
                    $payload['zones'] = [$payload['zone']];
                } else {
                    $payload['zones'] = [];
                }
            }
        }

        if (isset($payload['isGetPrevious']) && $payload['isGetPrevious'] == true) {
            try {
                $response = $this->meterService->getPreviousReading($payload['account_no']);
                return response()->json($response);
            } catch (\Exception $e) {
                Log::error('getPreviousReading failed: ' . $e->getMessage());
                return response()->json([
                    'status' => 'error',
                    'message' => 'Unable to get previous reading.'
                ], 500);
            }
        }


        if(isset($payload['isReRead']) && $payload['isReRead'] == 'true') {
            $response = $this->meterService->getReRead($payload['reference_no']);
            return response()->json($response);
        }

        if(isset($payload['isGetRecentReading']) && $payload['isGetRecentReading'] == true) {
            $response = session('recent_reading') ?? null;
            return response()->json($response);
        }

        if(isset($payload['isGetReadUnread']) && $payload['isGetReadUnread'] == true) {
            $response = $this->meterService->getReadUnread($payload['targetDate']);
            return response()->json($response);
        }

        $response = $this->meterService->filterAccount($payload);
        return response()->json($response);
        }

        $isReRead = !empty($request->input('re-read')) && !empty($request->input('reference_no')) ? true : false;
        $reference_no = $request->input('reference_no') ?? null;

        if ($isReRead) {
            $bill = $this->meterService->getBill($reference_no);
            if (isset($bill['status']) && $bill['status'] == 'error') {
                return redirect()->route('reading.index');
            }
        }

        $user = auth()->user();

        if ($user->user_type === 'technician') {
            if (empty($user->zone_assigned)) {
                // Treat as admin if no zones assigned
                $zones = Zone::all();
                $showAllOption = true;
            } else {
                $assignedZones = explode(',', $user->zone_assigned);
                $zones = Zone::whereIn('zone', $assignedZones)->get();
                $showAllOption = false;
            }
        } else {
            $zones = Zone::all();
            $showAllOption = true;
        }


        return view('reading.index', [
            'isReRead' => $isReRead,
            'reference_no' => $reference_no,
            'zones' => $zones,
            'showAllOption' => $showAllOption,
        ]);

    }


    public function show(string $reference_no) {

        $data = $this->meterService::getBill($reference_no);

        if(isset($data['status']) && $data['status'] == 'error') {

            if(empty($data['client']['account_no'])) {
                return redirect()->back()->with('alert', [
                    'status' => 'error',
                    'message' => 'No concessionaire found'
                ]);
            }

            return redirect()->route('reading.index')->with('alert', [
                'status' => 'error',
                'message' => 'Bill Not Found'
            ]);
        }

        $url = env('NOVUPAY_URL') . '/payment/merchants/' . $reference_no;

        $qr_code = $this->generateService::qr_code($url, 80);

        $amount = $data['current_bill']['amount' ?? 0];
        $assumed_penalty = $amount * 0.15;
        $assumed_amount_after_due = $amount + $assumed_penalty;

        $data['current_bill']['assumed_penalty'] = $assumed_penalty;
        $data['current_bill']['assumed_amount_after_due'] = $assumed_amount_after_due;

        $isReRead = [
            'status' => $data['current_bill']['reading']['isReRead'] ?? false,
            'reference_no' => $data['current_bill']['reading']['reread_reference_no']
        ];

        return view('reading.show', compact('data', 'isReRead', 'reference_no', 'qr_code'));
    }

    public function report(Request $request) {
        $user = auth()->user();
        $zone = $request->zone ?? 'all';
        $entries = $request->entries ?? 10;
        $toSearch = $request->search ?? '';
        $date = $request->date ?? $this->meterService->getLatestReadingMonth();

        $zonesQuery = DB::table('concessioner_accounts');

        if ($user->user_type === 'technician' && !empty($user->zone_assigned)) {
            $assignedZones = explode(',', $user->zone_assigned);
            $zonesQuery->whereIn('zone', $assignedZones);
        }

        $zonesRaw = $zonesQuery
            ->select('zone', DB::raw('COUNT(*) as total_accounts'))
            ->groupBy('zone')
            ->get();

        $readingsPerZone = DB::table('readings')
            ->join('concessioner_accounts', 'readings.account_no', '=', 'concessioner_accounts.account_no')
            ->select('concessioner_accounts.zone', DB::raw('COUNT(*) as read_count'))
            ->whereMonth('readings.created_at', Carbon::parse($date)->month)
            ->whereYear('readings.created_at', Carbon::parse($date)->year);

        if ($user->user_type === 'technician' && !empty($user->zone_assigned)) {
            $readingsPerZone->whereIn('concessioner_accounts.zone', $assignedZones);
        }

        $readingsPerZone = $readingsPerZone->groupBy('concessioner_accounts.zone')->pluck('read_count', 'zone');

        $zoneAreas = DB::table('zones')->pluck('area', 'zone');

        $zones = $zonesRaw->map(function ($zone) use ($readingsPerZone, $zoneAreas) {
            $zone->read_count = $readingsPerZone[$zone->zone] ?? 0;
            $zone->area = $zoneAreas[$zone->zone] ?? 'Unknown';
            return $zone;
        });

        $collection = collect($this->meterService::getReport($zone, $date, $toSearch))->flatten(2);

        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $collection->slice(($currentPage - 1) * $entries, $entries)->values();

        $data = new LengthAwarePaginator(
            $currentItems,
            $collection->count(),
            $entries,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('reading.report', compact('data', 'entries', 'zones', 'zone', 'date', 'toSearch'));
    }


    public function store(Request $request) {
        $payload = $request->all();

        Log::info('Reading Store Request', $request->all());


        if(isset($payload['isClearRecent']) && $payload['isClearRecent'] == true) {
            session()->forget('recent_reading');
            return response()->json([
                'status' => 'success',
                'message' => 'recent reading cleared'
            ]);
        }

        $validator = Validator::make($payload, [
        'reading_month' => [
            function ($attribute, $value, $fail) {
                if ($this->isTesting && empty($value)) {
                    return $fail('Reading month is required.');
                }
            }
        ],
        'account_no' => [
            'required',
            function ($attribute, $value, $fail) {
                if (!DB::table('concessioner_accounts')->where('account_no', $value)->exists()) {
                    $fail('The meter no. or account no. does not exist.');
                }
            },
        ],
        'previous_reading' => 'required|integer|min:0',
        'present_reading' => 'required|integer|min:0',
        'is_high_consumption' => 'required|in:yes,no',
        'isReRead' => 'required|in:true,false',
        'reference_no' => 'nullable|exists:bill,reference_no'
    ]);


    if ($validator->fails()) {
        return response()->json([
            'status' => 'error',
            'message' => 'Validation failed.',
            'errors' => $validator->errors()
        ], 422);
    }

    try {
        $date = $this->isTesting
            ? Carbon::createFromFormat('Y-m-d', $payload['reading_month'])
            : Carbon::now();
    } catch (\Exception $e) {
        return response()->json([
            'status' => 'error',
            'message' => 'Invalid reading month format.'
        ], 400);
    }

    $month = $date->month;
    $year = $date->year;
    $account_no = $payload['account_no'];
    $isReRead = $payload['isReRead'] === 'true' ? true : false;

    if (!$isReRead) {
        $exists = Reading::whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->where('account_no', $account_no)
            ->exists();

        if ($exists) {
            return response()->json([
                'status' => 'error',
                'message' => "Reading already exists for {$date->format('F Y')}."
            ], 409);
        }
    }

    DB::beginTransaction();

    try {
        $account = $this->meterService->getAccount($account_no);
        Log::info('Account info:', ['account' => $account]);

        $present_reading = $payload['present_reading'];
        $previous_reading = $payload['previous_reading'];
        $consumption = $present_reading - $previous_reading;

        if ($consumption < 0) {
            throw new \Exception('Present reading must be greater than or equal to previous reading.');
        }

        $propertyTypeId = DB::table('property_types')
            ->where('name', $account->property_type)
            ->value('id');

        if (!$propertyTypeId) {
            return response()->json([
                'status' => 'error',
                'message' => "No property type found for '{$account->property_type}'."
            ], 400);
        }

        $computed = $this->meterService->create_breakdown([
            'account_no' => $account_no,
            'property_types_id' => $propertyTypeId,
            'present_reading' => $present_reading,
            'previous_reading' => $previous_reading,
            'consumption' => $consumption,
            'date' => $date,
            'is_high_consumption' => $payload['is_high_consumption'],
            'isReRead' => $isReRead,
            'reference_no' => $payload['reference_no'] ?? null
        ]);

        if ($computed['status'] !== 'success') {
            DB::rollBack();
            return response()->json($computed, 400);
        }

        $billData = $computed['bill'];
        $reference_no = $billData['reference_no'];
        $amount = $billData['amount'];

        $basicCharge = $computed['basic_charge'];
        $totalAmount = $computed['bill']['amount'];

        // Save bill
        $bill = Bill::updateOrCreate(
            ['reference_no' => $reference_no],
            [
                'account_no' => $account_no,
                'amount' => $amount,
                'penalty' => 0,
                'discount' => $computed['bill']['discount'] ?? 0,
                'amount_after_due' => $computed['bill']['amount_after_due'] ?? $amount
            ]
        );

        $today = Carbon::today();

        $discountRecord = Discount::where('account_no', $account->account_no)
            ->whereDate('effective_date', '<=', $today)
            ->whereDate('expired_date', '>=', $today)
            ->first();

        Log::info('Checking discount record', [
            'account_no' => $account->account_no,
            'record' => $discountRecord ? $discountRecord->toArray() : null,
            'type_id' => $discountRecord ? $discountRecord->discount_type_id : null,
        ]);

        $totalDiscount = 0;

        if ($discountRecord && $discountRecord->discount_type_id) {
            $discountTypeRow = DiscountType::find($discountRecord->discount_type_id);
            Log::info('DiscountTypeRow', [
                'discount_type_row' => $discountTypeRow ? $discountTypeRow->toArray() : null
            ]);

            if ($discountRecord->discount_type_id == 1) {
                $seniorDiscount = PaymentDiscount::where('eligible', 'senior')->first();

                if ($seniorDiscount) {
                    //calculate baseAmount based on percentage_of
                    $baseAmount = $seniorDiscount->percentage_of === 'basic_charge' ? $basicCharge : $totalAmount;

                    $seniorAmount = $seniorDiscount->type === 'fixed'
                        ? round(floatval($seniorDiscount->amount), 2)
                        : round($baseAmount * floatval($seniorDiscount->amount), 2);

                    BillDiscount::create([
                        'bill_id' => $bill->id,
                        'name' => $seniorDiscount->name,
                        'description' => $seniorDiscount->type ?? null,
                        'amount' => $seniorAmount,
                    ]);

                    Log::info('Senior discount applied', ['amount' => $seniorAmount]);

                    $totalDiscount += $seniorAmount;
                }
            }

            // Franchise Discount
            if ($discountRecord->discount_type_id == 2) {
                $franchiseDiscount = PaymentDiscount::where('eligible', 'franchise')->first();

                if ($franchiseDiscount) {
                    $baseAmount = $franchiseDiscount->percentage_of === 'basic_charge' ? $basicCharge : $totalAmount;

                    $franchiseAmount = $franchiseDiscount->type === 'fixed'
                        ? round(floatval($franchiseDiscount->amount), 2)
                        : round($baseAmount * floatval($franchiseDiscount->amount), 2);

                    BillDiscount::create([
                        'bill_id' => $bill->id,
                        'name' => $franchiseDiscount->name,
                        'description' => $franchiseDiscount->type ?? null,
                        'amount' => $franchiseAmount,
                    ]);

                    $totalDiscount += $franchiseAmount;

                    Log::info('Franchise discount applied', ['amount' => $franchiseAmount]);
                }
            }

        } else {
            Log::info('No valid discount for this account', ['account_no' => $account->account_no]);
        }

        $bill->update([
            'discount' => $totalDiscount,
            'amount_after_due' => $bill->amount - $totalDiscount
        ]);

        // Generate payment QR
        $paymentPayload = [
            'reference_no' => $reference_no,
            'amount' => $bill->amount_after_due,
            'customer' => [
                'name' => $account->user->name ?? '',
                'account_no' => $account->account_no,
                'address' => $account->address ?? ''
            ]
        ];

        $qrResponse = $this->generatePaymentQR($reference_no, $paymentPayload);

                if (!$qrResponse) {
                    DB::rollBack();
                    return response()->json([
                        'status' => 'error',
                        'message' => 'Failed to save this transaction. Please try again later.'
                    ], 500);
                }


                session(['recent_reading' => [
                    'name' => $account->user->name ?? '',
                    'address' => $account->address ?? '',
                    'account_no' => $account->account_no ?? '',
                    'timestamp' => Carbon::now()
                ]]);

                DB::commit();

                return response()->json([
                    'status' => 'success',
                    'message' => 'Bill has been created, redirecting...',
                    'redirect_url' => route('reading.show', ['reference_no' => $reference_no]),
                    'data' => [
                        'reference_no' => $reference_no,
                        'amount' => $amount,
                        'customer' => $paymentPayload['customer']
                    ]
                ], 201);

            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Reading Store Error:', ['exception' => $e]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Error occurred: ' . $e->getMessage()
                ], 500);
            }
        }


    private function convertAmount(float $amount): string
    {
        $amountFloat = floatval(str_replace(',', '', $amount));

        if (fmod($amountFloat, 1) === 0.0) {
            return number_format($amountFloat, 2, '', '');
        } else {

            $amountNoDot = str_replace('.', '', number_format($amountFloat, 2, '.', ''));
            return $amountNoDot;
        }
    }

    // private function generatePaymentQR(string $reference_no, array $payload) {

    //     // $api = env('NOVUPAY_URL') . '/api/v1/save/transaction';
    //     $api = 'http://localhost/api/v1/save/transaction';

    //     $ch = curl_init($api);
    //     curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    //     curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    //     curl_setopt($ch, CURLOPT_HTTPHEADER, [
    //         'Content-Type: application/json'
    //     ]);
    //     curl_setopt($ch, CURLOPT_POST, true);
    //     curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

    //     $response = curl_exec($ch);
    //     $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    //     $curlError = curl_errno($ch) ? curl_error($ch) : null;
    //     curl_close($ch);

    //     $decodedResponse = json_decode($response, true);

    //     if(is_null($decodedResponse)) {
    //         Log::error('error: ' . $decodedResponse);
    //         return false;
    //     }

    //     if ($httpCode == 200) {

    //         if ($decodedResponse['status'] == 'success' && isset($decodedResponse['reference_no'])) {
    //             return true;
    //         } else {
    //             Log::error('error: ' . $decodedResponse);
    //             return false;
    //         }

    //     } else {
    //         Log::error('error: ' . $decodedResponse);
    //         return false;
    //     }

    // }

    // Temporary payment QR generator for testing
    private function generatePaymentQR(string $reference_no, array $payload)
    {
        Log::info('generatePaymentQR TEST MODE', [
            'reference_no' => $reference_no,
            'payload' => $payload,
        ]);

        return [
            'status' => 'success',
            'reference_no' => $reference_no,
            'qr_code' => 'TEST_QR_CODE_' . $reference_no
        ];
    }


    public function datatable($query)
{
    $user = auth()->user();

    if ($user->user_type === 'technician' && !empty($user->zone_assigned)) {
        $assignedZones = explode(',', $user->zone_assigned);
        $query->whereHas('account', function ($q) use ($assignedZones) {
            $q->whereIn('zone', $assignedZones);
        });
    }

    return DataTables::of($query)
        ->addIndexColumn()
        ->editColumn('account_no', function ($row) {
            return $row->account_no;
        })
        ->editColumn('created_at', function ($row) {
            return Carbon::parse($row->created_at)->format('F d, Y');
        })
        ->addColumn('actions', function ($row) {
            return
                '<div class="d-flex align-items-center gap-2">
                    <a href="' . route('reading.show', $row->bill->reference_no) . '"
                        class="btn btn-primary text-white text-uppercase fw-bold"
                        id="show-btn" data-id="' . e($row->id) . '">
                        <i class="bx bx-receipt"></i>
                    </a>
                </div>';
        })
        ->rawColumns(['actions'])
        ->make(true);
    }


    public function create_breakdown(array $data) {
        try {
            // Log inputs for debugging
            Log::info('Creating breakdown with data:', $data);

            $rate = Rates::where('property_types_id', $data['property_types_id'])
            ->where('cu_m', '<=', $data['consumption'])
            ->orderBy('cu_m', 'desc')
            ->first();

        if (!$rate) {
            Log::warning("No rate found for property type {$data['property_types_id']} and consumption {$data['consumption']}");

            // Optional: fallback rate
            $rate = Rates::where('property_types_id', $data['property_types_id'])
                ->orderBy('cu_m', 'desc')
                ->first();

            if (!$rate) {
                return [
                    'status' => 'error',
                    'message' => "No valid rates found for property type {$data['property_types_id']}. Please configure the rate table."
                ];
            }
        }


        return [
            'status' => 'success',
            'bill' => [
                'reference_no' => 'REF' . now()->timestamp,
                'amount' => $rate->amount,
                'penalty' => 0
            ]
        ];
        } catch (\Exception $e) {
            Log::error('create_breakdown failed', ['error' => $e->getMessage()]);

            return [
                'status' => 'error',
                'message' => 'An unexpected error occurred during billing.'
            ];
        }
    }



}
