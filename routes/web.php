<?php

use App\Http\Controllers\AccountOverviewController;
use App\Http\Controllers\ConcessionaireController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoginController;
use App\Http\Controllers\PaymentBreakdownController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PropertyTypesController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SupportTicketController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\BaseRateController;
use App\Http\Controllers\RatesController;
use App\Http\Controllers\ReadingController;
use App\Http\Controllers\ImportController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\RegisterController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return redirect()->to('/login');
});

Route::get('/login', [LoginController::class, 'index'])
    ->name('auth.index');

Route::post('/login', [LoginController::class, 'login'])
    ->name('auth.login');

Route::get('/login', [LoginController::class, 'index']);

Route::any('/logout', [LoginController::class, 'logout'])
    ->name('auth.logout');

// Show register page
Route::get('/register', [RegisterController::class, 'showRegistrationForm'])
    ->name('register');

// Handle register form
Route::post('/register', [RegisterController::class, 'register'])
    ->name('auth.register.store');

Route::middleware('admin')->prefix('admin')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('reading', [ReadingController::class, 'index'])
        ->name('reading.index');

    Route::post('reading', [ReadingController::class, 'store'])
        ->name('reading.store');

    Route::get('reading/view/bill/{reference_no}', [ReadingController::class, 'view_bill'])
        ->name('reading.view-bill');

    Route::get('reading/bill/{reference_no}', [ReadingController::class, 'show'])
        ->name('reading.show');

    Route::get('reading/reports', [ReadingController::class, 'report'])
        ->name('reading.report');

    Route::prefix('users')->group(function() {

        Route::resource('roles', RoleController::class)
            ->names('roles')
            ->only('index', 'destroy');

        Route::resource('concessionaires', ConcessionaireController::class)
            ->names('concessionaires')
            ->except('show');

        Route::resource('personnel', AdminController::class)
            ->names('admins');
    });

    Route::any('import', [ImportController::class, 'index'])
        ->name('import');

    Route::prefix('payments')->group(function() {
        Route::get('', [PaymentController::class, 'index'])
            ->name('payments.index');
        Route::any('previous-billing', [PaymentController::class, 'upload'])
            ->name('previous-billing.upload');
        Route::get('process/{reference_no}', [PaymentController::class, 'pay'])
            ->name('payments.pay');
        Route::post('process/{reference_no}', [PaymentController::class, 'pay'])
            ->name('payments.pay');
    });

    Route::prefix('settings')->group(function() {
        Route::resource('property-types', PropertyTypesController::class)
            ->names('property-types');

        Route::resource('rates', RatesController::class)
            ->names('rates')->only('index', 'create', 'update', 'store');

        Route::put('update-rates', [RatesController::class, 'updateBulkRate'])->name('bulk-rates.update');

        Route::resource('base-rate', BaseRateController::class)
            ->names('base-rate')->only('index', 'store');

        Route::resource('payment-breakdown', PaymentBreakdownController::class)
            ->names('payment-breakdown');
    });


    Route::get('/transactions', [ConcessionaireController::class, 'index'])
        ->name('transactions');

    Route::get('/reports', [ConcessionaireController::class, 'index'])
        ->name('reports');

    Route::prefix('/support')->group(function() {
        Route::prefix('/ticket/submit')->group(function() {
            Route::get('/', [SupportTicketController::class, 'create'])
                ->name('admin.support-ticket.create');
            Route::post('/', [SupportTicketController::class, 'store'])
                ->name('admin.support-ticket.store');
            Route::get('/{ticket}', [SupportTicketController::class, 'show'])
                ->name('admin.support-ticket.show');
            Route::delete('/{ticket}', [SupportTicketController::class, 'destroy'])
                ->name('admin.support-ticket.destroy');
            Route::get('/edit/{ticket}', [SupportTicketController::class, 'edit'])
                ->name('admin.support-ticket.edit');
            Route::put('/edit/{ticket}', [SupportTicketController::class, 'update'])
                ->name('admin.support-ticket.update');
        });
    });

});

Route::middleware('auth')->prefix('concessionaire')->group(function() {
    Route::prefix('my')->group(function() {
        Route::get('overview', [AccountOverviewController::class, 'index'])
            ->name('account-overview.index');
        Route::get('bills', [AccountOverviewController::class, 'bills'])
            ->name('account-overview.bills');
        Route::get('bills/{reference_no?}', [AccountOverviewController::class, 'bills'])
            ->name('account-overview.bills.reference_no');
    });

    Route::prefix('/support')->group(function() {
        Route::prefix('/ticket/submit')->group(function() {
            Route::get('/', [SupportTicketController::class, 'create'])
                ->name('client.support-ticket.create');
            Route::post('/', [SupportTicketController::class, 'store'])
                ->name('client.support-ticket.store');
            Route::get('/{ticket}', [SupportTicketController::class, 'show'])
                ->name('client.support-ticket.show');
            Route::delete('/{ticket}', [SupportTicketController::class, 'destroy'])
                ->name('client.support-ticket.destroy');
            Route::get('/edit/{ticket}', [SupportTicketController::class, 'edit'])
                ->name('client.support-ticket.edit');
            Route::put('/edit/{ticket}', [SupportTicketController::class, 'update'])
                ->name('client.support-ticket.update');
        });
    });
});

Route::resource('/{user_type}/profile', ProfileController::class)
        ->names('profile');

// bux payment routes
Route::get('/payments/redirect/{reference_no}', [PaymentController::class, 'redirect'])->name('payments.redirect');
Route::post('/payments/webhook', [PaymentController::class, 'webhook'])->name('payments.webhook');
