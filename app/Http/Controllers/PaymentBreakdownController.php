<?php

namespace App\Http\Controllers;

use App\Services\PaymentBreakdownService;
use App\Services\PropertyTypesService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Yajra\DataTables\Facades\DataTables;
use App\Models\Ruling;
use App\Models\PaymentDiscount;


class PaymentBreakdownController extends Controller
{

    public $paymentBreakdownService;
    public $propertyService;

    public function __construct(PaymentBreakdownService $paymentBreakdownService, PropertyTypesService $propertyService) {

        $this->middleware(function ($request, $next) {

            if (!Gate::any(['admin'])) {
                abort(403, 'Unauthorized');
            }

            return $next($request);
        });


        $this->paymentBreakdownService = $paymentBreakdownService;
        $this->propertyService = $propertyService;
    }

    public function index(Request $request) {

        $view = $request->view;

        if(request()->ajax()) {
            if($request->action == 'regular') {
                $regular = $this->paymentBreakdownService::getData();
                return $this->datatable('regular', $regular);
            }

            if($request->action == 'penalty') {
                $penalty = $this->paymentBreakdownService->getPenalty() ?? [];
                return $this->datatable('penalty', $penalty);
            }

            if($request->action == 'service-fee') {
                $service_fee = $this->paymentBreakdownService->getServiceFee() ?? [];
                return $this->datatable('service_fee', $service_fee);
            }

            if($request->action == 'discount') {
                $discount = $this->paymentBreakdownService->getDiscounts() ?? [];
                return $this->datatable('discount', $discount);
            }
        }

        if(is_null($view)) {
            return redirect()->route('payment-breakdown.index', ['view' => 'regular']);
        }

        if(!in_array($view, ['regular', 'penalty', 'discount', 'service-fee', 'ruling'])) {
            return redirect()->route('payment-breakdown.index', ['view' => 'regular']);
        }

        $ruling = Ruling::first() ?? [];

        return view('payment-breakdown.index', compact('view', 'ruling'));
    }

    public function create(Request $request) {

        $action = $request->action;

        if(!in_array($action, ['regular', 'penalty', 'discount', 'service-fee'])) {
            return redirect()->route('payment-breakdown.index');
        }

        $penalty = $this->paymentBreakdownService->getPenalty() ?? [];
        $service_fee = $this->paymentBreakdownService->getServiceFee() ?? [];
        $property_types = $this->propertyService->getData() ?? [];

        return view('payment-breakdown.form', compact('action', 'penalty', 'service_fee', 'property_types'));
    }

    public function store(Request $request) {

        $payload = $request->all();

        $action = $payload['action'];

        if(!in_array($action, ['regular', 'penalty', 'discount', 'service-fee', 'ruling'])) {
            return;
        }

        if($action == 'regular') {

            $validator = Validator::make($payload, [
                'name' => 'required|unique:payment_breakdowns',
                'type' => 'required|in:percentage,fixed',
                'percentage_of' => 'nullable|required_if:type,percentage|in:basic_charge,total_amount',
                'amount' => 'required|numeric'
            ]);

            if($validator->fails()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            $response = $this->paymentBreakdownService::create($payload);
        }

        if($action == 'penalty') {

            $validator = Validator::make($payload, [
                'penalty.from.*'         => ['required', 'integer', 'min:1'],
                'penalty.to.*'           => ['required', 'regex:/^\*|\d+$/'],
                'penalty.amount_type.*'  => ['required', 'in:fixed,percentage'],
                'penalty.amount.*'       => ['required', 'numeric'],
            ], [
                'penalty.required'                       => '*At least one penalty entry is required.',

                'penalty.from.*.required'                => '*From is required.',
                'penalty.from.*.integer'                 => '*From must be a number.',
                'penalty.from.*.min'                     => '*From must be at least 1.',

                'penalty.to.*.required'                  => '*To is required.',
                'penalty.to.*.regex'                     => '*To must be a number or "*".',

                'penalty.amount_type.*.required'         => '*Amount type is required.',
                'penalty.amount_type.*.in'               => '*Amount type must be fixed or percentage.',

                'penalty.amount.*.required'              => '*Amount is required.',
                'penalty.amount.*.numeric'               => '*Amount must be numeric.',
            ]);

            $validator->after(function ($validator) use ($payload) {
                $ranges = [];

                $from = $payload['penalty']['from'] ?? [];
                $to = $payload['penalty']['to'] ?? [];

                foreach ($from as $i => $start) {
                    $start = (int) $start;
                    $endRaw = $to[$i] ?? '*';

                    $end = $endRaw === '*' ? PHP_INT_MAX : (int) $endRaw;

                    // Check for invalid range
                    if ($end !== PHP_INT_MAX && $end < $start) {
                        $validator->errors()->add("penalty.to.$i", '*To must be greater than or equal to From.');
                    }

                    // Check for overlaps
                    foreach ($ranges as $j => [$prevStart, $prevEnd]) {
                        if (
                            ($start <= $prevEnd && $end >= $prevStart)
                        ) {
                            $validator->errors()->add("penalty.from.$i", "*Range $start to infinite overlaps with another range.");
                            $validator->errors()->add("penalty.to.$i", "*Overlapping range.");
                            break;
                        }
                    }

                    $ranges[] = [$start, $end];
                }
            });

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            if($validator->fails()) {
                return redirect()->route('payment-breakdown.create', ['action' => $action])
                    ->withErrors($validator)
                    ->withInput();
            }

            $response = $this->paymentBreakdownService::create($payload);

        }

        if($action == 'service-fee') {

            $validator = Validator::make($payload, [
                'service_fee.property_type.*' => [
                    'required',
                    'exists:property_types,id',
                    function ($attribute, $value, $fail) use ($payload) {
                        preg_match('/\d+/', $attribute, $matches);
                        $index = isset($matches[0]) ? (int) $matches[0] : null;

                        if ($index === 1) {
                            $exists = DB::table('payment_service_fees')
                                ->where('property_id', $value)
                                ->exists();

                            if ($exists) {
                                $fail('* already exists');
                            }
                        }
                    },
                ],
                'service_fee.amount.*' => 'required|numeric',
            ], [
                'service_fee.property_type.*.required' => '* required',
                'service_fee.property_type.*.exists' => '* invalid property types',
                'service_fee.amount.*.required' => '* required',
                'service_fee.amount.*.numeric' => '* must be a number',
            ]);

            if($validator->fails()) {
                return redirect()->route('payment-breakdown.create', ['action' => $action])
                    ->withErrors($validator)
                    ->withInput();

            }

            $response = $this->paymentBreakdownService::create($payload);

        }

        if($action == 'discount') {

            $validator = Validator::make($payload, [
                'name' => 'required|unique:payment_discount',
                'type' => 'required|in:percentage,fixed',
                'percentage_of' => 'nullable|required_if:type,percentage|in:basic_charge,total_amount',
                'eligible' => 'required|in:pwd,senior,franchise',
                'amount' => 'required|numeric'
            ]);

            if($validator->fails()) {
                return redirect()->route('payment-breakdown.create', ['action' => $action])
                    ->withErrors($validator)
                    ->withInput();
            }
            $response = $this->paymentBreakdownService::create($payload);

        }

        if($action == 'ruling') {

            $validator = Validator::make($payload, [
                'due_date' => 'required|integer',
                'disconnection_date' => 'required|integer',
                'disconnection_rule' => 'required|integer',
                'snr_dc_rule' => 'required|integer'
            ]);

            if($validator->fails()) {
                return redirect()->route('payment-breakdown.index', ['view' => $action])
                    ->withErrors($validator)
                    ->withInput();
            }

            $response = $this->paymentBreakdownService::create($payload);
        }

        if ($response['status'] === 'success') {
            if($action == 'regular' || $action == 'discount') {
                 return redirect()->route('payment-breakdown.create', ['action' => $action])->with('alert', [
                    'status' => 'success',
                    'message' => $response['message']
                ]);
            }

            if($action == 'ruling') {
                return redirect()->route('payment-breakdown.index', ['view' => $action])->with('alert', [
                    'status' => 'success',
                    'message' => $response['message']
                ]);
            }

            return redirect()->route('payment-breakdown.create', ['action' => $action])
                ->withInput()
                ->with('alert', [
                    'status' => 'success',
                    'message' => $response['message']
                ]);
        } else {
            return redirect()->route('payment-breakdown.create', ['action' => $action])->withInput()->with('alert', [
                'status' => 'error',
                'message' => $response['message']
            ]);
        }
    }

    public function edit(int $id, Request $request) {
        $action = $request->action;

        if(!in_array($action, ['regular', 'penalty', 'service-fee', 'discount'])) {
            return redirect()->route('payment-breakdown.index');
        }

        if ($action === 'discount') {
        $data = $this->paymentBreakdownService::getDiscounts($id);
        } else {
            $data = $this->paymentBreakdownService::getData($id);
        }

        $property_types = $this->propertyService->getData() ?? [];

        return view('payment-breakdown.form', compact('action', 'property_types', 'data'));
    }


    public function update(Request $request, $id)
    {
        // Determine the action (discount, regular, penalty, service-fee)
        $action = $request->input('action');

        if ($action === 'discount') {
            // Validation
            $validated = $request->validate([
                'name' => "required|unique:payment_discount,name,{$id}",
                'eligible' => 'nullable|in:pwd,senior,franchise',
                'type' => 'required|in:fixed,percentage',
                'percentage_of' => 'nullable|in:basic_charge,total_amount',
                'amount' => 'required|numeric',
            ]);

            // Find discount
            $discount = PaymentDiscount::findOrFail($id);

            // Update values
            $discount->update([
                'name' => $validated['name'],
                'eligible' => $validated['eligible'] ?? null,
                'type' => $validated['type'],
                'percentage_of' => $validated['percentage_of'] ?? null,
                'amount' => $validated['amount'],
            ]);

            return redirect()
                ->route('payment-breakdown.index', ['view' => 'discount'])
                ->with('alert', [
                    'status' => 'success',
                    'message' => 'Discount updated successfully.'
                ]);
        }

        // Handle other actions (optional)
        return redirect()->back()->with('alert', [
            'status' => 'error',
            'message' => 'Action not supported.'
        ]);
    }


    public function destroy(Request $request, int $id) {

        $action = $request->input('action') ?? null;

        if(!in_array($action, ['regular', 'discount'])) {
            return response()->json([
                'status' => 'error',
                'message' => 'Unable to delete due to unknown action'
            ]);
        }

        $response = $this->paymentBreakdownService::delete($action, $id);

        if ($response['status'] === 'success') {

            return response()->json([
                'status' => 'success',
                'message' => $response['message']
            ]);

        } else {
            return response()->json([
                'status' => 'error',
                'message' => $response['message']
            ]);
        }

    }



    public function datatable($action, $query) {
        if($action == 'regular') {
            return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('amount', function($row) {
                if($row->type == 'percentage') {
                    return $row->amount . '%';
                } else {
                    return '₱' .  number_format($row->amount ?? 0, 2);
                }
            })
            ->addColumn('actions', function ($row) use ($action) {
                return '
                <div class="d-flex align-items-center gap-2">
                    <a href="' . route('payment-breakdown.edit', ['payment_breakdown' => $row->id, 'action' => $action]) . '"
                        class="btn btn-secondary text-white text-uppercase fw-bold"
                        id="update-btn" data-id="' . e($row->id) . '">
                        <i class="bx bx-edit-alt"></i>
                    </a>
                    <button class="btn btn-danger text-white text-uppercase fw-bold btn-delete" id="btn-delete" data-id="' . e($row->id) . '">
                        <i class="bx bx-trash"></i>
                    </button>
                </div>';
            })
            ->rawColumns(['status', 'actions'])
            ->make(true);
        }

        if($action == 'penalty') {
            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('amount', function($row) {
                    if($row->amount_type == 'percentage') {
                        return $row->amount . '%';
                    } else {
                        return '₱' .  number_format($row->amount ?? 0, 2);
                    }
                })
                ->editColumn('amount_type', function($row) {
                    $type = [
                        'fixed' => 'Fixed Amount',
                        'percentage' => 'Percentage Amount'
                    ];

                    $target = $row->amount_type;

                    return $type[$target] ?? 'N/A';

                })
                ->make(true);
        }

        if($action == 'service_fee') {
            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('property', function($row) {
                    return $row->property->name;
                })
                ->editColumn('amount', function($row) {
                    return '₱' .  number_format($row->amount ?? 0, 2);
                })
                ->make(true);
        }

        if($action == 'franchise') {
    return DataTables::of($query)
        ->addIndexColumn()
        ->editColumn('amount', function($row) {
            if($row->type == 'percentage') {
                return $row->amount . '%';
            } else {
                return '₱' .  number_format($row->amount ?? 0, 2);
            }
        })
        ->addColumn('actions', function ($row) use ($action) {
            return '
            <div class="d-flex align-items-center gap-2">
                <a href="' . route('payment-breakdown.edit', ['payment_breakdown' => $row->id, 'action' => $action]) . '"
                    class="btn btn-secondary text-white text-uppercase fw-bold"
                    id="update-btn" data-id="' . e($row->id) . '">
                    <i class="bx bx-edit-alt"></i>
                </a>
                <button class="btn btn-danger text-white text-uppercase fw-bold btn-delete" id="btn-delete" data-id="' . e($row->id) . '">
                    <i class="bx bx-trash"></i>
                </button>
            </div>';
        })
        ->rawColumns(['status', 'actions'])
        ->make(true);
}


        if($action == 'discount') {
            return DataTables::of($query)
                ->addIndexColumn()
                ->editColumn('name', function($row) {
                    return $row->name;
                })
                ->editColumn('amount', function($row) {
                    if($row->type == 'percentage') {
                        return $row->amount . '%';
                    } else {
                        return '₱' .  number_format($row->amount ?? 0, 2);
                    }
                })
                 ->editColumn('eligible', function($row) {

                    $eligible = [
                        'senior' => 'Senior Citizen',
                        'franchise' => 'Franchise Discount',
                        'pwd' => 'Person with disability'
                    ];

                    $index = $row->eligible;

                    $eligible = $eligible[$index] ?? 'N/A';

                    return $eligible;


                })
                ->addColumn('actions', function ($row) use ($action) {
                    return '
                    <div class="d-flex align-items-center gap-2">
                        <a href="' . route('payment-breakdown.edit', ['payment_breakdown' => $row->id, 'action' => $action]) . '"
                            class="btn btn-secondary text-white text-uppercase fw-bold"
                            id="update-btn" data-id="' . e($row->id) . '">
                            <i class="bx bx-edit-alt"></i>
                        </a>
                        <button class="btn btn-danger text-white text-uppercase fw-bold btn-delete" id="btn-delete" data-id="' . e($row->id) . '">
                            <i class="bx bx-trash"></i>
                        </button>
                    </div>';
                })
                ->rawColumns(['status', 'actions'])
                ->make(true);
        }
    }

}
