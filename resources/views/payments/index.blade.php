@extends('layouts.app')

@section('content')
    <main class="main">
        <div class="responsive-wrapper">
            <div class="main-header d-flex justify-content-between">
                <h1>{{$filter}} Bills</h1>
                <div class="d-flex align-items-center gap-3">
                    <a href="{{ route('previous-billing.upload') }}"
                        class="btn btn-outline-primary px-5 py-3 text-uppercase">
                         Upload Billing
                     </a>
                    <a href="{{ route('payments.index', ['filter' => $filter === 'paid' ? 'unpaid' : 'paid']) }}"
                        class="btn btn-primary px-5 py-3 text-uppercase">
                         View {{ $filter === 'paid' ? 'Unpaid' : 'Paid' }}
                     </a>
                </div>
            </div>
            <div class="inner-content mt-5 pb-5 mb-5">
                <div class="row mb-4">
                    <div class="col-12 col-md-1">
                        <label class="mb-1">Show Entries</label>
                        <select name="entries" id="entries" class="form-select text-uppercase dropdown-toggle">
                            @foreach([10, 25, 50, 100, 200, 250, 350, 400, 450, 500] as $entry)
                                <option value="{{ $entry }}" {{ $entries == $entry ? 'selected' : '' }}>
                                    {{ $entry }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-2 mb-3">
                        <label class="mb-1">Filter</label>
                        <select name="filter" id="filter" class="form-select text-uppercase dropdown-toggle">
                            <option value="unpaid" {{$filter == 'unpaid' ? 'selected' : ''}}>UnPaid</option>
                            <option value="paid" {{$filter == 'paid' ? 'selected' : ''}}>Paid</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-3 mb-3">
                        <label class="mb-1">Zone</label>
                        <select name="zone_no" id="zone_no" class="form-select text-uppercase dropdown-toggle">
                            <option value="all">All Zones</option>
                            @forelse($zones as $targetedZone)
                                <option value="{{$targetedZone->zone}}" {{$targetedZone->zone == $zone ? 'selected' : ''}}> {{$targetedZone->zone . ' - ' . $targetedZone->area}} </option>
                            @empty
                                <option value="">No Zones Available</option>
                            @endforelse
                        </select>
                    </div>
                    <div class="col-12 col-md-3 mb-3">
                        <label class="mb-1">Reading Month</label>
                        <input type="month" name="month" id="date" class="form-control" value="{{$date}}">
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="mb-1">Search <span class="text-muted ms-1">[account no]</span></label>
                        <div class="position-relative">
                            <input
                                type="text"
                                name="search"
                                id="search"
                                class="form-control pe-5"
                                value="{{ $toSearch }}"
                                placeholder=""
                            >

                            @if(!empty($toSearch))
                                <button
                                    type="button"
                                    id="clear-search"
                                    class="btn position-absolute top-50 end-0 translate-middle-y me-2 p-0 text-muted"
                                    style="border: none; background: none; font-size: 1.2rem;"
                                    aria-label="Clear search"
                                >
                                    &times;
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped w-100 mt-4">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Account No</th>
                                <th>Zone</th>
                                <th>Billing Period</th>
                                <th>Reading Date</th>
                                <th>Bill Date</th>
                                <th>Amount</th>
                                <th>Due Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($data as $index => $row)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $row->reading->account_no ?? 'N/A' }}</td>
                                    <td>{{ $row->reading->zone ?? 'N/A' }}</td>
                                    <td>
                                        @if ($row->bill_period_from && $row->bill_period_to)
                                            {{ \Carbon\Carbon::parse($row->bill_period_from)->format('M d, Y') }}
                                            TO
                                            {{ \Carbon\Carbon::parse($row->bill_period_to)->format('M d, Y') }}
                                        @else
                                            N/A
                                        @endif
                                    </td>
                                    <td>
                                        {{ !empty($row->bill_period_to)
                                            ? \Carbon\Carbon::parse($row->created_at)->format('M d, Y')
                                            : 'N/A' }}
                                    </td>
                                    <td>
                                        {{ !empty($row->bill_period_to)
                                            ? \Carbon\Carbon::parse($row->bill_period_to)->format('M d, Y')
                                            : 'N/A' }}
                                    </td>
                                    <td>₱{{ number_format((float)($row->amount ?? 0), 2) }}</td>
                                    <td>
                                        {{ !empty($row->due_date)
                                            ? \Carbon\Carbon::parse($row->due_date)->format('M d, Y')
                                            : 'N/A' }}
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            @if (!$row->isPaid)
                                                <a href="{{ route('payments.pay', ['reference_no' => $row->reference_no]) }}"
                                                class="btn btn-primary text-white text-uppercase fw-bold">
                                                    <i class="bx bx-credit-card-alt"></i>
                                                </a>
                                            @else
                                                <a target="_blank" href="{{ route('reading.show', $row->reference_no) }}"
                                                class="btn btn-primary text-white text-uppercase fw-bold"
                                                id="show-btn" data-id="{{ $row->id }}">
                                                    <i class="bx bx-receipt"></i>
                                                </a>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                            <tr>
                                <td colspan="12">
                                    <div class="text-uppercase text-center">No Data Found</div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="w-100 mt-4">
                    {{ $data->links() }}
                </div>
            </div>
        </div>
    </main>
@endsection

@section('script')
<script>
    $(function () {
        function updateUrl() {
            const params = new URLSearchParams(window.location.search);

            ['search', 'entries', 'filter', 'zone_no', 'date'].forEach(id => {
                const val = $('#' + id).val();
                const key = id === 'zone_no' ? 'zone' : id;

                val ? params.set(key, val) : params.delete(key);
            });

            window.location.href = window.location.pathname + '?' + params.toString();
        }

        $('#search, #entries, #filter, #zone_no, #date').on('change', updateUrl);

        $('#clear-search').on('click', function () {
            $('#search').val('');
            updateUrl();
        });
    });
</script>
@endsection
