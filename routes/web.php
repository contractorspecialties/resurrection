<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\CustomerMessageController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EstimateBalanceController;
use App\Http\Controllers\EstimateController;
use App\Http\Controllers\ManualPaymentController;
use App\Http\Controllers\MessageCenterController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\PaymentSettingsController;
use App\Http\Controllers\PortalController;
use App\Http\Controllers\PortalPaymentController;
use App\Http\Controllers\PortalQuickBillController;
use App\Http\Controllers\PriceBookController;
use App\Http\Controllers\QuickBillController;
use App\Http\Controllers\RecurringServiceController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Middleware\EnsureOnboardingCompleted;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::middleware('guest')->group(function () {
    Route::get('/register', [AuthController::class, 'showRegister'])
        ->name('register');

    Route::post('/register', [AuthController::class, 'register'])
        ->name('register.store');

    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->name('login.store');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');


/*
|--------------------------------------------------------------------------
| Customer Portal
|--------------------------------------------------------------------------
*/

Route::get('/portal/{token}', [PortalController::class, 'show'])
    ->name('portal.show');

Route::post('/portal/{token}/revision', [PortalController::class, 'requestRevision'])
    ->name('portal.revision');

Route::post('/portal/{token}/accept', [PortalController::class, 'accept'])
    ->name('portal.accept');

Route::post('/portal/{token}/deposit', [PortalPaymentController::class, 'deposit'])
    ->name('portal.deposit');

Route::post('/portal/{token}/final-balance', [PortalPaymentController::class, 'finalBalance'])
    ->name('portal.final-balance');

Route::get('/portal/{token}/payment-return', [PortalPaymentController::class, 'returned'])
    ->name('portal.payment.return');


/*
|--------------------------------------------------------------------------
| Quick Bill Customer Payment
|--------------------------------------------------------------------------
*/

Route::get('/quick-pay/{token}', [PortalQuickBillController::class, 'show'])
    ->name('portal.quick-bill.show');

Route::post('/quick-pay/{token}', [PortalPaymentController::class, 'quickBill'])
    ->name('portal.quick-bill.pay');

Route::get('/quick-pay/{token}/return', [PortalPaymentController::class, 'quickBillReturned'])
    ->name('portal.quick-bill.return');


/*
|--------------------------------------------------------------------------
| Stripe Webhook
|--------------------------------------------------------------------------
*/

Route::post('/webhooks/stripe', StripeWebhookController::class)
    ->name('webhooks.stripe');


/*
|--------------------------------------------------------------------------
| Authenticated Contractor App
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | Initial Setup
    |--------------------------------------------------------------------------
    */

    Route::get('/setup', [OnboardingController::class, 'show'])
        ->name('setup');

    Route::post('/setup', [OnboardingController::class, 'store'])
        ->name('setup.store');


    /*
    |--------------------------------------------------------------------------
    | Onboarding-Complete App
    |--------------------------------------------------------------------------
    */

    Route::middleware(EnsureOnboardingCompleted::class)->group(function () {

        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */

        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->name('dashboard');

        Route::post('/dashboard/job-reminders', [DashboardController::class, 'updateJobReminders'])
            ->name('dashboard.job-reminders');


        /*
        |--------------------------------------------------------------------------
        | Customers
        |--------------------------------------------------------------------------
        */

        Route::get('/customers', [ClientController::class, 'index'])
            ->name('customers.index');

        Route::get('/customers/archived', [ClientController::class, 'archived'])
            ->name('customers.archived');

        Route::post('/customers/{client}/restore', [ClientController::class, 'restore'])
            ->name('customers.restore');

        Route::get('/customers/new', [ClientController::class, 'create'])
            ->name('customers.create');

        Route::post('/customers', [ClientController::class, 'store'])
            ->name('customers.store');

        Route::get('/customers/{client}/edit', [ClientController::class, 'edit'])
            ->name('customers.edit');

        Route::put('/customers/{client}', [ClientController::class, 'update'])
            ->name('customers.update');

        Route::delete('/customers/{client}', [ClientController::class, 'destroy'])
            ->name('customers.destroy');


        /*
        |--------------------------------------------------------------------------
        | Price Book
        |--------------------------------------------------------------------------
        */

        Route::get('/price-book', [PriceBookController::class, 'index'])
            ->name('price-book.index');

        Route::get('/price-book/options', [PriceBookController::class, 'options'])
            ->name('price-book.options');

        Route::get('/price-book/new', [PriceBookController::class, 'create'])
            ->name('price-book.create');

        Route::post('/price-book', [PriceBookController::class, 'store'])
            ->name('price-book.store');

        Route::get('/price-book/{item}/edit', [PriceBookController::class, 'edit'])
            ->name('price-book.edit');

        Route::put('/price-book/{item}', [PriceBookController::class, 'update'])
            ->name('price-book.update');

        Route::delete('/price-book/{item}', [PriceBookController::class, 'destroy'])
            ->name('price-book.destroy');

        Route::post('/price-book/{item}/restore', [PriceBookController::class, 'restore'])
            ->name('price-book.restore');


        /*
        |--------------------------------------------------------------------------
        | Estimates
        |--------------------------------------------------------------------------
        */

        Route::get('/estimates', [EstimateController::class, 'index'])
            ->name('estimates.index');

        Route::get('/estimates/new', [EstimateController::class, 'create'])
            ->name('estimates.create');

        Route::post('/estimates', [EstimateController::class, 'store'])
            ->name('estimates.store');

        Route::get('/estimates/{estimate}', [EstimateController::class, 'show'])
            ->name('estimates.show');

        Route::get('/estimates/{estimate}/edit', [EstimateController::class, 'edit'])
            ->name('estimates.edit');

        Route::put('/estimates/{estimate}', [EstimateController::class, 'update'])
            ->name('estimates.update');

        Route::post('/estimates/{estimate}/mark-sent', [EstimateController::class, 'markSent'])
            ->name('estimates.mark-sent');

        Route::post('/estimates/{estimate}/balance-due', [EstimateBalanceController::class, 'makeDue'])
            ->name('estimates.balance-due');

        Route::post('/estimates/{estimate}/job-date', [EstimateController::class, 'setJobDate'])
            ->name('estimates.job-date');


        /*
        |--------------------------------------------------------------------------
        | Quick Bills
        |--------------------------------------------------------------------------
        */

        Route::get('/quick-bills', [QuickBillController::class, 'index'])
            ->name('quick-bills.index');

        Route::get('/quick-bills/new', [QuickBillController::class, 'create'])
            ->name('quick-bills.create');

        Route::post('/quick-bills', [QuickBillController::class, 'store'])
            ->name('quick-bills.store');

        Route::get('/quick-bills/{quickBill}', [QuickBillController::class, 'show'])
            ->name('quick-bills.show');


        /*
        |--------------------------------------------------------------------------
        | Recurring Services
        |--------------------------------------------------------------------------
        */

        Route::get('/recurring-services', [RecurringServiceController::class, 'index'])
            ->name('recurring-services.index');

        Route::get('/recurring-services/new', [RecurringServiceController::class, 'create'])
            ->name('recurring-services.create');

        Route::post('/recurring-services', [RecurringServiceController::class, 'store'])
            ->name('recurring-services.store');

        Route::get('/recurring-services/{recurringService}/edit', [RecurringServiceController::class, 'edit'])
            ->name('recurring-services.edit');

        Route::put('/recurring-services/{recurringService}', [RecurringServiceController::class, 'update'])
            ->name('recurring-services.update');

        Route::post('/recurring-services/{recurringService}/generate', [RecurringServiceController::class, 'generate'])
            ->name('recurring-services.generate');

        Route::post('/recurring-services/{recurringService}/toggle', [RecurringServiceController::class, 'toggle'])
            ->name('recurring-services.toggle');


        /*
        |--------------------------------------------------------------------------
        | Messages
        |--------------------------------------------------------------------------
        */

        Route::get('/messages', [MessageCenterController::class, 'index'])
            ->name('messages.index');

        Route::get('/messages/new', [MessageCenterController::class, 'create'])
            ->name('messages.create');

        Route::post('/messages', [MessageCenterController::class, 'store'])
            ->name('messages.store');

        Route::post('/messages/estimates/{estimate}/email', [CustomerMessageController::class, 'estimateEmail'])
            ->name('messages.estimate.email');

        Route::post('/messages/estimates/{estimate}/sms', [CustomerMessageController::class, 'estimateSms'])
            ->name('messages.estimate.sms');

        Route::post('/messages/quick-bills/{quickBill}/email', [CustomerMessageController::class, 'quickBillEmail'])
            ->name('messages.quick-bill.email');

        Route::post('/messages/quick-bills/{quickBill}/sms', [CustomerMessageController::class, 'quickBillSms'])
            ->name('messages.quick-bill.sms');


        /*
        |--------------------------------------------------------------------------
        | Manual Payments
        |--------------------------------------------------------------------------
        */

        Route::post('/payments/manual/estimate/{estimate}', [ManualPaymentController::class, 'estimate'])
            ->name('payments.manual.estimate');

        Route::post('/payments/manual/quick-bill/{quickBill}', [ManualPaymentController::class, 'quickBill'])
            ->name('payments.manual.quick-bill');


        /*
        |--------------------------------------------------------------------------
        | Stripe / Payment Settings
        |--------------------------------------------------------------------------
        */

        Route::get('/payments', [PaymentSettingsController::class, 'index'])
            ->name('payments.index');

        Route::post('/payments/stripe/connect', [PaymentSettingsController::class, 'connect'])
            ->name('payments.stripe.connect');

        Route::get('/payments/stripe/refresh', [PaymentSettingsController::class, 'refresh'])
            ->name('payments.stripe.refresh');

        Route::get('/payments/stripe/return', [PaymentSettingsController::class, 'returned'])
            ->name('payments.stripe.return');
    });
});