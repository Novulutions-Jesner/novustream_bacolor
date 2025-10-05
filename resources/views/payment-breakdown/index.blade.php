@extends('layouts.app')

@section('content')
    <main class="main">
        <div class="responsive-wrapper">
            <div class="main-header d-flex justify-content-between">
                <h1>Payment Breakdown</h1>
                <div class="d-flex align-items-center gap-2">
                    <div class="dropdown">
                        <a class="btn btn-primary px-5 py-3 dropdown-toggle text-uppercase" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            Add New
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{route('payment-breakdown.create', ['action' => 'regular'])}}">Regular Breakdown</a></li>
                            <li><a class="dropdown-item" href="{{route('payment-breakdown.create', ['action' => 'penalty'])}}">Penalty Breakdown</a></li>
                            <li><a class="dropdown-item" href="{{route('payment-breakdown.create', ['action' => 'discount'])}}">Discounts</a></li>
                            <li><a class="dropdown-item" href="{{route('payment-breakdown.create', ['action' => 'service-fee'])}}">Service Fee</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="inner-content mt-5 pb-5">

                <ul class="nav nav-pills mb-5" id="pills-tab" role="tablist">
                    @foreach(['regular' => 'Regular Breakdown', 'penalty' => 'Penalty Breakdown', 'discount' => 'Discounts', 'service-fee' => 'Service Fee', 'ruling' => 'Ruling'] as $key => $label)
                        <li class="nav-item" role="presentation">
                            <a
                                class="nav-link text-uppercase  {{ $view == $key ? 'active' : '' }}"
                                id="pills-{{ $key }}-tab"
                                href="{{ route('payment-breakdown.index', ['view' => $key]) }}"
                            >
                                {{ $label }}
                            </a>
                        </li>
                    @endforeach
                </ul>

                @if($view == 'regular')
                    <div class="card shadow">
                        <div class="card-header border-0 m-0 pt-3">
                            <h6 class="card-title mb-0 text-uppercase fw-bold">Regular Breakdown</h6>
                        </div>
                        <div class="card-body">
                            <table class="w-100 table table-bordered table-hover" id="regular-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Amount</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if($view == 'penalty')
                    <div class="card shadow">
                        <div class="card-header border-0 m-0 pt-3">
                            <h6 class="card-title mb-0 text-uppercase fw-bold">Penalty Breakdown</h6>
                        </div>
                        <div class="card-body">
                            <table class="w-100 table table-bordered table-hover" id="penalty-table">
                                <thead>
                                    <tr>
                                        <th>Due Days (From)</th>
                                        <th>Due Days (To)</th>
                                        <th>Amount Type</th>
                                        <th>Penalty Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if($view == 'discount')
                    <div class="card shadow">
                        <div class="card-header border-0 m-0 pt-3">
                            <h6 class="card-title mb-0 text-uppercase fw-bold">Discounts</h6>
                        </div>
                        <div class="card-body">
                            <table class="w-100 table table-bordered table-hover" id="discount-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Eligible</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if($view == 'service-fee')
                    <div class="card shadow">
                        <div class="card-header border-0 m-0 pt-3">
                            <h6 class="card-title mb-0 text-uppercase fw-bold">Service Fee</h6>
                        </div>
                        <div class="card-body">
                            <table class="w-100 table table-bordered table-hover" id="service-table">
                                <thead>
                                    <tr>
                                        <th>Property</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if($view == 'ruling')
                    <form action="{{ route('payment-breakdown.store', ['action' => 'ruling']) }}" method="POST">
                        @method('POST')
                        @csrf
                        <div class="row">
                            <div class="col-12 col-md-4 mb-3">
                                <div class="card shadow" style="min-height: 280px">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0 text-uppercase fw-bold">Due Date</h5>
                                    </div>
                                    <div class="card-body position-relative mb-2">
                                        <p class="card-text text-uppercase">Set the due date to a day after the billing date.</p>
                                        <div style="position: absolute; left: 0; bottom: 20px; width: 100%; padding: 0 20px 0 20px;">
                                            <label class="mb-2">No. of days</label>
                                            <input
                                                type="number"
                                                name="due_date"
                                                id="due_date"
                                                class="form-control"
                                                value="{{ old('due_date', $ruling->due_date ?? '') }}">
                                            @error('due_date')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4 mb-3">
                                <div class="card shadow" style="min-height: 280px">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0 text-uppercase fw-bold">Disconnection Date</h5>
                                    </div>
                                    <div class="card-body position-relative mb-2">
                                        <p class="card-text text-uppercase">Schedule the disconnection date after the due date and make sure to notify the concessionaire about the pending status.</p>
                                        <div style="position: absolute; left: 0; bottom: 20px; width: 100%; padding: 0 20px 0 20px;">
                                            <label class="mb-2">No. of days</label>
                                            <input
                                                type="number"
                                                name="disconnection_date"
                                                id="disconnection_date"
                                                class="w-100 form-control"
                                                value="{{ old('disconnection_date', $ruling->disconnection_date ?? '') }}">
                                            @error('disconnection_date')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4 mb-3">
                                <div class="card shadow" style="min-height: 280px">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0 text-uppercase fw-bold">Disconnection Rule</h5>
                                    </div>
                                    <div class="card-body position-relative mb-2">
                                        <p class="card-text text-uppercase">Ensure full disconnection is carried out for all applicable accounts.</p>
                                        <div style="position: absolute; left: 0; bottom: 20px; width: 100%; padding: 0 20px 0 20px;">
                                            <label class="mb-2">No. of unpaid months</label>
                                            <select name="disconnection_rule" id="disconnection_rule" class="form-select">
                                                <option value=""> - CHOOSE - </option>
                                                @for ($i = 1; $i <= 6; $i++)
                                                    <option value="{{ $i }}"
                                                        {{ (old('disconnection_rule', $ruling->disconnection_rule ?? '') == $i) ? 'selected' : '' }}>
                                                        {{ $i }} Month{{ $i > 1 ? 's' : '' }}
                                                    </option>
                                                @endfor
                                            </select>
                                            @error('disconnection_rule')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-4 mb-3">
                                <div class="card shadow" style="min-height: 280px">
                                    <div class="card-header">
                                        <h5 class="card-title mb-0 text-uppercase fw-bold">Senior Discount</h5>
                                    </div>
                                    <div class="card-body position-relative mb-2">
                                        <p class="card-text text-uppercase">Implement the senior discount and determine the maximum cubic meter allowed to qualify</p>
                                        <div style="position: absolute; left: 0; bottom: 20px; width: 100%; padding: 0 20px 0 20px;">
                                            <label class="mb-2">Maximum Consumption</label>
                                            <input
                                                type="number"
                                                name="snr_dc_rule"
                                                id="snr_dc_rule"
                                                class="w-100 form-control"
                                                value="{{ old('snr_dc_rule', $ruling->snr_dc_rule ?? '') }}">
                                            @error('snr_dc_rule')
                                                <div class="invalid-feedback d-block">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end mt-4 pb-5">
                                <button type="submit" class="btn btn-primary px-5 py-3 text-uppercase fw-bold px-4">Save</button>
                            </div>
                        </div>
                    </form>
                @endif
            </div>
        </div>
    </main>
@endsection

@section('script')
<script>
    $(function() {

         @if (session('alert'))
            setTimeout(() => {
                let alertData = @json(session('alert'));
                alert(alertData.status, alertData.message);
            }, 100);
        @endif

        const regular = '{{ route('payment-breakdown.index', ['action' => 'regular']) }}';
        const penalty = '{{ route('payment-breakdown.index', ['action' => 'penalty']) }}';
        const service = '{{ route('payment-breakdown.index', ['action' => 'service-fee']) }}';
        const discount = '{{ route('payment-breakdown.index', ['action' => 'discount']) }}';

        const regularTable = $('#regular-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: regular,
            columns: [
                { data: 'name', name: 'name' },
                { data: 'amount', name: 'amount' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
            responsive: true,
            order: [[0, 'desc']],
            scrollX: true
        });

        const penaltyTable = $('#penalty-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: penalty,
            columns: [
                { data: 'due_from', name: 'due_from' },
                { data: 'due_to', name: 'due_to' },
                { data: 'amount_type', name: 'amount_type' },
                { data: 'amount', name: 'amount' },
            ],
            responsive: true,
            order: [[0, 'desc']],
            scrollX: true
        });

        const serviceTable = $('#service-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: service,
            columns: [
                { data: 'property', name: 'property' },
                { data: 'amount', name: 'amount' },
            ],
            responsive: true,
            order: [[0, 'desc']],
            scrollX: true
        });

        const discountTable = $('#discount-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: discount,
            columns: [
                { data: 'name', name: 'name' },
                { data: 'eligible', name: 'eligible' },
                { data: 'amount', name: 'amount' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false }
            ],
            responsive: true,
            order: [[0, 'desc']],
            scrollX: true
        });

        $(document).on('click', '.btn-delete', function() {

            const view = new URLSearchParams(window.location.search).get('view');
            const id = $(this).data('id');
            const token = '{{ csrf_token() }}';

            const url = '{{ route("payment-breakdown.destroy", ["action" => "__VIEW__", "payment_breakdown" => "__ID__"]) }}'
                .replace('__VIEW__', view)
                .replace('__ID__', id);

            const tableElement = $(this).closest('table');
            let targetTable = null;

            if (tableElement.is('#regular-table')) targetTable = regularTable;
            else if (tableElement.is('#penalty-table')) targetTable = penaltyTable;
            else if (tableElement.is('#service-table')) targetTable = serviceTable;
            else if (tableElement.is('#discount-table')) targetTable = discountTable;

            if (targetTable) {
                remove(targetTable, url, token);
            } else {
                console.error('Could not identify target DataTable.');
            }
        });
    });

    function remove(tableInstance, url, token) {
        if (confirm('Are you sure you want to delete this item?')) {
            $.ajax({
                url: url,
                type: 'DELETE',
                data: {
                    _token: token
                },
                success: function(response) {
                    tableInstance.ajax.reload(null, false);
                    alert('Item deleted successfully!');
                },
                error: function(xhr) {
                    alert('Failed to delete item.');
                }
            });
        }
    }
</script>
@endsection
