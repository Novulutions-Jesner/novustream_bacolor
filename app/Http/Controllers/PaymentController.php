<?php

namespace App\Http\Controllers;

use App\Imports\PreviousBillingImport;
use App\Models\Bill;
use App\Services\GenerateService;
use App\Services\MeterService;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;
use Yajra\DataTables\Facades\DataTables;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Maatwebsite\Excel\Excel as ExcelFormat;

class PaymentController extends Controller
{

    public $meterService;
    public $generateService;

    public function __construct(MeterService $meterService,
        GenerateService $generateService) {
        $this->meterService = $meterService;
        $this->generateService = $generateService;
    }

    public function index(Request $request)
    {

        $filter = $request->filter ?? '';

        if (!in_array($filter, ['unpaid', 'paid'], true)) {
            return redirect()->route('payments.index', ['filter' => 'unpaid']);
        }

        $zones = $this->meterService->getZones();
        $zone = $request->zone ?? 'all';

        $entries = $request->entries ?? 10;
        $toSearch = $request->search ?? '';
        $date = $request->date ?? $this->meterService->getLatestReadingMonth();

        $collection = collect($this->meterService::getPayments($filter, $zone, $date, $toSearch))
            ->flatten(2);

        $currentPage = LengthAwarePaginator::resolveCurrentPage();
        $currentItems = $collection->slice(($currentPage - 1) * $entries, $entries)->values();

        $data = new LengthAwarePaginator(
            $currentItems,
            $collection->count(),
            $entries,
            $currentPage,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('payments.index', compact('data', 'entries', 'filter', 'zones', 'zone', 'date', 'toSearch'));
    }

    public function upload(Request $request)
    {
        if ($request->getMethod() !== 'POST') {
            return view('payments.upload');
        }

        if (!$request->hasFile('file')) {
            return response()->json([
                'status' => 'error',
                'message' => 'No file uploaded.',
            ]);
        }

        $file = $request->file('file');

        if (
            !$file->isValid() ||
            $file->getClientOriginalExtension() !== 'xlsx' ||
            $file->getMimeType() !== 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ) {
            return response()->json([
                'status' => 'error',
                'message' => 'Only Excel (.xlsx) files are allowed.',
            ]);
        }

        $spreadsheet = IOFactory::load($file->getRealPath());
        $sheetNames = $spreadsheet->getSheetNames();

        $expectedHeaders = [
            'reference_no', 'account_no', 'billing_from', 'billing_to', 'previous_reading',
            'present_reading', 'consumption', 'penalty', 'unpaid', 'amount',
            'amount_paid', 'change', 'date_paid', 'due_date', 'payor_name',
            'payment_reference_no',
        ];

        $allMessages = [];
        $importedSheets = [];

        $headingData = (new HeadingRowImport(2))->toArray($file);

        foreach ($sheetNames as $index => $sheetName) {
            $actualHeaders = array_map('strtolower', array_map('trim', $headingData[$index][0] ?? []));
            $missing = array_diff($expectedHeaders, $actualHeaders);

            if (!empty($missing)) {
                $allMessages[] = [
                    'sheet' => $sheetName,
                    'status' => 'error',
                    'message' => 'Missing headers in sheet.',
                    'missing_headers' => array_values($missing),
                ];
                continue;
            }

            try {
                $importInstance = new PreviousBillingImport($sheetName);

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

                $failures = $importInstance->failures();
                $failureErrors = [];

                if ($failures->isNotEmpty()) {
                    foreach ($failures as $failure) {
                        $row = $failure->row();
                        foreach ($failure->errors() as $error) {
                            $failureErrors[] = "Row [$row]: $error";
                        }
                    }
                }

                $skippedRows = $importInstance->getSkippedRows();
                $rowCount = $importInstance->getRowCounter();
                $totalImported = max($rowCount - 2 - count($failureErrors) - count($skippedRows), 0);

                if (!empty($failureErrors) || !empty($skippedRows)) {
                    $message = [];
                    if (!empty($failureErrors)) {
                        $message[] = count($failureErrors) . ' skipped due to logic checks';
                    }
                    if (!empty($skippedRows)) {
                        $message[] = count($skippedRows) . ' skipped due to logic checks';
                    }

                    $allMessages[] = [
                        'sheet' => $sheetName,
                        'status' => 'warning',
                        'message' => "Total of <b>(".number_format($totalImported, 0).")</b> records partially imported. <br>" . implode(', ', $message),
                        'errors' => array_merge($failureErrors, $skippedRows),
                    ];
                } else {
                    $allMessages[] = [
                        'sheet' => $sheetName,
                        'status' => 'success',
                        'message' => "Total of <b>(".number_format($totalImported, 0).")</b> records imported successfully.",
                    ];
                }

            } catch (\Maatwebsite\Excel\Validators\ValidationException $e) {
                $failures = $e->failures();
                $messages = [];

                foreach ($failures as $failure) {
                    $row = $failure->row();
                    foreach ($failure->errors() as $error) {
                        $messages[] = "Row [$row]: $error";
                    }
                }

                $allMessages[] = [
                    'sheet' => $sheetName,
                    'status' => 'error',
                    'message' => 'Validation errors found during import.',
                    'errors' => $messages,
                ];
            } catch (\Exception $e) {
                \Log::error("Import error on sheet '$sheetName': " . $e->getMessage(), [
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

   public function pay(Request $request, string $reference_no)  {
    if ($request->isMethod('post')) {
        $payload = $request->all();

        switch ($payload['payment_type']) {
            case 'cash':
                return $this->processCashPayment($reference_no, $payload);

            case 'online':
                // Redirect to BUx flow (instead of handling here directly)
                return $this->processOnlinePayment($reference_no, $payload);
        }
    }

    // ======== GET BILL DETAILS ========
    $data = $this->meterService::getBill($reference_no);

    if (isset($data['status']) && $data['status'] === 'error') {
        return redirect()->back()->with('alert', [
            'status' => 'error',
            'message' => $data['message']
        ]);
    }

    // Prevent duplicate payment session
    if (!is_null($data['active_payment'])) {
        return redirect()->route('payments.pay', ['reference_no' => $data['active_payment']['reference_no']]);
    }

    // ======== QR CODE (for walk-in / display) ========
    $url = env('NOVUPAY_URL') . '/payment/merchants/' . $reference_no;
    // 👆 You can also switch this back to NovuPay if QR code is tied to them
    $qr_code = $this->generateService::qr_code($url, 80);

    // ======== Compute Amounts ========
    $amount = $data['current_bill']['amount'] ?? 0;
    $assumed_penalty = $amount * 0.15;
    $assumed_amount_after_due = $amount + $assumed_penalty;

    $data['current_bill']['assumed_penalty'] = $assumed_penalty;
    $data['current_bill']['assumed_amount_after_due'] = $assumed_amount_after_due;

    return view('payments.pay', compact('data', 'reference_no', 'qr_code'));
}


    private function getBill(string $reference_no, $payload = null, bool $strictAmount = false)
    {
        $data = $this->meterService::getBill($reference_no);

        if (!$data || !isset($data['current_bill'])) {
            return ['error' => 'Bill not found'];
        }

        $total = (float) $data['current_bill']['amount'] + (float) $data['current_bill']['penalty'];

        if($strictAmount) {
            $validator = Validator::make($payload, [
                'payment_amount' => 'required|gte:' . $total
            ], [
                'payment_amount.gte' => 'Cash payment is insufficient'
            ]);

            if ($validator->fails()) {
                return ['error' => $validator->errors()->first()];
            }
        }

        return ['data' => $data];
    }

    public function processCashPayment(string $reference_no, array $payload) {

        $result = $this->getBill($reference_no, $payload, true);

        if (isset($result['error'])) {
            return redirect()->back()->with('alert', [
                'status' => 'error',
                'message' => $result['error']
            ]);
        }

        $data = $result['data'];

        $now = Carbon::now()->format('Y-m-d H:i:s');

        $amount = (float) $data['current_bill']['amount'] + (float) $data['current_bill']['penalty'];
        $change = (float) $payload['payment_amount'] - $amount;
        $forAdvancePayment = isset($payload['for_advances']) && $payload['for_advances'] ? true : false;

        $saveChange = false;

        if($change != 0 && $forAdvancePayment) {
            $saveChange = true;
        }

        $currentBill = Bill::find($data['current_bill']['id']);

        if ($currentBill) {
            $currentBill->update([
                'isPaid' => true,
                'amount_paid' => $payload['payment_amount'],
                'change' => $change,
                'payor_name' => $payload['payor'],
                'date_paid' => $now,
                'isChangeForAdvancePayment' => $saveChange
            ]);
        }

        if (!empty($data['unpaid_bills'])) {
            foreach ($data['unpaid_bills'] as $unpaid_bill) {
                $unpaidBill = Bill::find($unpaid_bill['id']);

                if ($unpaidBill) {
                    $unpaidBill->update([
                        'payor_name' => $payload['payor'],
                        'date_paid' => $now,
                        'isPaid' => true,
                        'amount_paid' => $payload['payment_amount'],
                        'change' => $change,
                        'paid_by_reference_no' => $reference_no
                    ]);
                }
            }
        }


        return redirect()->back()->with('alert', [
            'status' => 'success',
            'message' => 'Bill has been paid'
        ]);
    }

    public function processOnlinePaymentOld(string $reference_no, array $payload) {

        $result = $this->getBill($reference_no, $payload, false);


        if (isset($result['error'])) {
            return redirect()->back()->with('alert', [
                'status' => 'error',
                'message' => $result['error']
            ]);
        }

        $url = env('NOVUPAY_URL') . '/payment/merchants/' . $reference_no;

        return redirect()->route('payments.pay', ['reference_no' => $reference_no])->with('alert', [
            'status' => 'success',
            'payment_request' => true,
            'redirect' => $url,
        ]);

    }

    public function processOnlinePayment(string $reference_no, array $payload){
        $result = $this->getBill($reference_no, $payload, false);
        // var_dump($payload);
        // echo "--------------0000--------------";
        // var_dump($result); exit;
        if (isset($result['error'])) {
            return redirect()->back()->with('alert', [
                'status' => 'error',
                'message' => $result['error']
            ]);
        }

        $data = $result['data'];

        $amount = (float) $data['current_bill']['amount'] + (float) $data['current_bill']['penalty'];
        $amount = number_format($amount, 2, '.', '');

        // === Build BUx payload ===
        $buxPayload = [
            "req_id"          => (string) $data['current_bill']['reference_no'],
            "client_id"       => env('NOVUPAY_CLIENT_ID'),
            "amount"          => $amount,
            "description"     => "Payment for bill period {$data['current_bill']['bill_period_from']} to {$data['current_bill']['bill_period_to']}",
            "expiry"          => 2,
            "email"           => (string) ($data['client']['email'] ?? 'jeff@novulutions.com'),
            "contact"         => (string) $data['client']['contact_no'],
            "name"            => (string) $data['client']['name'],
            'notification_url' => 'https://1dc0247c4b66.ngrok-free.app/payments/webhook',
            "redirect_url"    => 'https://1dc0247c4b66.ngrok-free.app/payments/redirect/' . $reference_no,
            "param1"          => "Bacolor",
            "param2"          => (string) $data['client']['account_no'],
            "enabled_channels"=> []
        ];

        // \Log::info('BUx Payload', $buxPayload); // still logs it in storage/logs/laravel.log
        // dd($buxPayload); // dumps to browser and stops execution


        $client = new \GuzzleHttp\Client();

        $client = new \GuzzleHttp\Client([
            'base_uri' => env('NOVUPAY_ENDPOINT'),
            'http_errors' => false,
            'debug' => true, // this will dump full request/response to stdout
        ]);



        try {
            $client = new \GuzzleHttp\Client();

            $response = $client->post(env('NOVUPAY_ENDPOINT'), [
                'headers' => [
                    'x-api-key'     => env('NOVUPAY_API_KEY'),
                    'client-id'     => env('NOVUPAY_CLIENT_ID'),
                    'client-secret' => env('NOVUPAY_CLIENT_SECRET'),
                    'Content-Type'  => 'application/json',
                ],
                'body' => json_encode($buxPayload, JSON_UNESCAPED_SLASHES),
            ]);




            $result = json_decode($response->getBody()->getContents(), true);

            if (!isset($result['checkout_url'])) {
                return redirect()->back()->with('alert', [
                    'status' => 'error',
                    'message' => 'Unable to generate BUx checkout link.'
                ]);
            }

            // === Redirect user to BUx checkout page ===
            return redirect()->route('payments.pay', ['reference_no' => $reference_no])->with('alert', [
                'status'          => 'success',
                'payment_request' => true,
                'redirect'        => $result['checkout_url'],
            ]);

        } catch (\Exception $e) {
            return redirect()->back()->with('alert', [
                'status' => 'error',
                'message' => 'BUx integration failed: ' . $e->getMessage()
            ]);
        }
    }


    public function callback(Request $request, string $reference_no) {

        $payload = $request->all();

        $bill = $this->meterService->getBill($reference_no);

        if($bill) {

            $now = Carbon::now()->format('Y-m-d H:i:s');

            $bill['current_bill']->update([
                'isPaid' => true,
                'amount_paid' => $payload['amount'],
                'date_paid' => $now,
            ]);

            if (!empty($bill['unpaid_bills'])) {
                foreach ($bill['unpaid_bills'] as $unpaid_bill) {
                    $unpaid_bill->update([
                        'isPaid' => true,
                        'amount_paid' => $payload['amount'],
                        'date_paid' => $now,
                        'paid_by_reference_no' => $reference_no
                    ]);
                }
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Payment successful'
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Payment not found'
        ], 404);

    }

    public function datatable($query) {
        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('account_no', function($row) {
                return $row->reading->account_no ?? 'N/A';
            })
            ->editColumn('billing_period', function ($row) {
                return ($row->bill_period_from && $row->bill_period_to)
                    ? Carbon::parse($row->bill_period_from)->format('M d, Y') . ' TO ' . Carbon::parse($row->bill_period_to)->format('M d, Y')
                    : 'N/A';
            })
            ->editColumn('bill_date', function ($row) {
                return !empty($row->bill_period_to)
                    ? Carbon::parse($row->bill_period_to)->format('M d, Y')
                    : 'N/A';
            })
            ->editColumn('amount', function ($row) {
                return '₱' . number_format((float)($row->amount ?? 0), 2);
            })
            ->editColumn('due_date', function ($row) {
                return !empty($row->due_date)
                    ? Carbon::parse($row->due_date)->format('M d, Y')
                    : 'N/A';
            })
            ->editColumn('status', function ($row) {
                return $row->isPaid
                    ? '<div class="alert alert-primary mb-0 py-1 px-2 text-center">Paid</div>'
                    : '<div class="alert alert-danger mb-0 py-1 px-2 text-center">Unpaid</div>';
            })
            ->addColumn('actions', function ($row) {
                if(!$row->isPaid) {
                    return '
                    <div class="d-flex align-items-center gap-2">
                        <a href="' . route('payments.pay', ['reference_no' => $row->reference_no]) . '"
                            class="btn btn-primary text-white text-uppercase fw-bold">
                            <i class="bx bx-credit-card-alt" ></i>
                        </a>
                    </div>';
                } else {
                    return
                    '<div class="d-flex align-items-center gap-2">
                        <a target="_blank" href="' . route('reading.show', $row->reference_no) . '"
                            class="btn btn-primary text-white text-uppercase fw-bold"
                            id="show-btn" data-id="' . e($row->id) . '">
                            <i class="bx bx-receipt"></i>
                        </a>
                    </div>';
                }
            })
            ->rawColumns(['status', 'actions'])
            ->make(true);
    }


    public function webhook(Request $request)
        {
            // BUx will send JSON payload here when status changes
            \Log::info('BUx Webhook Received', $request->all());

            // You can also validate req_id, amount, etc. here

            return response()->json(['status' => 'ok']);
        }


}

