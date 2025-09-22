@extends('layouts.app')

@section('content')
    <main class="main">
        <div class="responsive-wrapper">
            <div class="inner-content mt-5 pb-5 mb-5">
                <div class="d-md-flex justify-content-center pb-5 gap-5">
                    <div class="mb-5" style="width: 100%">
                        <div class="card shadow border-0 p-2 pb-0 px-3" style="border-radius: 20px;">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-12 mb-3">
                                        <label for="zone_no" class="form-label">Zone</label>
                                        <select name="zone_no" id="zone_no" class="form-select text-uppercase dropdown-toggle">
                                            @if($showAllOption)
                                                <option value="all"> All Zones </option>
                                            @endif
                                            @foreach($zones as $item)
                                                <option value="{{ $item->zone }}">
                                                    {{ $item->zone . ' - ' . $item->area }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('zone_no')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="filter" class="form-label">Filter</label>
                                        <select name="filter" id="filter" class="form-select dropdown-toggle">
                                            <option value="50"> 50 </option>
                                            <option value="100"> 100 </option>
                                            <option value="300"> 300 </option>
                                            <option value="500"> 500 </option>
                                            <option value="700"> 700 </option>
                                            <option value="1000"> 1000 </option>
                                        </select>
                                        @error('filter')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="search_by" class="form-label">Search By</label>
                                        <select name="search_by" id="search_by" class="form-select dropdown-toggle text-uppercase">
                                        <option value="name">Name</option>
                                        <option value="account_no">Account No</option>
                                        <option value="meter_serial_no">Meter No</option>
                                        <option value="read">Read Accounts</option>
                                        <option value="unread">Unread Accounts</option>
                                    </select>

                                        @error('search_by')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-12 mb-3">
                                        <label for="search" class="form-label">Search</label>
                                        <input type="text" name="search" id="search" class="form-control text-uppercase" placeholder="Tap to search...">
                                        @error('search')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-5" style="width: 100%">
                        <div class="concessionaire-result">

                        </div>
                        <div class="concessionaire-list">

                        </d iv>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal fade " id="accountModal" data-bs-backdrop="static" tabindex="-1" aria-labelledby="accountModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered text-uppercase" da>
                <div class="modal-content text-uppercase">
                    <div class="modal-header px-4 text-uppercase">
                        <h5 class="modal-title text-uppercase" id="accountModalLabel"></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 text-uppercase">
                        <div id="accountModalBody">

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
    <style>
        .h-extend {
            height: 50px;
        }
    </style>
@endsection

@section('script')
<script>
    let selectedAccountNo = null;
    let offset = 0;
    const limit = 50;
    let isLoading = false;
    let hasMoreData = true;
    let didScrollToPreviousAccount = false;
    const isReRead = '{{$isReRead ? 'true' : 'false'}}';
    const reference_no = '{{$reference_no}}';
    const recentReading = @json(session('recent_reading'))

    $(function () {


        @if (session('alert'))
            setTimeout(() => {
                const { status, message } = @json(session('alert'));
                alert(status, message);
            }, 100);
        @endif

        checkIsReRead();

        function checkIsReRead() {

            if(isReRead == 'true') {
                $.get('{{ route(Route::currentRouteName()) }}', {
                    reference_no: reference_no,
                    isReRead: isReRead,
                }, function (response) {
                    selectedAccountNo = response.account_no;
                    const previousReading = parseFloat(response.previous_reading ?? 0);
                    const presentReading = parseFloat(response.present_reading ?? 0);
                    const consumption = parseFloat(response.consumption ?? 0);
                    const suggestedNextMonth = response.suggestedNextMonth;
                    const sc_expired_date = response.sc_expired_date;
                    const isHighConsumption = response.isHighConsumption ? true : false;

                    $('#accountModal .modal-title').html('Proceed Re-Reading');

                    let modalContent = `
                        <p class="mb-1"><strong class="text-uppercase">Account No:</strong> ${response.account_no}</p>
                        <p class="mb-1"><strong class="text-uppercase">Name:</strong> ${response.name ?? 'N/A'}</p>
                        <p class="mb-1"><strong class="text-uppercase">Address:</strong> ${response.address ?? 'N/A'}</p>
                        `
                        if(sc_expired_date != null) {
                            modalContent += `<div class="text-uppercase fw-bold mt-3  text-center py-2 px-3 alert alert-warning">Senior citizen discount will be expired on ${sc_expired_date}</div`
                        }
                    modalContent+=`
                        <hr>
                        <div class="row mt-3 ">
                            @if(env('IS_TEST_READING'))
                                <div class="col-md-12 mb-3">
                                    <label for="reading_month" class="form-label">Reading Month</label>
                                    <input type="date" class="form-control h-extend" id="reading_month" name="reading_month" value="${suggestedNextMonth}" placeholder="########">
                                </div>
                            @endif
                            <div class="col-md-12 mb-3">
                                <label for="present_reading" class="form-label">Present Reading</label>
                                <input type="number" class="form-control h-extend" id="present_reading" value="${presentReading}" placeholder="########">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="previous_reading" class="form-label">Previous Reading</label>
                                <input type="text" class="form-control restricted h-extend" id="previous_reading" value="${previousReading}" readonly>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="consumption" class="form-label">Consumption</label>
                                <input type="text" class="form-control restricted h-extend" id="consumption" value="${consumption}" readonly>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label d-block">Mark as High Consumption</label>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="is_high_consumption" id="is_high_consumption_yes" value="yes">
                                    <label class="form-check-label" for="is_high_consumption_yes">Yes</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="is_high_consumption" id="is_high_consumption_no" value="no" checked>
                                    <label class="form-check-label" for="is_high_consumption_no">No</label>
                                </div>
                            </div>
                        </div>
                        <div class="text-end mt-4">
                            <button type="button" class="btn btn-primary px-5 py-3 text-uppercase fw-bold" id="proceedButton">Proceed</button>
                        </div>
                    `;
                    $('#accountModalBody').html(modalContent);
                    $('.btn-close').remove();
                    const modal = new bootstrap.Modal(document.getElementById('accountModal'), {
                        backdrop: 'static',
                        keyboard: false
                    });

                   modal.show();

                    if (isHighConsumption) {
                        $('input[name="is_high_consumption"][value="yes"]').prop('checked', true);
                        $('input[name="is_high_consumption"][value="no"]').prop('checked', false);
                    } else {
                        $('input[name="is_high_consumption"][value="no"]').prop('checked', true);
                        $('input[name="is_high_consumption"][value="yes"]').prop('checked', false);
                    }
                });
            }

        }

        function fetchAccountData(append = false) {
            if (isLoading || !hasMoreData) return;
            isLoading = true;

            const zone = $('#zone_no').val();
            const filter = $('#filter').val();
            const searchBy = $('#search_by').val();
            const search = $('#search').val();

            $.get('{{ route(Route::currentRouteName()) }}', {
                zone,
                filter,
                search_by: searchBy,
                search,
                offset,
                limit
            }, function (data) {
                isLoading = false;

                if (!append) {
                    $('.concessionaire-list').empty();
                    offset = 0;
                    hasMoreData = true;
                }

                if (!data.length) {
                    if (!append) {
                        $('.concessionaire-list').html(`
                            <div class="alert alert-danger text-uppercase text-center shadow" role="alert">
                                No records found.
                            </div>
                        `);
                        $('.concessionaire-result').html(`
                            <div class="text-uppercase fw-bold text-muted fst-italic mb-2">
                                Accounts Found: 0
                            </div>
                        `);
                    }
                    hasMoreData = false;
                    return;
                }

                if (!append) {
                    $('.concessionaire-result').html(`
                        <div class="text-uppercase fw-bold text-muted fst-italic mb-2 " data-count="${data.length}">
                            Accounts Found: ${data.length}
                        </div>
                    `);
                } else {
                    const $resultDiv = $('.concessionaire-result > div');
                    const currentCount = parseInt($resultDiv.data('count') || 0);
                    const newCount = currentCount + data.length;
                    $resultDiv.data('count', newCount);
                    $resultDiv.html(`Accounts Found: ${newCount}`);
                }

                data.forEach((account, index) => {
                    const status = account.status;
                    const isActive = account.user?.isActive == 1;

                    const cardStyle = `
                        background-color: ${isActive ? '#fff' : '#ffffffff'};
                        cursor: pointer;
                    `;

                    const textColor = isActive ? '' : 'color: #000000ff;';

                    const dot = isActive
                        ? `<div style="width: 12px; height: 12px; border-radius: 50%; position: absolute; top: 18px; right: 25px; background-color: #28a745;"></div>`
                        : `<div style="width: 12px; height: 12px; border-radius: 50%; position: absolute; top: 18px; right: 25px; background-color: #ff1a1aff;"></div>`;

                    const html = `
                        <div class="card shadow mb-3 account-card"
                            data-account-no="${account.account_no}"
                            data-index="${offset + index}"
                            style="${cardStyle}"
                            data-account='${JSON.stringify(account)}'>
                            <div class="card-body" style="${textColor}">
                                ${dot}
                                <h5 class="card-title mb-0 fw-normal">Account No: ${account.account_no}</h5>
                                <hr class="my-2 mb-2">
                                <h5 class="fw-normal">Meter No: ${account.meter_serial_no}</h5>
                                <h4>${account.user ? account.user.name : 'N/A'}</h4>
                                <h5 class="fw-normal text-capitalize">${account.address ?? 'N/A'}</h5>
                            </div>
                        </div>
                    `;

                    $('.concessionaire-list').append(html);
                });


                offset += data.length;

                if (data.length < limit) {
                    hasMoreData = false;
                }

                if (!didScrollToPreviousAccount && recentReading && !append) {
                    setTimeout(() => {
                        const $target = $(`.account-card[data-account-no="${recentReading.account_no}"]`);
                        if ($target.length) {
                            $('.concessionaire-list').animate({
                                scrollTop: $target.offset().top - $('.concessionaire-list').offset().top + $('.concessionaire-list').scrollTop()
                            }, 500);
                            $target.css('box-shadow', '0 0 10px 5px #007bff');
                        }
                        didScrollToPreviousAccount = true;
                    }, 300);
                }
            }).fail((jqXHR, textStatus, errorThrown) => {
                isLoading = false;
                console.error('Error fetching data:', textStatus, errorThrown);
            });
        }

        function fetchRecentReading() {
            $.get('{{ route(Route::currentRouteName()) }}', {
                isGetRecentReading: true
            }, function(response) {
                if (!$.isEmptyObject(response)) {
                    renderRecentReadingCard(response);
                } else {
                    $('#recent-reading-container').empty();
                }
            }).fail(function(xhr) {
                console.error('Failed to fetch recent reading:', xhr);
            });
        }

        function renderRecentReadingCard(data) {
            if (!data) return;

            const formattedDate = data.timestamp
                ? new Date(data.timestamp).toLocaleString('en-US', {
                    year: 'numeric', month: 'long', day: '2-digit',
                    hour: '2-digit', minute: '2-digit', hour12: true
                }).replace(',', ' at')
                : '';

            const card = $(`
                <div class="card shadow mb-3 account-card mt-4 border-3 border-primary" style="cursor: pointer">
                    <div class="card-body">
                        <button class="btn btn-danger" id="clearRecent" style="position: absolute; top: 10px; right: 10px;">
                            <i class='bx bx-trash'></i>
                        </button>
                        <h5 class="card-title mb-0 fw-normal text-uppercase py-2 fw-bold">Recent Reading</h5>
                        <hr class="my-2 mb-2">
                        <h5 class="fw-normal">Account No: ${data.account_no}</h5>
                        <h4>${data.name}</h4>
                        <h5 class="fw-normal text-capitalize">${data.address}</h5>
                        <small>${formattedDate}</small>
                    </div>
                </div>
            `);

            $('#recent-reading-container').html(card);
        }

        $(document).on('click', '#showReadUnread', function () {
            const $btn = $(this);
            const originalText = $btn.text();
            const monthYear = $('#targetDate').val();

            if (monthYear == '') {
                alert('error', 'No month selected');
                return;
            }

            $btn.text('Please wait...').prop('disabled', true);

            const date = new Date(`${monthYear}-01`);
            const formattedMonthYear = date.toLocaleString('default', { month: 'long', year: 'numeric' });

            $.get('{{ route(Route::currentRouteName()) }}', {
                targetDate: monthYear,
                isGetReadUnread: true
            }, function (response) {
                $('#readUnreadModal').remove();

                const modalHtml = `
                    <div class="modal fade" id="readUnreadModal" tabindex="-1" aria-labelledby="readUnreadModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title">View Read and Unread For ${formattedMonthYear}</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <ul class="nav nav-pills" id="readUnreadTabs" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link text-uppercase px-4 active" id="unread-tab" data-bs-toggle="tab" data-bs-target="#unread" type="button" role="tab">Unread</button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button class="nav-link text-uppercase px-4" id="read-tab" data-bs-toggle="tab" data-bs-target="#read" type="button" role="tab">Read</button>
                                        </li>
                                    </ul>
                                    <div class="tab-content mt-3">
                                        <div class="tab-pane fade show active" id="unread" role="tabpanel">
                                            <div id="unread-list" style="max-height: 600px; overflow-y: auto;"></div>
                                        </div>
                                        <div class="tab-pane fade" id="read" role="tabpanel">
                                            <div id="read-list" style="max-height: 600px; overflow-y: auto;"></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>`;

                $('body').append(modalHtml);

                function renderAccountCards(data) {
                    if (!data.length) {
                        return '<div class="text-muted text-center py-3 text-uppercase">No records found.</div>';
                    }

                    return data.map(item => `
                        <div class="card mb-2 shadow-sm">
                            <div class="card-body p-3">
                                <h6 class="card-title mb-1"><strong>${item.name}</strong></h6>
                                <p class="mb-1"><strong>Account No:</strong> ${item.account_no}</p>
                                <p class="mb-1"><strong>Address:</strong> ${item.address}</p>
                                <p class="mb-0"><strong>Meter No:</strong> ${item.meter_no}</p>
                            </div>
                        </div>
                    `).join('');
                }

                $('#unread-list').html(renderAccountCards(response.unread || []));
                $('#read-list').html(renderAccountCards(response.read || []));

                const modal = new bootstrap.Modal(document.getElementById('readUnreadModal'));
                modal.show();
            }).always(function () {
                $btn.text(originalText).prop('disabled', false);
            });
        });

        $(document).on('click', '.account-card', function () {

            const account = $(this).data('account');
            selectedAccountNo = account.account_no;

            $('#accountAlert').empty();

            $.get('{{ route(Route::currentRouteName()) }}', {
                account_no: account.account_no,
                isGetPrevious: true,
            }, function (response) {
                const previousReading = parseFloat(response.previous_reading ?? 0);
                const suggestedNextMonth = response.suggestedNextMonth;
                const sc_expired_date = response.sc_expired_date;

                $('#accountModal .modal-title').html('Proceed Reading');

                let modalContent = `
                    <p class="mb-1"><strong class="text-uppercase">Account No:</strong> ${account.account_no}</p>
                    <p class="mb-1"><strong class="text-uppercase">Name:</strong> ${account.user?.name ?? 'N/A'}</p>
                    <p class="mb-1"><strong class="text-uppercase">Address:</strong> ${account.address ?? 'N/A'}</p>
                    `
                    if(sc_expired_date != null) {
                        modalContent += `<div class="text-uppercase fw-bold mt-3  text-center py-2 px-3 alert alert-warning">Senior citizen discount will be expired on ${sc_expired_date}</div`
                    }
                modalContent+=`
                    <hr>
                    <div class="row mt-3">
                        @if(env('IS_TEST_READING'))
                            <div class="col-md-12 mb-3">
                                <label for="reading_month" class="form-label">Reading Month</label>
                                <input type="date" class="form-control h-extend" id="reading_month" name="reading_month" value="${suggestedNextMonth}" placeholder="########">
                            </div>
                        @endif
                        <div class="col-md-12 mb-3">
                            <label for="present_reading" class="form-label">Present Reading</label>
                            <input type="number" class="form-control h-extend" id="present_reading" value="0" placeholder="########">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="previous_reading" class="form-label">Previous Reading</label>
                            <input type="text" class="form-control restricted h-extend" id="previous_reading" value="${previousReading}" readonly>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="consumption" class="form-label">Consumption</label>
                            <input type="text" class="form-control restricted h-extend" id="consumption" value="0" readonly>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label d-block">Mark as High Consumption</label>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="is_high_consumption" id="is_high_consumption_yes" value="yes">
                                <label class="form-check-label" for="is_high_consumption_yes">Yes</label>
                            </div>
                            <div class="form-check form-check-inline">
                                <input class="form-check-input" type="radio" name="is_high_consumption" id="is_high_consumption_no" value="no" checked>
                                <label class="form-check-label" for="is_high_consumption_no">No</label>
                            </div>
                        </div>
                    </div>
                    <div class="text-end mt-4">
                        <button type="button" class="btn btn-primary px-5 py-3 text-uppercase fw-bold d-none" id="proceedButton">Proceed</button>
                    </div>
                `;
                $('#accountModalBody').html(modalContent);
                $('#accountModal').modal('show');
            });
        });

        $(document).on('input', '#present_reading', function () {
            const present = parseFloat($(this).val()) || 0;
            const previous = parseFloat($('#previous_reading').val()) || 0;
            const consumption = Math.max(present - previous, 0);

            $('#consumption').val(consumption);

            $('#proceedButton').removeClass('d-none');


            // if (present > 0 && present > previous) {
            //     $('#proceedButton').removeClass('d-none');
            // } else {
            //     $('#proceedButton').addClass('d-none');
            // }
        });

        $(document).on('click', '#present_reading', function() {
            $(this).val('');
        });

        $(document).on('click', '#proceedButton', function () {
            const presentReading = $('#present_reading').val();
            const previousReading = $('#previous_reading').val();
            const readingMonth = $('#reading_month').val();
            const is_high_consumption = $('input[name="is_high_consumption"]:checked').val();

            if (!selectedAccountNo) {
                alert('error', 'Account number is missing.');
                return;
            }

            // if (!presentReading || isNaN(presentReading) || Number(presentReading) <= Number(previousReading)) {
            //     alert('error', 'Present reading must be greater than previous reading.');
            //     return;
            // }

            const postData = {
                reading_month: readingMonth,
                account_no: selectedAccountNo,
                previous_reading: previousReading,
                present_reading: presentReading,
                is_high_consumption: is_high_consumption,
                isReRead: isReRead,
                reference_no: reference_no,
            };

            $.ajax({
                url: '{{ route("reading.store") }}',
                type: 'POST',
                data: JSON.stringify(postData),
                contentType: 'application/json',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                success: function (response) {
                    alert(response.status, response.message);
                    $('#accountModal').modal('hide');
                    if (response.redirect_url) {
                        fetchRecentReading(recentReading);
                        setTimeout(() => {
                            window.open(
                                response.redirect_url,
                                'popupWindow',
                                'width=800,height=800,resizable=no,scrollbars=yes,toolbar=no,menubar=no,location=no,status=no'
                            );
                        }, 2000);
                    } else {
                        offset = 0;
                        hasMoreData = true;
                        fetchAccountData();
                    }
                },
                error: function (xhr) {
                    let errorMsg = 'An error occurred.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                    alert('error', errorMsg);
                }
            });
        });

        $(document).on('click', '#clearRecent', function() {
            $.post('{{ route(Route::currentRouteName()) }}', {
                isClearRecent: true,
                _token: '{{ csrf_token() }}'
            }, function(response) {
            }).fail(function(xhr) {
                console.error('Failed to fetch recent reading:', xhr);
            }).done(function() {
                fetchRecentReading();
            });
        });

        $('#zone_no, #filter, #search_by').on('change', function () {
            offset = 0;
            hasMoreData = true;
            fetchAccountData();
        });

        $('#search').on('keyup', function () {
            clearTimeout($.data(this, 'timer'));
            const wait = setTimeout(() => {
                offset = 0;
                hasMoreData = true;
                fetchAccountData();
            }, 400);
            $(this).data('timer', wait);
        });

        $('.concessionaire-list').on('scroll', function () {
            const $list = $(this);
            if ($list.scrollTop() + $list.innerHeight() >= $list[0].scrollHeight - 20) {
                fetchAccountData(true);
            }
        });

        fetchAccountData();
        fetchRecentReading();
    });
</script>
@endsection
