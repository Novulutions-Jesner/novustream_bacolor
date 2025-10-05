<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Print Bill {{$reference_no}}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-SgOJa3DmI69IUzQ2PVdRZhwQ+dy64/BUtbMJw1MZ8t5HZApcHrRKUc4W0kG879m7" crossorigin="anonymous">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js" integrity="sha384-k6d4wzSIapyDyv1kpU366/PK5hCdSbCRGRCMv+eplOQJWyd1fbcAu9OCUj5zNLiq" crossorigin="anonymous"></script>
    @vite(['resources/js/app.js'])
</head>
<body>

    <div class="container">
        <div class="print-controls" style="display: grid; grid-template-columns: repeat(2, auto); gap: 12px; margin: 50px auto; width: fit-content;">
            @php
                $previousUrl = url()->previous();
                $currentUrl = url()->current();
                $fallbackUrl = Auth::user()->user_type == 'client' ? route('account-overview.show') : route('reading.index');
                $backUrl = ($previousUrl !== $currentUrl) ? $previousUrl : $fallbackUrl;
            @endphp

            <a href="{{$backUrl}}"
                id="goBackButton"
                style="border: 1px solid #32667e; padding: 12px 40px; text-align:center; text-transform: uppercase; display: flex; align-items: center; gap: 8px; text-decoration: none; color: #32667e; background-color: transparent; border-radius: 5px;">
                <i style="font-size: 15px;" class='bx bx-left-arrow-alt'></i> Go Back
            </a>

            @if(!$isReRead['status'])
                <button
                    class="download-js"
                    data-target="#bill"
                    data-filename="{{$data['current_bill']['reference_no']}}"
                    style="background-color: #32667e; color: white; padding: 12px 40px; text-align:center; text-transform: uppercase; display: flex; align-items: center; gap: 8px; border: none; border-radius: 5px; cursor: pointer;">
                    <i style="font-size: 15px;" class='bx bxs-download'></i> Download
                </button>
                @if(!$data['current_bill']['isPaid'] == true)
                    <button
                        class="reRead"
                        style="text-align: center; background-color: #32667e; color: white; padding: 12px 40px; text-align:center; text-transform: uppercase; display: flex; align-items: center; gap: 8px; border: none; border-radius: 5px; cursor: pointer;">
                        <i style="font-size: 15px;" class='bx bxs-printer'></i> Re Read
                    </button>
                @endif
                <button
                    class="print-js"
                    style="text-align: center; background-color: #32667e; color: white; padding: 12px 40px; text-align:center; text-transform: uppercase; display: flex; align-items: center; gap: 8px; border: none; border-radius: 5px; cursor: pointer;">
                    <i style="font-size: 15px;" class='bx bxs-printer'></i> Print Bill
                </button>
            @endif
        </div>
        @if($isReRead['status'])
            <div class="d-flex justify-content-center text-center">
                <div style="font-size: 14px;" class="alert alert-danger px-4 text-uppercase fw-bold">
                    This bill has been discarded as it has already been re-read. <br>
                    Please refer to the re-read bill with reference number:
                    <a style="color: inherit;" href="{{ route('reading.show', ['reference_no' => $isReRead['reference_no']]) }}">
                        {{ $isReRead['reference_no'] }}
                    </a>.
                </div>
            </div>
        @endif
        <div style="padding-bottom: 50px">
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
                                    alt="logo" class="web-logo">
                                <img src="{{ $base64 }}" alt="logo" class="print-logo">
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
                                <div class="oversized" style="margin: 4px 0 0 0; display: flex; gap: 5px; align-items: center;">
                                    <div style="font-size: 20px; font-weight: 600">Account No. </div>
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
                                <div class="oversized-2" style="text-align: center; margin: 10px 0 10px 0; font-size: 10px; font-weight: 800; font-style: italic; color:rgb(91, 91, 91)">
                                    <ul style="list-style: none !important">
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
                            <div class="oversized" style="display: flex; justify-content: space-between; margin-top: 5px">
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
                        </div>
                        <div style="margin: 5px 0 5px 0; width: 100%; height: 1px; border-bottom: 1px dashed black;"></div>
                        <div class="oversized" style="display: flex; justify-content: space-between; margin: 5px 0 5px 0;">
                            <div style="font-size: 20px; font-weight: 800; text-transform: uppercase">Current Billing:</div>
                            <div style="font-size: 20px; font-weight: 800; text-transform: uppercase">{{ (float) $data['current_bill']['total'] - (float) $arrears - (float) $totalDiscount - (float) ($franchise->amount ?? 0) }}
</div>
                        </div>
                        @if($arrears != 0)
                            <div style="display: flex; justify-content: space-between;">
                                <div style="text-transform: uppercase;">Arrears:</div>
                                <div style="text-transform: uppercase;">{{$arrears}}</div>
                            </div>
                        @endif
                        <div style="margin: 5px 0 5px 0; width: 100%; height: 1px; border-bottom: 1px dashed black;"></div>
                        <div class="oversized" style="display: flex; justify-content: space-between; align-items: center;">
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
                                {{\Carbon\Carbon::parse($data['current_bill']['due_date'])->format('m/d/Y')}}
                            </div>
                        </div>
                        <div style="margin: 5px 0 0 0; display: flex; justify-content: space-between; align-items: center;">
                            <div style="text-transform: uppercase;">Penalty Amt: </div>
                            <div style="text-transform: uppercase;">
                                {{number_format($data['current_bill']['assumed_penalty'], 2)}}
                            </div>
                        </div>
                        <div class="oversized" style="margin: 5px 0 0 0; display: flex; justify-content: space-between; align-items: center;">
                            <div style="text-transform: uppercase; font-size: 20px; font-weight: 800;">Amount After Due:</div>
                            <div style="text-transform: uppercase; font-size: 20px; font-weight: 800;">
                                {{number_format($data['current_bill']['assumed_amount_after_due'], 2)}}
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
                        <div style="margin: 30px 0 0 0; display: flex; justify-content: center; align-items: center;">
                            <div class="emp">This is NOT valid as Official Receipt</div>
                        </div>
                        <div style="margin: 5px 0 5px 0; width: 100%; height: 1px; border-bottom: 1px dashed black;"></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade" id="reReadModal" tabindex="-1" aria-labelledby="reReadModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h6 class="modal-title text-uppercase fw-bold" id="reReadModalLabel">Confirm to Re-Read?</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-uppercase fw-medium alert alert-danger text-center" style="font-size: 14px;">Once you proceed, the current bill will be discarded and replaced with an updated version</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" style="font-size: 14px; background-color: #32667e !important" class="btn btn-primary text-uppercase fw-medium px-4 py-2" id="confirmReRead">Yes, Proceed</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <style>

        body * {
            font-family: Verdana, Geneva, Tahoma, sans-serif;
        }

        .print-controls {
            a, button {
                font-size: 14px !important;
                font-weight: 600;
            }
        }

        #bill {
            font-size: 13px;
        }

        @import url("https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap");

        .web-logo {
            width: 90px;
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
                margin: 0mm 5mm 10mm 0mm;
            }

            .oversized div {
                font-size: 15px !important;
                font-weight: 800 !important;
            }

            .oversized-2 ul li {
                font-size: 10px !important;
                font-weight: 800 !important;
            }

            .emp {
                background-color: #000;
                padding: 8px 10px 8px 10px;
                margin-bottom: 100px;
                color: #000;
                font-size: 12px;
                font-weight: 600;
                text-transform: uppercase;
            }

            body * {
                padding: 0px !important;
                box-shadow: none !important;
                visibility: visible !important;
                font-size: 10px !important;
                font-weight: 600;
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
    <script>
        $(function () {
            @if (session('alert'))
                setTimeout(() => {
                    let alertData = @json(session('alert'));
                    alert(alertData.status, alertData.message);
                }, 100);
            @endif

            const reference_no = '{{$reference_no}}';

            if (window.opener && window.opener !== window) {
                $('#goBackButton').on('click', function (e) {
                    e.preventDefault();
                    window.close();
                });
            }

            let selectedBillId = null;

            $(document).on('click', '.reRead', function() {
                selectedBillId = $(this).data('bill-id');
                $('#reReadModal').modal('show');
            });

            $('#confirmReRead').on('click', function() {
                $('#reReadModal').modal('hide');
                const redirect = `{{ route('reading.index') }}?re-read=true&reference_no=${encodeURIComponent(reference_no)}`;
                console.log(redirect);
                setTimeout(() => {
                    window.location.href = redirect;
                }, 1000);
            });
        });
    </script>
</body>
