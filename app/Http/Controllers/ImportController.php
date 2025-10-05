<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;
use PhpOffice\PhpSpreadsheet\IOFactory;

use App\Imports\AdminAccountsImport;
use App\Imports\TechnicianAccountsImport;
use App\Imports\ConcessionaireImport;
use App\Imports\SCDiscountImport;
use App\Imports\RateCodesImport;
use App\Imports\StatusCodeImport;
use App\Imports\ClientInformationImport;
use App\Imports\SettingsImport;

class ImportController extends Controller
{
    public function index(Request $request)
    {
        if ($request->getMethod() !== 'POST') {
            return view('import');
        }

        if (!$request->hasFile('file')) {
            return response()->json([
                'status' => 'error',
                'message' => 'No file uploaded.'
            ]);
        }

        $file = $request->file('file');
        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheetNames = $spreadsheet->getSheetNames();
        $filename = strtolower(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));

        $sheetToProcessMap = [
            'client informations'         => 'client_informations',
            'client info'                 => 'client_informations',
            'admin accounts'              => 'admin_accounts',
            'administrators'              => 'admin_accounts',
            'technician accounts'         => 'technician_accounts',
            'technicians'                 => 'technician_accounts',
            'concessionaire informations' => 'concessionaire',
            'concessionaire'              => 'concessionaire',
            'sc discount'                 => 'sc_discount',
            'senior citizen discount'     => 'sc_discount',
            'advances'                    => 'advances',
            'outstanding balance'         => 'outstanding_balance',
            'rates code'                  => 'rates_code',
            'status code'                 => 'status_code',
            'zones'                       => 'zones',
            'settings'                    => 'settings',
        ];

        $processConfig = [
            'client_informations' => [
                'expected_headers' => ['name','value','description'],
                'import_class' => ClientInformationImport::class,
            ],
            'admin_accounts' => [
                'expected_headers' => ['name','role','email','password'],
                'import_class' => AdminAccountsImport::class,
            ],
            'technician_accounts' => [
                'expected_headers' => ['name','email','password'],
                'import_class' => TechnicianAccountsImport::class,
            ],
            'concessionaire' => [
                'expected_headers' => [
                    'account_no','name','address','zone','rate_code','status',
                    'meter_brand','meter_serial_no','sc_no','date_connected',
                    'contact_no','sequence_no'
                ],
                'import_class' => ConcessionaireImport::class,
            ],
            'advances' => [
                'expected_headers' => ['account_no','amount','as_of'],
                'import_class' => \App\Imports\AdvancesImport::class,
            ],
            'outstanding_balance' => [
                'expected_headers' => ['account_no','amount'],
                'import_class' => \App\Imports\OutstandingBalanceImport::class,
            ],
            'sc_discount' => [
                'expected_headers' => ['account_no','name','id_no','effectivity_date','expired_date', 'type'],
                'import_class' => SCDiscountImport::class,
            ],
            'rates_code' => [
                'expected_headers' => ['rate_code','name','rate','0_10','11_20','21_30','31_40','41_50','51_60'],
                'import_class' => RateCodesImport::class,
            ],
            'status_code' => [
                'expected_headers' => ['code','name'],
                'import_class' => StatusCodeImport::class,
            ],
            'zones' => [
                'expected_headers' => ['zone','area'],
                'import_class' => \App\Imports\ZoneImport::class,
            ],
            'settings' => [
                'expected_headers' => ['name','value','description'],
                'import_class' => SettingsImport::class,
            ],
        ];

        $normalize = function ($header) {
            $header = strtolower(trim($header));
            $header = preg_replace('/[^a-z0-9]+/i', '_', $header);
            return $header;
        };

        $headingData = (new HeadingRowImport(2))->toArray($file);

        $allMessages = [];
        $importedSheets = [];

        foreach ($sheetNames as $index => $sheetName) {
            $sheetKey = strtolower(trim($sheetName));

            if (in_array($sheetKey, ['sheet1','worksheet'])) {
                foreach ($sheetToProcessMap as $alias => $processKey) {
                    if (str_contains($filename, str_replace(' ', '', $alias))) {
                        $sheetKey = $alias;
                        break;
                    }
                }
            }

            if (!isset($sheetToProcessMap[$sheetKey])) {
                $allMessages[] = [
                    'sheet' => $sheetName,
                    'status' => 'error',
                    'message' => 'Unrecognized sheet: ' . $sheetName,
                ];
                continue;
            }

            $processKey = $sheetToProcessMap[$sheetKey];
            $config = $processConfig[$processKey];

            if (!in_array($processKey, ['client_informations', 'settings'])) {
                $expectedHeaders = array_map($normalize, $config['expected_headers']);
                $rawHeaders = $headingData[$index][0] ?? [];
                $actualHeaders = array_map($normalize, $rawHeaders);

                $missingHeaders = array_diff($expectedHeaders, $actualHeaders);

                if (!empty($missingHeaders)) {
                    $allMessages[] = [
                        'sheet' => $sheetName,
                        'status' => 'error',
                        'message' => 'Missing headers: ' . implode(', ', $missingHeaders),
                    ];
                    continue;
                }
            }

            try {
                $importInstance = new $config['import_class']($sheetName);

                if ($processKey === 'technician_accounts' && method_exists($importInstance, 'setUserType')) {
                    $importInstance->setUserType('technician');
                }

                Excel::import(new class($importInstance, $sheetName) implements \Maatwebsite\Excel\Concerns\WithMultipleSheets {
                    private $importInstance;
                    private $sheetName;

                    public function __construct($importInstance, $sheetName)
                    {
                        $this->importInstance = $importInstance;
                        $this->sheetName = $sheetName;
                    }

                    public function sheets(): array
                    {
                        return [$this->sheetName => $this->importInstance];
                    }
                }, $file);

                $importedSheets[] = $sheetName;

                $rowCount = method_exists($importInstance, 'getRowCounter') ? $importInstance->getRowCounter() : 0;
                $skippedRows = method_exists($importInstance, 'getSkippedRows') ? $importInstance->getSkippedRows() : [];
                $totalImported = max($rowCount - count($skippedRows), 0);

                if (!empty($skippedRows)) {
                    $allMessages[] = [
                        'sheet' => $sheetName,
                        'status' => 'warning',
                        'message' => "Total of ($totalImported) records partially imported. " . count($skippedRows) . " skipped.",
                        'errors' => $skippedRows,
                    ];
                } else {
                    $allMessages[] = [
                        'sheet' => $sheetName,
                        'status' => 'success',
                        'message' => "Total of ($totalImported) records imported successfully.",
                    ];
                }
            } catch (\Exception $e) {
                Log::error("Import error on sheet '$sheetName': " . $e->getMessage(), [
                    'trace' => $e->getTraceAsString()
                ]);
                $allMessages[] = [
                    'sheet' => $sheetName,
                    'status' => 'error',
                    'message' => 'An error occurred: ' . $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'status' => 'completed',
            'imported' => $importedSheets,
            'messages' => $allMessages,
        ]);
    }
}
