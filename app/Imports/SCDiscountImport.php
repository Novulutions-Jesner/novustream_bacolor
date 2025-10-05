<?php

namespace App\Imports;

use App\Models\SeniorDiscount;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class SCDiscountImport implements
    ToModel,
    WithHeadingRow,
    WithValidation,
    SkipsEmptyRows,
    SkipsOnFailure,
    WithChunkReading
{
    use SkipsFailures;

    protected array $skippedRows = [];
    protected int $rowCounter = 3;
    protected int $inserted = 0;
    protected int $updated = 0;

    public function rules(): array
    {
        return [
            'account_no' => ['required'],
            // 'id_no' => ['required'],
            // 'effectivity_date' => ['required'],
            // 'expired_date' => ['required'],
            'type' => ['required'],
        ];
    }

    public function customValidationMessages(): array
    {
        return [
            'account_no.required' => 'Missing required field: account_no',
            // 'id_no.required' => 'Missing required field: id_no',
            // 'effectivity_date.required' => 'Missing required field: effectivity_date',
            // 'expired_date.required' => 'Missing required field: expired_date',
            'type.required' => 'Missing required field: type',
        ];
    }

    public function model(array $row)
    {
        $rowNum = $this->rowCounter++;
        $row = array_map('trim', $row);

        try {
            $accountNo = $row['account_no'] ?? null;
            $idNo = $row['id_no'] ?? null;
            $effectiveDate = $this->parseDate($row['effectivity_date'] ?? null);
            $expiredDate = $this->parseDate($row['expired_date'] ?? null);
            $type = $row['discount_type_id'] ?? 1;

            if (!$accountNo || !$idNo || !$effectiveDate || !$expiredDate) {
                $this->skippedRows[] = "Row $rowNum skipped: Missing required data.";
                return null;
            }

            $existing = SeniorDiscount::where('account_no', $accountNo)->first();

            if ($existing) {
                $existing->update([
                    'id_no' => $idNo,
                    'effective_date' => $effectiveDate,
                    'expired_date' => $expiredDate,
                    'discount_type_id' => $type,
                ]);

                $this->updated++;
                return null;
            }

            $this->inserted++;
            return new SeniorDiscount([
                'account_no' => $accountNo,
                'id_no' => $idNo,
                'effective_date' => $effectiveDate,
                'expired_date' => $expiredDate,
                'discount_type_id' => $type,
            ]);
        } catch (\Exception $e) {
            $this->skippedRows[] = "Row $rowNum skipped: Exception - " . $e->getMessage();
            Log::error('Import error in Senior Citizen Discount Sheet', [
                'error' => $e->getMessage(),
                'row' => $row,
                'trace' => $e->getTraceAsString(),
            ]);
            return null;
        }
    }

    private function parseDate($value)
    {
        if (!$value) {
            return null;
        }
        if (is_numeric($value)) {
            return Date::excelToDateTimeObject($value)->format('Y-m-d');
        }
        $timestamp = strtotime($value);
        return $timestamp ? date('Y-m-d', $timestamp) : null;
    }

    public function headingRow(): int
    {
        return 2;
    }

    public function chunkSize(): int
    {
        return 1000;
    }

    public function getSkippedRows(): array
    {
        return $this->skippedRows;
    }

    public function getRowCounter(): int
    {
        return $this->rowCounter;
    }

    public function getInsertedCount(): int
    {
        return $this->inserted;
    }

    public function getUpdatedCount(): int
    {
        return $this->updated;
    }
}
