<?php

namespace App\Imports;

use App\Models\Admin;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithStartRow;

class TechnicianAccountsImport implements ToCollection, SkipsEmptyRows, WithStartRow
{
    protected $rowCounter = 0;
    protected $skippedRows = [];

    public function startRow(): int
    {
        return 3; // Excel template starts data at row 3
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            $rowData = [
                'name'          => isset($row[0]) ? trim($row[0]) : null,
                'zone_assigned' => isset($row[1]) ? trim($row[1]) : null,
                'email'         => isset($row[2]) ? trim($row[2]) : null,
                'contact_no'    => isset($row[3]) ? trim($row[3]) : null, // not stored now
                'password'      => isset($row[4]) ? trim($row[4]) : null,
            ];

            // Skip rows with missing required fields
            if (empty($rowData['name']) || empty($rowData['email']) || empty($rowData['password'])) {
                $this->skippedRows[] = "Row " . ($index + $this->startRow()) . " skipped: Missing required fields.";
                continue;
            }

            // Insert or update technician
            Admin::updateOrCreate(
                ['email' => $rowData['email']],
                [
                    'name'          => $rowData['name'],
                    'user_type'     => 'technician',
                    'zone_assigned' => $rowData['zone_assigned'],
                    'password'      => Hash::make($rowData['password']),
                ]
            );

            $this->rowCounter++;
        }
    }

    public function getRowCounter(): int
    {
        return $this->rowCounter;
    }

    public function getSkippedRows(): array
    {
        return $this->skippedRows;
    }
}
