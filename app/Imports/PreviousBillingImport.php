<?php

namespace App\Imports;

use App\Models\Bill;
use App\Models\Reading;
use App\Models\SeniorDiscount;
use App\Models\BillBreakdown;
use App\Models\BillDiscount;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use App\Services\PaymentBreakdownService;
use Carbon\Carbon;

class PreviousBillingImport implements
    ToModel,
    WithHeadingRow,
    WithChunkReading,
    SkipsEmptyRows,
    SkipsOnFailure
{
    use SkipsFailures;

    protected $skippedRows = [];
    protected $rowCounter = 3;
    protected $sheetName;

    public function __construct($sheetName)
    {
        $this->sheetName = $sheetName;
    }

    public function rules(): array
    {
        return [
            'reference_no'      => ['required'],
            'account_no'        => ['required'],
            'billing_from'      => ['required'],
            'billing_to'        => ['required'],
            'current_bill'      => ['nullable', 'numeric'],
            'arrears'           => ['nullable', 'numeric'],

            'previous_reading'  => ['nullable', 'numeric'],
            'present_reading'   => ['nullable', 'numeric'],
            'consumption'       => ['nullable', 'numeric'],
            'penalty'           => ['nullable', 'numeric'],
            'unpaid'            => ['nullable', 'numeric'],
            'amount_paid'       => ['nullable', 'numeric'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'reference_no.required' => 'Missing required field: reference_no',
            'account_no.required'   => 'Missing required field: account_no',
            'billing_from.required' => 'Missing required field: billing_from',
            'billing_to.required'   => 'Missing required field: billing_to',
        ];
    }

    public function model(array $row)
    {
        $rowNum = $this->rowCounter++;
        $row = array_map(function ($v) {
            return is_string($v) ? trim($v) : $v;
        }, $row);

        $get = function ($keys, $default = null) use ($row) {
            foreach ((array)$keys as $k) {
                if (array_key_exists($k, $row) && $row[$k] !== null && $row[$k] !== '') {
                    return $row[$k];
                }
            }
            return $default;
        };

        $billing_from = $this->transformDate($get(['billing_from']));
        $billing_to   = $this->transformDate($get(['billing_to']));
        $zone         = $this->sheetName;

        $reading_id = Reading::insertGetId([
            'zone'             => $zone ?? null,
            'account_no'       => $get(['account_no']),
            'previous_reading' => $get(['previous_reading']),
            'present_reading'  => $get(['present_reading']),
            'consumption'      => $get(['consumption']),
            'created_at'       => $billing_from,
            'updated_at'       => $billing_from,
        ]);

        $currentBillValue = $get(['current_bill']);
        $arrearsValue = $get(['arrears', 'unpaid']);

        $amount = null;
        if ($currentBillValue !== null && $currentBillValue !== '') {
            $amount = $this->cleanAmount($currentBillValue);
        } elseif ($arrearsValue !== null && $arrearsValue !== '') {
            $amount = $this->cleanAmount($arrearsValue);
        }

        if ($amount === null && isset($row['arrears'], $row['current_bill'])) {
            $amount = $this->cleanAmount($row['arrears']) + $this->cleanAmount($row['current_bill']);
        }

        $bill = Bill::create([
            'reading_id'       => $reading_id,
            'reference_no'     => $get(['reference_no']),
            'bill_period_from' => $billing_from,
            'bill_period_to'   => $billing_to,
            'previous_unpaid'  => $this->cleanAmount($get(['unpaid', 'arrears']) ?? 0),
            'penalty'          => $this->cleanAmount($get(['penalty']) ?? 0),
            'amount'           => $amount,
            'amount_paid'      => $this->cleanAmount($get(['amount_paid']) ?? 0),
            'change'           => $this->cleanAmount($get(['change']) ?? 0),
            'isPaid'           => !empty($get(['amount_paid'])) ? 1 : 0,
            'date_paid'        => $this->transformDate($get(['date_paid'])),
            'due_date'         => $this->transformDate($get(['due_date'])),
            'payor_name'       => $get(['payor_name']),
        ]);

        $payload = [
            'account_no' => $get(['account_no']),
            'previous_unpaid' => $this->cleanAmount($get(['amount_paid']) ?? 0),
            'basic_charge' => $this->cleanAmount($amount),
            'date' => $billing_from,
        ];

        $breakdowns = $this->create_breakdown($payload);

        foreach ($breakdowns['deductions'] as $deduction) {
            BillBreakdown::insert([
                'bill_id' => $bill->id,
                'name' => $deduction['name'],
                'description' => $deduction['description'],
                'amount' => $deduction['amount'],
                'created_at' => $billing_from,
                'updated_at' => $billing_from,
            ]);
        }

        foreach ($breakdowns['discounts'] as $discount) {
            BillDiscount::insert([
                'bill_id' => $bill->id,
                'name' => $discount['name'],
                'description' => $discount['description'],
                'amount' => $discount['amount'],
                'created_at' => $billing_from,
                'updated_at' => $billing_from,
            ]);
        }
    }

    protected function create_breakdown($payload)
    {
        $paymentBreakdownService = new PaymentBreakdownService;
        $other_deductions = $paymentBreakdownService::getData();
        $discounts = $paymentBreakdownService::getDiscounts();

        $deductions = [
            [
                'name' => 'Previous Balance',
                'amount' => $payload['previous_unpaid'],
                'description' => ''
            ],
            [
                'name' => 'Basic Charge',
                'amount' => $payload['basic_charge'],
                'description' => '',
            ],
        ];

        foreach ($other_deductions as $deduction) {
            $amount = $deduction->type === 'percentage'
                ? $payload['basic_charge'] * $deduction->amount
                : $deduction->amount;

            $deductions[] = [
                'name' => $deduction->name,
                'description' => $deduction->type === 'percentage' ? $deduction->amount . '%' : '',
                'amount' => $amount,
            ];
        }

        $sc_discount = SeniorDiscount::where('account_no', $payload['account_no'])->first();

        $appliedDiscounts = [];
        if ($sc_discount) {
            $scStartDate = Carbon::parse($sc_discount->effective_date);
            $scEndDate = Carbon::parse($sc_discount->expired_date);
            $billDate = Carbon::parse($payload['date']);

            $isEligible = $billDate->between($scStartDate, $scEndDate);
            foreach ($discounts as $discount) {
                if ($discount->eligible === 'senior' && $isEligible) {
                    $discountAmount = strtolower($discount->type) === 'percentage'
                        ? $payload['basic_charge'] * $discount->amount
                        : $discount->amount;

                    $appliedDiscounts[] = [
                        'name' => $discount->name,
                        'amount' => $discountAmount,
                        'description' => '',
                    ];
                }
            }
        }

        return [
            'deductions' => $deductions,
            'discounts' => $appliedDiscounts
        ];
    }

    protected function transformDate($value)
    {
        if (is_numeric($value)) {
            return Date::excelToDateTimeObject($value)->format('Y-m-d');
        }

        if (is_string($value) && preg_match('/=DATE\((\d+),(\d+),(\d+)\)/i', $value, $matches)) {
            [$_, $year, $month, $day] = $matches;
            return sprintf('%04d-%02d-%02d', $year, $month, $day);
        }

        $timestamp = strtotime($value);
        return $timestamp ? date('Y-m-d', $timestamp) : null;
    }

    protected function cleanAmount($value)
    {
        if ($value === null) return 0;
        $clean = str_replace([',', ' '], ['', ''], trim((string)$value));
        return is_numeric($clean)
            ? (fmod(floatval($clean), 1.0) === 0.0 ? (int)$clean : floatval($clean))
            : $clean;
    }

    public function chunkSize(): int
    {
        return 10000;
    }

    public function getSkippedRows()
    {
        return $this->skippedRows;
    }

    public function headingRow(): int
    {
        return 2;
    }

    public function getRowCounter()
    {
        return $this->rowCounter;
    }
}
