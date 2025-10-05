@extends('layouts.app')

@section('content')
    <main class="main">
        <div class="responsive-wrapper">
            <div class="main-header">
                <h1>Dashboard</h1>
            </div>
            <div class="row mt-5">
                <div class="col-12 col-md-4 mb-3">
                    <div class="card border-primary border-2 shadow p-3">
                        <div class="card-body">
                            <h4 class="mb-3 text-uppercase fw-medium">Admins</h4>
                            <h1>{{number_format($data['admins']) ?? 0}}</h1>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4 mb-3">
                    <div class="card border-primary border-2 shadow p-3">
                        <div class="card-body">
                            <h4 class="mb-3 text-uppercase fw-medium">Concessionaires</h4>
                            <h1>{{number_format($data['concessionaires'])}}</h1>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4 mb-3">
                    <div class="card border-primary border-2 shadow p-3">
                        <div class="card-body">
                            <h4 class="mb-3 text-uppercase fw-medium">Technicians</h4>
                            <h1>{{number_format($data['technicians']) ?? 0}}</h1>
                        </div>
                    </div>
                </div>
            </div>
            <div>
                <hr class="mt-3 mb-3">
                <p class="text-uppercase text-muted fw-bold">
                    As of
                    <span class="text-decoration-underline fst-italic">
                      {{ \Carbon\Carbon::now()->format('F d, Y \a\t h:i A') }}
                    </span>
                </p>
            </div>

            <div class="row">
                <div class="col-12 col-md-4 mb-3">
                    <div class="card border-primary border-2 shadow p-3">
                        <div class="card-body">
                            <h4 class="mb-3 text-uppercase fw-medium">Unpaid Amount</h4>
                            <h1>₱{{number_format($data['total_unpaid'] ?? 0, 2)}}</h1>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4 mb-3">
                    <div class="card border-primary border-2 shadow p-3">
                        <div class="card-body">
                            <h4 class="mb-3 text-uppercase fw-medium">Paid Amount</h4>
                            <h1>₱{{number_format($data['total_paid'] ?? 0, 2)}}</h1>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4 mb-3">
                    <div class="card border-primary border-2 shadow p-3">
                        <div class="card-body">
                            <h4 class="mb-3 text-uppercase fw-medium">Total Amount</h4>
                            <h1>₱{{number_format($data['total_transactions'] ?? 0, 2)}}</h1>
                        </div>
                    </div>
                </div>
                <!-- <div class="col-12 col-md-4 mb-3">
                    <div class="card border-primary border-2 shadow p-3">
                        <div class="card-body">
                            <h4 class="mb-3 text-uppercase fw-medium">Transactions</h4>
                            <h1>{{ $data['total_transactions_count'] ?? 0 }}</h1>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4 mb-3">
                    <div class="card border-primary border-2 shadow p-3">
                        <div class="card-body">
                            <h4 class="mb-3 text-uppercase fw-medium">Cash Payment</h4>
                            <h1>{{ $data['payment_method_count']['cash'] ?? 0 }}</h1>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-4 mb-3">
                    <div class="card border-primary border-2 shadow p-3">
                        <div class="card-body">
                            <h4 class="mb-3 text-uppercase fw-medium">Online Payment</h4>
                            <h1>{{ $data['payment_method_count']['online'] ?? 0 }}</h1>
                        </div>
                    </div>
                </div> -->
            </div>
        </div>
    </main>
@endsection
