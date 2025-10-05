@extends('layouts.app')

@section('content')
    <main class="main">
        <div class="responsive-wrapper">
            <div class="main-header d-flex justify-content-between">
                <h1>Payment Breakdown</h1>
                <div class="d-flex align-items-center gap-2">
                    <a href="{{ route('payment-breakdown.index', ['view' => request('action')]) }}"
                        class="btn btn-outline-primary px-5 py-3 text-uppercase">
                        Go Back
                    </a>
                </div>
            </div>
            <div class="inner-content mt-5">
                @if($action == 'regular')
                    <form action="{{ isset($data) ? route('payment-breakdown.update', $data->id) : route('payment-breakdown.store', ['action' => 'regular']) }}" method="POST">
                        @csrf
                        @if(isset($data))
                            @method('PUT')
                        @endif
                        <div class="row">
                            <div class="col-12 col-md-12 mb-3">
                                <div class="card shadow border-0 p-2">
                                    <div class="card-header border-0 bg-transparent">
                                        <div class="text-uppercase fw-bold">Information</div>
                                    </div>
                                    <div class="card-body">

                                        <div class="row mb-3">
                                            <div class="col-md-12 mb-3">
                                                <label for="name" class="form-label">Name</label>
                                                <input type="text" class="form-control text-uppercase @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $data->name ?? '') }}">
                                                @error('name')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="type" class="form-label">Amount Type</label>
                                                <select name="type" id="type" class="form-select text-uppercase @error('type') is-invalid @enderror">
                                                    <option value="fixed" {{ old('type', $data->type ?? '') == 'fixed' ? 'selected' : '' }} selected>Fixed Amount</option>
                                                    <option value="percentage" {{ old('type', $data->type ?? '') == 'percentage' ? 'selected' : '' }}>Percentage Amount</option>
                                                </select>
                                                @error('type')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-6 mb-3 percentage-of-field">
                                                <label for="percentage_of" class="form-label">Percentage Amount Of</label>
                                                <select name="percentage_of" id="percentage_of" class="form-select text-uppercase">
                                                    <option value=""> - CHOOSE - </option>
                                                    <option value="basic_charge" {{ old('percentage_of', $data->percentage_of ?? '') == 'basic_charge' ? 'selected' : '' }}>Basic Charge</option>
                                                    <option value="total_amount" {{ old('percentage_of', $data->percentage_of ?? '') == 'total_amount' ? 'selected' : '' }}>Total Amount</option>
                                                </select>
                                                @error('percentage_of')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-6 mb-3">
                                                <label for="amount" class="form-label">Amount / Percentage</label>
                                                <input type="text" class="form-control text-uppercase @error('amount') is-invalid @enderror" id="amount" name="amount" value="{{ old('amount', $data->amount ?? '') }}">
                                                @error('amount')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end my-5">
                            <button type="submit" class="btn btn-primary px-5 py-3 text-uppercase fw-bold">Submit</button>
                        </div>
                    </form>
                @endif

                @if($action == 'penalty')
                    <form action="{{ route('payment-breakdown.store', ['action' => 'penalty']) }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-12 col-md-12 mb-3">
                                <div class="card shadow border-0 p-2">
                                    <div class="card-header border-0 bg-transparent">
                                        <div class="text-uppercase fw-bold">Information</div>
                                    </div>
                                    <div class="card-body">
                                        <div id="breakdown-rows">
                                            @php
                                                $fromValues = old('penalty.from', isset($penalty) ? collect($penalty)->pluck('due_from')->toArray() : []);
                                                $toValues = old('penalty.to', isset($penalty) ? collect($penalty)->pluck('due_to')->toArray() : []);
                                                $amountValues = old('penalty.amount', isset($penalty) ? collect($penalty)->pluck('amount')->toArray() : []);
                                                $amountTypeValues = old('penalty.amount_type', isset($penalty) ? collect($penalty)->pluck('amount_type')->toArray() : []);
                                                $percentageOfValues = old('penalty.percentage_of', isset($penalty) ? collect($penalty)->pluck('percentage_of')->toArray() : []);
                                            @endphp

                                            @foreach($fromValues as $index => $from)
                                                <div class="mb-2 d-flex align-items-start gap-3 w-100 breakdown-rows">
                                                    <div class="row w-100">

                                                        <!-- Days Due (From) -->
                                                        <div class="col-12 col-md-3 mb-3">
                                                            <label class="form-label">Days Due (From)</label>
                                                            <input type="text" name="penalty[from][]" class="form-control text-uppercase @error("penalty.from.$index") is-invalid @enderror" value="{{ $from }}">
                                                            @error("penalty.from.$index")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                        </div>

                                                        <!-- Days Due (To) -->
                                                        <div class="col-12 col-md-3 mb-3">
                                                            <label class="form-label">Days Due (To)</label>
                                                            <input type="text" name="penalty[to][]" class="form-control text-uppercase @error("penalty.to.$index") is-invalid @enderror" value="{{ $toValues[$index] ?? '' }}">
                                                            @error("penalty.to.$index")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                        </div>

                                                        <!-- Amount Type -->
                                                        <div class="col-12 col-md-3 mb-3">
                                                            <label class="form-label">Amount Type</label>
                                                            <select name="penalty[amount_type][]" class="form-select text-uppercase amount-type-select @error("penalty.to.$index") is-invalid @enderror" data-index="{{ $index }}">
                                                                <option value=""> - CHOOSE -</option>
                                                                <option value="fixed" {{ ($amountTypeValues[$index] ?? '') === 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                                                                <option value="percentage" {{ ($amountTypeValues[$index] ?? '') === 'percentage' ? 'selected' : '' }}>Percentage</option>
                                                            </select>
                                                            @error("penalty.amount_type.$index")<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                                                        </div>

                                                        <!-- Amount -->
                                                        <div class="col-12 col-md-3 mb-3">
                                                            <label class="form-label">Amount</label>
                                                            <input type="text" name="penalty[amount][]" class="form-control text-uppercase @error("penalty.amount.$index") is-invalid @enderror" value="{{ $amountValues[$index] ?? '' }}">
                                                            @error("penalty.amount.$index")<div class="invalid-feedback">{{ $message }}</div>@enderror
                                                        </div>
                                                    </div>

                                                    <!-- Remove Button -->
                                                    <div class="mt-2">
                                                        <button type="button" class="btn btn-danger mt-3 remove-row-breakdown">
                                                            <i class='bx bx-x'></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="button" id="add-row-breakdown" class="btn btn-dark mt-3 text-uppercase px-4">Add Row</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end my-5">
                            <button type="submit" class="btn btn-primary px-5 py-3 text-uppercase fw-bold">Submit</button>
                        </div>
                    </form>
                @endif

                @if($action == 'service-fee')
                    <form action="{{ route('payment-breakdown.store', ['action' => 'service-fee']) }}" method="POST">
                        @csrf
                        <div class="row">
                            <div class="col-12 col-md-12 mb-3">
                                <div class="card shadow border-0 p-2">
                                    <div class="card-header border-0 bg-transparent">
                                        <div class="text-uppercase fw-bold">Information</div>
                                    </div>
                                    <div class="card-body">
                                        <div id="service-rows">
                                            @if(old('service_fee.property_type') || isset($service_fee))
                                                @php
                                                    $propertyTypes = old('service_fee.property_type', isset($service_fee) ? collect($service_fee)->pluck('property_id')->toArray() : []);
                                                    $amountValues = old('service_fee.amount', isset($service_fee) ? collect($service_fee)->pluck('amount')->toArray() : []);
                                                @endphp
                                                @foreach($propertyTypes as $index => $propertyType)
                                                    <div class="mb-2 d-flex align-items-start gap-3 w-100 service-rows">
                                                        <div class="row w-100">
                                                            <!-- Property Type -->
                                                            <div class="col-12 col-md-6 mb-3">
                                                                <label class="form-label">Property Type</label>
                                                                <select name="service_fee[property_type][]" class="form-select text-uppercase @error("service_fee.property_type.$index") is-invalid @enderror">
                                                                    <option value=""> - CHOOSE - </option>
                                                                    @foreach($property_types as $type)
                                                                        <option value="{{ $type->id }}" {{ old("service_fee.property_type.$index", $propertyType) == $type->id ? 'selected' : '' }}>
                                                                            {{ $type->name }}
                                                                        </option>
                                                                    @endforeach
                                                                </select>
                                                                @error("service_fee.property_type.$index")
                                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                                @enderror
                                                            </div>

                                                            <!-- Amount -->
                                                            <div class="col-12 col-md-6 mb-3">
                                                                <label class="form-label">Amount</label>
                                                                <input type="text" name="service_fee[amount][]" class="form-control text-uppercase @error("service_fee.amount.$index") is-invalid @enderror"
                                                                    value="{{ old("service_fee.amount.$index", $amountValues[$index] ?? '') }}">
                                                                @error("service_fee.amount.$index")
                                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                                @enderror
                                                            </div>
                                                        </div>

                                                        <!-- Remove Button -->
                                                        <div class="mt-2">
                                                            <button type="button" class="btn btn-danger mt-3 remove-row-service">
                                                                <i class='bx bx-x'></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            @endif
                                        </div>
                                        <div class="d-flex justify-content-end">
                                            <button type="button" id="add-row-service" class="btn btn-dark mt-3 text-uppercase px-4">Add Row</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end my-5">
                            <button type="submit" class="btn btn-primary px-5 py-3 text-uppercase fw-bold">Submit</button>
                        </div>
                    </form>
                @endif

                @if($action == 'discount')
                    <form action="{{ isset($data) ? route('payment-breakdown.update', $data->id) : route('payment-breakdown.store', ['action' => 'discount']) }}" method="POST">
                        @csrf
                        @if(isset($data))
                            @method('PUT')
                        @endif

                        <input type="hidden" name="action" value="{{ $action }}">
                        <div class="row">
                            <div class="col-12 col-md-12 mb-3">
                                <div class="card shadow border-0 p-2">
                                    <div class="card-header border-0 bg-transparent">
                                        <div class="text-uppercase fw-bold">Information</div>
                                    </div>
                                    <div class="card-body">

                                        <div class="row mb-3">
                                            <div class="col-md-12 mb-3">
                                                <label for="name" class="form-label">Name</label>
                                                <input type="text" class="form-control text-uppercase @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $data->name ?? '') }}">
                                                @error('name')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="eligible" class="form-label">Amount Type</label>
                                                <select name="eligible" id="eligible" class="form-select text-uppercase @error('eligible') is-invalid @enderror">
                                                    <option value=""> - CHOOSE - </option>
                                                    @foreach(['senior' => 'Senior Citizen', 'franchise' => 'Franchise Discount', 'pwd' => 'Person With Disability'] as $key => $label)
                                                        <option value="{{ $key }}" {{ old('eligible', $data->eligible ?? '') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                                    @endforeach
                                                </select>
                                                @error('eligible')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="type" class="form-label">Eligible</label>
                                                <select name="type" id="type" class="form-select text-uppercase @error('type') is-invalid @enderror">
                                                    <option value="fixed" {{ old('type', $data->type ?? '') == 'fixed' ? 'selected' : '' }} selected>Fixed Amount</option>
                                                    <option value="percentage" {{ old('type', $data->type ?? '') == 'percentage' ? 'selected' : '' }}>Percentage Amount</option>
                                                </select>
                                                @error('type')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-4 mb-3 percentage-of-field">
                                                <label for="percentage_of" class="form-label">Percentage Amount Of</label>
                                                <select name="percentage_of" id="percentage_of" class="form-select text-uppercase">
                                                    <option value=""> - CHOOSE - </option>
                                                    <option value="basic_charge" {{ old('percentage_of', $data->percentage_of ?? '') == 'basic_charge' ? 'selected' : '' }}>Basic Charge</option>
                                                    <option value="total_amount" {{ old('percentage_of', $data->percentage_of ?? '') == 'total_amount' ? 'selected' : '' }}>Total Amount</option>
                                                </select>
                                                @error('percentage_of')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                            <div class="col-md-4 mb-3">
                                                <label for="amount" class="form-label">Amount / Percentage</label>
                                                <input type="text" class="form-control text-uppercase @error('amount') is-invalid @enderror" id="amount" name="amount" value="{{ old('amount', $data->amount ?? '') }}">
                                                @error('amount')
                                                    <div class="invalid-feedback">{{ $message }}</div>
                                                @enderror
                                            </div>

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="d-flex justify-content-end my-5">
                            <button type="submit" class="btn btn-primary px-5 py-3 text-uppercase fw-bold">Submit</button>
                        </div>
                    </form>
                @endif

            </div>
        </div>
    </main>
@endsection

@section('script')
    <script>
        $(function () {

            @if (session('alert'))
                setTimeout(() => {
                    let alertData = @json(session('alert'));
                    alert(alertData.status, alertData.message);
                }, 100);
            @endif

            $('#add-row-breakdown').click(function () {
                let row = `
                    <div class="mb-2 d-flex align-items-start gap-3 w-100 breakdown-rows">
                        <div class="row w-100">
                            <!-- Days Due (From) -->
                            <div class="col-12 col-md-3 mb-3">
                                <label class="form-label">Days Due (From)</label>
                                <input type="text" name="penalty[from][]" class="form-control text-uppercase">
                            </div>

                            <!-- Days Due (To) -->
                            <div class="col-12 col-md-3 mb-3">
                                <label class="form-label">Days Due (To)</label>
                                <input type="text" name="penalty[to][]" class="form-control text-uppercase">
                            </div>

                            <!-- Amount Type -->
                            <div class="col-12 col-md-3 mb-3">
                                <label class="form-label">Amount Type</label>
                                <select name="penalty[amount_type][]" class="form-select text-uppercase amount-type-select">
                                    <option value=""> - CHOOSE -</option>
                                    <option value="fixed">Fixed Amount</option>
                                    <option value="percentage">Percentage</option>
                                </select>
                            </div>

                            <!-- Amount -->
                            <div class="col-12 col-md-3 mb-3">
                                <label class="form-label">Amount</label>
                                <input type="text" name="penalty[amount][]" class="form-control text-uppercase">
                            </div>
                        </div>
                    </div>
                `;

                $('#breakdown-rows').append(row);
            });

            $('#add-row-service').click(function () {
                let propertyTypes = @json($property_types);
                let options = `<option value=""> - CHOOSE - </option>`;
                propertyTypes.forEach(type => {
                    options += `<option value="${type.id}">${type.name}</option>`;
                });

                let row = `
                    <div class="mb-2 d-flex align-items-start gap-3 w-100 service-rows">
                        <div class="row w-100">
                            <!-- Property Type -->
                            <div class="col-12 col-md-6 mb-3">
                                <label for="property_type" class="form-label">Property Type</label>
                                <select name="service_fee[property_type][]" class="form-select text-uppercase">
                                    ${options}
                                </select>
                            </div>

                            <!-- Amount -->
                            <div class="col-12 col-md-6 mb-3">
                                <label for="amount" class="form-label">Amount</label>
                                <input type="text" name="service_fee[amount][]" class="form-control text-uppercase">
                            </div>
                        </div>

                        <!-- Remove Button -->
                        <div class="mt-2">
                            <button type="button" class="btn btn-danger mt-3 remove-row-service">
                                <i class='bx bx-x'></i>
                            </button>
                        </div>
                    </div>
                `;

                $('#service-rows').append(row);
            });


            $(document).on('click', '.remove-row-breakdown', function () {
                $(this).closest('.breakdown-rows').remove();
            });

            $(document).on('click', '.remove-row-service', function () {
                console.log(123);
                $(this).closest('.service-rows').remove();
            });

            function toggleFields() {
                const type = $('#type').val();
                if (type === 'percentage') {
                    $('.percentage-of-field').show();
                    $('#amount').attr('placeholder', 'ex. 0.12');
                } else {
                    $('.percentage-of-field').hide();
                    $('#amount').attr('placeholder', 'Fixed Amount');
                }
            }

            if ("{{ old('type', $data->type ?? '') }}" === "percentage") {
                $('.percentage-of-field').show();
            } else {
                $('.percentage-of-field').hide();
            }

            $('#type').on('change', function () {
                toggleFields();
            });

            toggleFields();
        });
    </script>
@endsection
