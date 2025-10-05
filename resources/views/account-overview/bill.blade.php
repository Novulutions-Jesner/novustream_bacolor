@extends('layouts.app')

@section('content')
    <main class="main">
        <div class="responsive-wrapper">
            <div class="main-header d-flex justify-content-between">
                <h1>
                     @php
                        $title = [
                            'accounts' => 'Account Properties',
                            'bills' => "Billing and Payments <br> | " . ($account_no ?? ''),
                            'receipt' => ($reference_no ?? ''),
                        ];
                    @endphp

                    {!! $title[$viewer] ?? '' !!}
                </h1>

                 @if($viewer == 'bills')
                    <a href="{{ route('account-overview.bills') }}"
                        class="btn btn-outline-primary px-5 py-3 text-uppercase">
                        Go Back
                    </a>
                @endif

                @if($viewer == 'receipt')
                    <div class="print-controls d-md-flex justify-content-center text-center text-center gap-4 mt-5 mb-3">
                        @php
                            $backUrl = route('account-overview.bills', ['account_no' => $data['client']['account_no'], 'view' => 'unpaid']);
                        @endphp

                        <a href="{{ $backUrl }}"
                            style="border: 1px solid #32667e; padding: 12px 40px; text-transform: uppercase; display: flex; align-items: center; gap: 8px; text-decoration: none; color: #32667e; background-color: transparent; border-radius: 5px; font-weight: bold;"
                            class="btn btn-outline-primary px-5 py-3 text-uppercase">
                            <i style="font-size: 18px;" class='bx bx-left-arrow-alt'></i> Go Back
                        </a>

                        <button
                            class="download-js btn btn-primary px-5 py-3 text-uppercase"
                            data-target="#bill"
                            data-filename="{{$data['current_bill']['reference_no']}}"
                            style="background-color: #32667e; color: white; padding: 12px 40px; text-transform: uppercase; display: flex; align-items: center; gap: 8px; border: none; border-radius: 5px; font-weight: bold; cursor: pointer;">
                            <i style="font-size: 18px;" class='bx bxs-download'></i> Download
                        </button>

                    </div>
                @endif
            </div>
            @if($viewer == 'accounts')
                <div class="inner-content mt-5 pb-5">
                    <table class="w-100 table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Account No.</th>
                                <th>Meter No</th>
                                <th>Address</th>
                                <th>Property Type</th>
                                <th>Date Connected</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>

                @section('script')
                    <script>
                        $(function() {
                            const url = '{{ route(Route::currentRouteName()) }}';

                            let table = $('table').DataTable({
                                processing: true,
                                serverSide: true,
                                ajax: url,
                                columns: [
                                    { data: 'id', name: 'id' },
                                    { data: 'account_no', name: 'account_no' },
                                    { data: 'meter_no', name: 'meter_no' },
                                    { data: 'address', name: 'address' },
                                    { data: 'property_type', name: 'property_type' },
                                    { data: 'date_connected', name: 'date_connected' },
                                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                                ],
                                responsive: true,
                                order: [[0, 'desc']],
                                scrollX: true
                            });

                        });
                    </script>
                @endsection
            @endif

            @if($viewer == 'bills')
                <div class="inner-content mt-5 pb-5">
                    <ul class="nav nav-pills mb-5" id="pills-tab" role="tablist">
                        @foreach(['unpaid' => 'Unpaid', 'paid' => 'Paid'] as $key => $label)
                            <li class="nav-item" role="presentation">
                                <a
                                    class="nav-link text-uppercase  {{ $view == $key ? 'active' : '' }}"
                                    id="pills-{{ $key }}-tab"
                                    href="{{ route('account-overview.bills', ['account_no' => $account_no, 'view' => $key]) }}"
                                >
                                    {{ $label }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                    <table class="w-100 table table-bordered table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Billing Period</th>
                                <th>Bill Date</th>
                                <th>Amount</th>
                                <th>Due Date</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
                @section('script')
                    <script>
                        $(function() {
                            const url = '{{ route(Route::currentRouteName()) }}';

                            let table = $('table').DataTable({
                                processing: true,
                                serverSide: true,
                                ajax: {
                                    url: url,
                                    data: {
                                        account_no: '{{ $account_no }}',
                                        view: '{{ $view }}'
                                    }
                                },
                                columns: [
                                    { data: 'id', name: 'id' },
                                    { data: 'billing_period', name: 'billing_period' },
                                    { data: 'bill_date', name: 'bill_date' },
                                    { data: 'amount', name: 'amount' },
                                    { data: 'due_date', name: 'due_date' },
                                    { data: 'status', name: 'status' },
                                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                                ],
                                responsive: true,
                                order: [[0, 'desc']],
                                scrollX: true
                            });
                        });
                    </script>
                @endsection
            @endif

            @if($viewer == 'receipt')
                <div style="padding-bottom: 50px; margin-top: 50px">
                    <div id="bill" style="margin-top: 30px">
                        <div class="bill-container">
                            <div style="position: relative; width: 100%; max-width: 450px; margin: 0 auto; padding: 25px; background: white; border-radius: 5px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);">
                                @if($data['current_bill']['isPaid'] == true)
                                    <div class="isPaid" style="padding: 10px 30px 10px 30px; position: absolute; right: -10px; top: 4px; text-transform: uppercase; color: red; letter-spacing: 3px; font-size: 12px; font-weight: 600">
                                        PAID
                                    </div>
                                @endif
                                @php
                                    $logoPath = public_path('images/client.png');

                                    $base64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
                                @endphp

                                <div style="text-align: center; margin-top: 18px; margin-bottom: 10px; padding-bottom: 10px; display: flex; justify-content: center; align-items: center; gap: 15px;">
                                    <div>
                                        <img src="{{ asset('images/client.png')}}"
                                            style="width: 90px; margin: 0 auto 10px auto"
                                            alt="logo" class="web-logo">
                                    </div>
                                    <div style="width: fit-content;">
                                        <p style="font-size: 11px; text-transform: uppercase; margin: 0; font-weight: 600">Republic of the Philippines</p>
                                        <p style="font-size: 15px; text-transform: uppercase; margin: 0; text-transform: uppercase; font-weight: 600">Bacolor Water District</p>
                                        <p style="font-size: 12px; text-transform: uppercase; margin: 3px 0 0 0;">Sta. Ines, Bacolor, Pampanga</p>
                                        <p style="font-size: 12px; text-transform: uppercase; margin: 0;">Tel No. (045) 900- 2911</p>
                                        <p style="font-size: 12px; text-transform: uppercase; margin: 0;">Cell No. 09190644815</p>
                                        <p style="font-size: 12px; text-transform: uppercase; margin: 0;">TIN 003 878 306 000 Non VAT</p>
                                    </div>
                                </div>
                                <div style="text-align:center; text-transform: uppercase; font-size: 16px; margin: 10px 0 10px 0;">
                                    <p style="font-size: 22px; text-transform: uppercase; margin: 0; text-transform: uppercase; font-weight: 600">Statement of Account</p>
                                </div>
                                <div style="width: 100%; height: 1px; margin: 10px 0 10px 0; border-bottom: 1px dashed black;"></div>
                                <div>
                                    <div style="font-size: 10px; text-transform: uppercase; display: flex; flex-direction: column; gap: 1px;">
                                        <div class="oversized" style="margin: 4px 0 0 0; display: flex; align-items: center;">
                                            <div style="font-size: 20px; font-weight: 600">Account No.</div>
                                            <div style="font-size: 20px; font-weight: 600">{{$data['client']['account_no'] ?? ''}}</div>
                                        </div>
                                        <div class="oversized" style="margin: 4px 0 0 0; display: flex; align-items: center;">
                                            <div style="font-size: 20px; font-weight: 600">{{$data['client']['name']}}</div>
                                        </div>
                                        <div style="margin: 4px 0 0 0; display: flex;">
                                            <div style="font-size: 15px;">{{$data['client']['address'] ?? ''}}</div>
                                        </div>
                                        <div style="margin: 4px 0 0 0; display: flex; gap: 10px;">
                                            <div style="font-size: 18px;">Meter No: </div>
                                            <div style="font-size: 18px;">{{$data['client']['meter_serial_no']}}</div>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <div style="width: 100%; height: 1px; margin: 15px 0 10px 0; border-bottom: 1px dashed black; position: relative; display: flex; justify-content: center; align-items: center;">
                                        <h6 style="font-weight: bold; text-align: center; text-transform: uppercase; margin-bottom: 0px; margin-top: 10px; position: absolute; top: -17px; background-color: #fff; padding: 0 10px 0 10px;">Current Billing Info</h6>
                                    </div>
                                    <div style="text-align: center; text-transform: uppercase;">
                                        <div style="margin: 4px 0 0 0; display: flex; justify-content: space-between;">
                                            <div>Bill Date</div>
                                            <div>{{\Carbon\Carbon::parse($data['current_bill']['created_at'])->format('m/d/Y')}}</div>
                                        </div>
                                        <div style="margin: 4px 0 0 0; display: flex; justify-content: space-between;">
                                            <div>Period</div>
                                            <div>{{\Carbon\Carbon::parse($data['current_bill']['bill_period_from'])->format('m/d/Y') . ' TO ' . \Carbon\Carbon::parse($data['current_bill']['bill_period_to'])->format('m/d/Y')}}</div>
                                        </div>
                                        <div style="margin: 4px 0 0 0; display: flex; justify-content: space-between;">
                                            <div>Due Date</div>
                                            <div>{{\Carbon\Carbon::parse($data['current_bill']['due_date'])->format('m/d/Y')}}</div>
                                        </div>
                                        <div style="text-align: center; margin: 10px 0 10px 0; font-size: 12px; font-weight: 600; font-style: italic; color:rgb(91, 91, 91)">
                                            <ul style="list-style: none !important;">
                                                <li>> Office - Last working day of the month</li>
                                                <li>> Online - Last day of the month</li>
                                            </ul>
                                        </div>
                                        <div style="margin: 4px 0 0 0; display: flex; justify-content: space-between;">
                                            <div>Disconnection Date</div>
                                            <div>{{\Carbon\Carbon::parse($data['current_bill']['due_date'])->format('m/d/Y')}}</div>
                                        </div>
                                    </div>
                                </div>
                                <div style="width: 100%; height: 1px; margin: 10px 0 10px 0; border-bottom: 1px dashed black;"></div>
                                <div>
                                    <div style="display: flex; justify-content: space-between;">
                                        <div style="text-transform: uppercase">Previous Reading</div>
                                        <div style="text-transform: uppercase">{{$data['current_bill']['reading']['previous_reading'] ?? 'N/A'}}</div>
                                    </div>
                                    <div style="display: flex; justify-content: space-between;">
                                        <div style="text-transform: uppercase">Present Reading</div>
                                        <div style="text-transform: uppercase">{{$data['current_bill']['reading']['present_reading'] ?? '0'}}</div>
                                    </div>
                                    <div style="display: flex; justify-content: space-between; margin-top: 5px">
                                        <div style="font-size: 20px; font-weight: 800; text-transform: uppercase">Cub. M Used</div>
                                        <div style="font-size: 20px; font-weight: 800; text-transform: uppercase">{{$data['current_bill']['reading']['consumption'] ?? '0'}}</div>
                                    </div>
                                </div>
                                <div style="margin: 5px 0 5px 0; width: 100%; height: 1px; border-bottom: 1px dashed black;"></div>
                                <div>

                                    @php
                                        $breakdown = collect($data['current_bill']['breakdown']);
                                        $arrears = $breakdown->firstWhere('name', 'Previous Balance')['amount'] ?? 0;
                                        $deductions = $breakdown->reject(fn($item) => $item['name'] === 'Previous Balance')->values();
                                    @endphp


                                    @forelse($deductions as $deduction)
                                        @php
                                            if (strtolower($deduction['name']) === 'system fee') {
                                                continue;
                                            }
                                        @endphp
                                        <div style="display: flex; justify-content: space-between;">
                                            <div style="text-transform: uppercase">{{$deduction['name']}}</div>
                                            <div style="text-transform: uppercase">{{$deduction['amount']}}</div>
                                        </div>
                                    @empty

                                    @endforelse

                                    @php
                                        $discounts = $data['current_bill']['discount'];
                                        $totalDiscount = collect($discounts)->sum('amount');
                                    @endphp

                                    @forelse($discounts as $discount)
                                        <div style="display: flex; justify-content: space-between;">
                                            <div style="text-transform: uppercase">{{$discount['name']}}</div>
                                            <div style="text-transform: uppercase">({{$discount['amount']}})</div>
                                        </div>
                                    @empty

                                    @endforelse

                                    @forelse($discounts as $discount)
                                        <div style="display: flex; justify-content: space-between;">
                                            <div style="text-transform: uppercase">{{$discount['name']}}</div>
                                            <div style="text-transform: uppercase">- ({{$discount['amount']}})</div>
                                        </div>
                                    @empty

                                    @endforelse
                                    @if(!empty($data['current_bill']['advances']))
                                        <div style="display: flex; justify-content: space-between; margin: 5px 0 5px 0;">
                                            <div>Advances</div>
                                            <div>- ({{$data['current_bill']['advances']}})</div>
                                        </div>
                                    @endif
                                    <!-- <div style="display: flex; justify-content: space-between;">
                                        <div style="text-transform: uppercase;">2% Franchise Tax:</div>
                                        <div style="text-transform: uppercase;">0</div>
                                    </div> -->
                                </div>
                                <div style="margin: 5px 0 5px 0; width: 100%; height: 1px; border-bottom: 1px dashed black;"></div>
                                <div style="display: flex; justify-content: space-between; margin: 5px 0 5px 0;">
                                    <div style="font-size: 20px; font-weight: 800; text-transform: uppercase">Current Billing:</div>
                                    <div style="font-size: 20px; font-weight: 800; text-transform: uppercase">{{(float) $data['current_bill']['total'] - (float) $arrears - (float) $totalDiscount}}</div>
                                </div>
                                @if($arrears != 0)
                                    <div style="display: flex; justify-content: space-between;">
                                        <div style="text-transform: uppercase;">Arrears:</div>
                                        <div style="text-transform: uppercase;">{{$arrears}}</div>
                                    </div>
                                @endif
                                <div style="margin: 5px 0 5px 0; width: 100%; height: 1px; border-bottom: 1px dashed black;"></div>
                                <div style="display: flex; justify-content: space-between; align-items: center;">
                                    <div style="text-transform: uppercase; font-size: 20px; font-weight: 800;">Amount Due:</div>
                                    <div style="text-transform: uppercase; font-size: 20px; font-weight: 800;">{{number_format($data['current_bill']['amount'], 2)}} </div>
                                </div>
                                <div style="margin: 5px 0 0 0; display: flex; justify-content: space-between; align-items: center;">
                                    <div style="text-transform: uppercase;">Payment After Due Date</div>
                                    <div style="text-transform: uppercase;"></div>
                                </div>
                                <div style="margin: 5px 0 0 0; display: flex; justify-content: space-between; align-items: center;">
                                    <div style="text-transform: uppercase;">Penalty Date: </div>
                                    <div style="text-transform: uppercase;">
                                        @if($data['current_bill']['hasPenalty'])
                                            {{\Carbon\Carbon::parse($data['current_bill']['due_date'])->format('m/d/Y')}}
                                        @endif
                                    </div>
                                </div>
                                <div style="margin: 5px 0 0 0; display: flex; justify-content: space-between; align-items: center;">
                                    <div style="text-transform: uppercase;">Penalty Amt: </div>
                                    <div style="text-transform: uppercase;">
                                        @if($data['current_bill']['hasPenalty'])
                                            {{number_format($data['current_bill']['penalty'], 2)}}
                                        @endif
                                    </div>
                                </div>
                                <div style="margin: 5px 0 0 0; display: flex; justify-content: space-between; align-items: center;">
                                    <div style="text-transform: uppercase; font-size: 20px; font-weight: 800;">Amount After Due:</div>
                                    <div style="text-transform: uppercase; font-size: 20px; font-weight: 800;">
                                        @if($data['current_bill']['hasPenalty'])
                                            {{number_format($data['current_bill']['amount_after_due'], 2)}}
                                        @endif
                                    </div>
                                </div>
                                <div style="margin: 8px 0 5px 0; width: 100%; height: 1px; border-bottom: 1px dashed black;"></div>
                                <div style="margin: 5px 0 5px 0; width: 100%; height: 1px; border-bottom: 1px dashed black;"></div>
                                <h6 style="font-weight: bold; text-transform: uppercase; text-align: center; margin-top: 10px; margin-bottom: 10px;">6 months Consumption History</h6>
                                <div style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; text-transform: uppercase;">
                                    @foreach($data['previousConsumption'] as $prevConsump)
                                        <div style="text-align: center;">
                                            <div>
                                                {{$prevConsump['month']}}
                                            </div>
                                            <div>
                                                {{$prevConsump['value']}}
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                                <div style="margin: 10px 0 5px 0; width: 100%; height: 1px; border-bottom: 1px dashed black;"></div>
                                <h6 style="font-weight: bold; text-align: center; margin-top: 10px; margin-bottom: 10px;">Two (2) months of non-payment of bills mean AUTOMATIC DISCONNECTION</h6>
                                <div style="margin: 5px 0 5px 0; width: 100%; height: 1px; border-bottom: 1px dashed black;"></div>
                                <div style="margin: 5px 0 0 0; display: flex; justify-content: space-between; align-items: center;">
                                    <div style="text-transform: uppercase;">Bill No:</div>
                                    <div style="text-transform: uppercase;">{{$data['current_bill']['reference_no']}}</div>
                                </div>
                                <div style="margin: 5px 0 0 0; display: flex; justify-content: space-between; align-items: center;">
                                    <div style="text-transform: uppercase;">Meter Reader</div>
                                    <div style="text-transform: uppercase;">{{$data['current_bill']['reading']['reader_name']}}</div>
                                </div>
                                <div style="margin: 5px 0 0 0; display: flex; justify-content: space-between; align-items: center;">
                                    <div style="text-transform: uppercase;">Time Stamp: </div>
                                    <div style="text-transform: uppercase;">{{\Carbon\Carbon::now()->format('D M d H:i:s \G\M\TP Y')}}</div>
                                </div>
                                <div style="margin: 5px 0 5px 0; width: 100%; height: 1px; border-bottom: 1px dashed black;"></div>
                                <div style="margin-top: 15px; display: flex; justify-content: center; gap: 35px; align-items: center;">
                                    <div>
                                        {!! $qr_code !!}
                                    </div>
                                    <div>
                                        <h6 style="font-weight: bold; text-transform: uppercase; text-align: left; margin-top: 0; margin-bottom: 5px;">Pay Now</h6>
                                        <ol style="font-size: 10px; text-transform: uppercase; list-style-type: decimal; padding: 0; margin-top: 0px">
                                            <li>Scan the QR code.</li>
                                            <li>Choose a merchant on NovuPay.</li>
                                            <li>Pay the total amount due.</li>
                                            <li>Keep your receipt.</li>
                                        </ol>
                                    </div>
                                </div>

                                @php
                                    $bill = $data['current_bill']['created_at'] ?? null;
                                    $start = $data['client']['sc_discount']['effective_date'] ?? null;
                                    $end = $data['client']['sc_discount']['expired_date'] ?? null;
                                @endphp

                                @php
                                    $remarks = [];

                                    if ($bill && $start && $end) {
                                        $billDate = \Carbon\Carbon::parse($bill);
                                        $startDate = \Carbon\Carbon::parse($start);
                                        $endDate = \Carbon\Carbon::parse($end);

                                        if ($billDate->between($startDate, $endDate) && $billDate->diffInMonths($endDate, false) <= 1) {
                                            $remarks[] = 'senior citizen discount will expire on ' . $endDate->format('F d, Y') . ', renew now';
                                        }
                                    }

                                    if (!empty($data['current_bill']['isHighConsumption'])) {
                                        $remarks[] = 'high consumption';
                                    }

                                @endphp

                                @if (!empty($remarks))
                                    <div style="margin: 20px 0 16px 0; display: flex; justify-content: center; align-items: center;">
                                        <div style="color: red; text-transform: uppercase; text-align: center; font-style: italic; font-weight: 500;">
                                            REMARKS: {{ implodeWithAnd($remarks) }}
                                        </div>
                                    </div>
                                @endif
                                <div style="margin: 20px 0 16px 0; display: flex; justify-content: center; align-items: center;">
                                    <div style="text-transform: uppercase; text-align: center; font-weight: 500; background-color: #000; color: #fff; padding: 5px;">This is NOT valid as Official Receipt</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </main>
    <style>

        body * {
            font-family: Verdana, Geneva, Tahoma, sans-serif;
            font-size: 13px;
        }

        @import url("https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap");

        .web-logo {
            width: 100px;
            margin: 0 auto 10px auto !important;
        }

        .print-logo {
            display: none !important;
        }

        .emp {
            background-color: #000;
            padding: 8px 10px 8px 10px;
            color: #fff;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 20px !important;
        }

        @media print {

            @page {
                margin: 0mm 5mm 0mm 0mm;
            }

            body * {
                padding: 0px !important;
                box-shadow: none !important;
                visibility: visible !important;
                font-size: 10px !important;
                font-weight: 800;
                font-family: monospace;
            }

            header, .print-controls {
                display: none !important;
            }

            .isPaid {
                display: none;
                visibility: hidden;
            }

            svg {
                width: 80px !important;
            }

            .web-logo {
                display: none !important;
            }

            .print-logo {
                width: 100px;
                margin: 0 auto 10px auto !important;
                display: block !important;
            }

        }
    </style>
@endsection

