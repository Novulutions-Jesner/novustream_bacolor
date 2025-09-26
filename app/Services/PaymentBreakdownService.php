<?php

namespace App\Services;

use App\Models\PaymentBreakdown;
use App\Models\PaymentBreakdownPenalty;
use App\Models\PaymentDiscount;
use App\Models\PaymentServiceFee;
use App\Models\Ruling;
use App\Models\Bill;
use Illuminate\Support\Facades\DB;

class PaymentBreakdownService {

    public static function getData(?int $id = null) {

        if(!is_null($id)) {
            return PaymentBreakdown::where('id', $id)
                ->first() ?? null;
        }

        return PaymentBreakdown::get();

    }

    public static function getPenalty(?int $id = null) {

        if(!is_null($id)) {
            return PaymentBreakdownPenalty::where('id', $id)
                ->first() ?? null;
        }

        return PaymentBreakdownPenalty::get();

    }

    public static function getServiceFee(?int $id = null) {

        if(!is_null($id)) {
            return PaymentServiceFee::with('property')->where('id', $id)
                ->first() ?? null;
        }

        return PaymentServiceFee::with('property')->get();

    }

     public static function getDiscounts(?int $id = null) {
        if (!is_null($id)) {
            return PaymentDiscount::where('id', $id)->first() ?? null;
        }
        return PaymentDiscount::all();
    }



    public static function create(array $payload) {
        DB::beginTransaction();

        try {
            $message = '';

            switch ($payload['action']) {
                case 'regular':
                    $model = PaymentBreakdown::create([
                        'name' => $payload['name'],
                        'type' => $payload['type'],
                        'percentage_of' => $payload['percentage_of'],
                        'amount' => $payload['amount'],
                    ]);
                    $message = 'Payment breakdown added';
                    break;

                case 'penalty':
                    $penalties = array_map(
                        fn($from, $to, $amount_type, $amount) => compact('from', 'to', 'amount_type', 'amount'),
                        $payload['penalty']['from'] ?? [],
                        $payload['penalty']['to'] ?? [],
                        $payload['penalty']['amount_type'] ?? [],
                        $payload['penalty']['amount'] ?? []
                    );

                    PaymentBreakdownPenalty::query()->delete();

                    foreach ($penalties as $item) {
                        $model = PaymentBreakdownPenalty::updateOrCreate(
                            [
                                'due_from' => $item['from'],
                                'due_to' => $item['to'],
                            ],
                            [
                                'amount_type' => $item['amount_type'],
                                'amount' => $item['amount'],
                            ]
                        );

                        if ($model->wasRecentlyCreated) {
                            $message = 'Payment penalty added';
                        } else {
                            $message = 'Payment penalty updated';
                        }
                    }
                    break;

                case 'service-fee':
                    $serviceFees = array_map(
                        fn($property_type, $amount) => compact('property_type', 'amount'),
                        $payload['service_fee']['property_type'] ?? [],
                        $payload['service_fee']['amount'] ?? []
                    );

                    PaymentServiceFee::query()->delete();

                    foreach ($serviceFees as $item) {
                        $model = PaymentServiceFee::updateOrCreate(
                            [
                                'property_id' => $item['property_type'],
                            ],
                            [
                                'amount' => $item['amount'],
                            ]
                        );

                        if ($model->wasRecentlyCreated) {
                            $message = 'Service fee added';
                        } else {
                            $message = 'Service fee updated';
                        }
                    }
                    break;

                case 'discount':
                    $model = PaymentDiscount::create([
                        'eligible' => $payload['eligible'],
                        'name' => $payload['name'],
                        'type' => $payload['type'],
                        'percentage_of' => $payload['percentage_of'],
                        'amount' => $payload['amount'],
                    ]);
                    $message = 'Payment discount added';
                    break;

                case 'ruling':
                    $firstRow = Ruling::first();

                    if ($firstRow) {
                        $firstRow->update([
                            'due_date' => $payload['due_date'],
                            'disconnection_date' => $payload['disconnection_date'],
                            'disconnection_rule' => $payload['disconnection_rule'],
                            'snr_dc_rule' => $payload['snr_dc_rule']
                        ]);
                        $message = 'Ruling updated';
                    } else {
                        Ruling::create([
                            'due_date' => $payload['due_date'],
                            'disconnection_date' => $payload['disconnection_date'],
                            'disconnection_rule' => $payload['disconnection_rule'],
                            'snr_dc_rule' => $payload['snr_dc_rule']
                        ]);
                        $message = 'Ruling added';
                    }
                    break;

                default:
                    throw new \Exception('Invalid action provided.');
            }

            DB::commit();

            return [
                'status' => 'success',
                'message' => $message ?: 'Operation completed successfully',
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'status' => 'error',
                'message' => 'Error occurred: ' . $e->getMessage(),
            ];
        }
    }


    public static function update(?int $id, array $payload) {

        DB::beginTransaction();

        try {

            $updateData = [
                'name' => $payload['name'],
                'type' => $payload['type'],
                'amount' => $payload['amount'],
            ];

            PaymentBreakdown::where('id', $id)->update($updateData);

            DB::commit();

            return [
                'status' => 'success',
                'message' => 'Payment breakdown  updated.'
            ];

        } catch (\Exception $e) {

            DB::rollBack();

            return [
                'status' => 'error',
                'message' => 'Error occured: ' . $e->getMessage()
            ];
        }

    }

    public static function delete(string $action, ?int $id) {

        DB::beginTransaction();

        try {

            if($action == 'regular') {
                $data = PaymentBreakdown::where('id', $id)->first();
                $data->delete();
            }

            if($action == 'discount') {
                $data = PaymentDiscount::where('id', $id)->first();
                $data->delete();
            }

            DB::commit();

            $message = [
                'regular' => 'Payment breakdown removed.',
                'discount' => 'Payment discount removed.'
            ];

            return [
                'status' => 'success',
                'message' => $message[$action] ?? 'An error occured'
            ];

        } catch (\Exception $e) {

            DB::rollBack();

            return [
                'status' => 'error',
                'message' => 'Error occured: ' . $e->getMessage()
            ];
        }

    }

    public function applyDiscount($bill, $basicCharge, $totalAmount, $eligibleKey = null)
    {
        if (!$eligibleKey) {
            return 0;
        }
        $d = PaymentDiscount::where('eligible', $eligibleKey)->first();
        if (!$d) {
            return 0;
        }

        if ($d->type === 'percentage') {
            if ($eligibleKey === 'senior') {
                $base = $bill->amount; // always total for seniors
            } else {
                $base = ($d->percentage_of === 'total_amount') ? $bill->amount : $basicCharge;
            }
            return round($base * $d->amount, 2);
        }

    }



}
