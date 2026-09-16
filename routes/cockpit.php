<?php

use App\Http\Controllers\Cockpit\AuditController;
use App\Http\Controllers\Cockpit\Auth\ProfileController;
use App\Http\Controllers\Cockpit\AuthenticationController;
use App\Http\Controllers\Cockpit\BusinessController;
use App\Http\Controllers\Cockpit\ConfigController;
use App\Http\Controllers\Cockpit\DashboardController;
use App\Http\Controllers\Cockpit\InvoiceController;
use App\Http\Controllers\Cockpit\SubscriptionPlanController;
use App\Http\Controllers\Cockpit\UomController;
use Illuminate\Support\Facades\Route;

Route::name('cockpit.')->group(function () {
    Route::get('/csrf-token', [\App\Http\Controllers\Support\CsrfTokenController::class, 'show'])->name('csrf.token');

    Route::middleware('guest:cockpit')->group(function () {
        Route::get('/login', [AuthenticationController::class, 'index'])->name('login');
        Route::post('/login', [AuthenticationController::class, 'store'])->name('login.attempt');
    });

    Route::middleware('auth:cockpit')->group(function () {
        Route::delete('/logout', [AuthenticationController::class, 'destroy'])->name('logout');

        Route::patch('/profile', [ProfileController::class, 'updateProfile'])->name('profile.update');
        Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/business', [BusinessController::class, 'index'])->name('merchants.index');
        Route::post('/business/{id}/toggle-status', [BusinessController::class, 'toggleStatus'])->name('merchants.toggle-status');
        Route::get('/business/{id}', [BusinessController::class, 'show'])->name('merchants.show');
        Route::get('/business/{id}/impersonate/{userId}', [BusinessController::class, 'impersonate'])->name('merchants.impersonate');

        Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices.index');
        Route::post('/invoices/{invoice}/approve', [InvoiceController::class, 'approve'])->name('invoices.approve');
        Route::post('/invoices/{invoice}/reject', [InvoiceController::class, 'reject'])->name('invoices.reject');

        Route::get('/subscription-plans', [SubscriptionPlanController::class, 'index'])->name('subscription-plans.index');
        Route::post('/subscription-plans', [SubscriptionPlanController::class, 'store'])->name('subscription-plans.store');
        Route::get('/subscription-plans/{id}', [SubscriptionPlanController::class, 'show'])->name('subscription-plans.show');
        Route::put('/subscription-plans/{id}', [SubscriptionPlanController::class, 'update'])->name('subscription-plans.update');
        Route::delete('/subscription-plans/{id}', [SubscriptionPlanController::class, 'destroy'])->name('subscription-plans.destroy');
        Route::post('/subscription-plans/{id}/toggle-status', [SubscriptionPlanController::class, 'toggleStatus'])->name('subscription-plans.toggle-status');
        Route::post('/subscription-plans/{id}/toggle-visibility', [SubscriptionPlanController::class, 'toggleVisibility'])->name('subscription-plans.toggle-visibility');
        Route::put('/subscription-plans/{id}/features', [SubscriptionPlanController::class, 'updateFeatures'])->name('subscription-plans.update-features');

        Route::get('/payment-methods', [\App\Http\Controllers\Cockpit\SubscriptionManualPaymentMethodController::class, 'index'])->name('payment-methods.index');
        Route::post('/payment-methods', [\App\Http\Controllers\Cockpit\SubscriptionManualPaymentMethodController::class, 'store'])->name('payment-methods.store');
        Route::put('/payment-methods/{id}', [\App\Http\Controllers\Cockpit\SubscriptionManualPaymentMethodController::class, 'update'])->name('payment-methods.update');
        Route::post('/payment-methods/{id}/toggle-status', [\App\Http\Controllers\Cockpit\SubscriptionManualPaymentMethodController::class, 'toggleStatus'])->name('payment-methods.toggle-status');
        Route::delete('/payment-methods/{id}', [\App\Http\Controllers\Cockpit\SubscriptionManualPaymentMethodController::class, 'destroy'])->name('payment-methods.destroy');

        Route::get('/uoms', [UomController::class, 'index'])->name('uoms.index');

        Route::get('/config', [ConfigController::class, 'index'])->name('config.index');
        Route::put('/config/settings', [ConfigController::class, 'updateSettings'])->name('config.settings.update');
        Route::patch('/config/feature-flag', [ConfigController::class, 'updateFlag'])->name('config.feature-flag.update');

        Route::get('/audit', [AuditController::class, 'index'])->name('audit.index');
    });
});
