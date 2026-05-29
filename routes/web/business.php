<?php

use App\Http\Controllers\Merchant\BillingController;
use App\Http\Controllers\Merchant\InvoiceController;
use App\Http\Controllers\Merchant\MerchantInfoController;
use App\Http\Controllers\Merchant\OutletController;
use App\Http\Controllers\Merchant\SubscribeController;
use Illuminate\Support\Facades\Route;

Route::prefix('business')
    ->name('business.')
    ->group(function () {
        Route::prefix('info')
            ->name('info.')
            ->group(function () {
                Route::get('/', [MerchantInfoController::class, 'index'])->name('detail');
                Route::put('/', [MerchantInfoController::class, 'save'])->name('detail.save');
                Route::post('/logo', [MerchantInfoController::class, 'saveLogo'])->name('detail.save.logo');
            });

        Route::resource('outlets', OutletController::class)->except(['edit']);

        Route::prefix('billing')
            ->name('billing.')
            ->group(function () {
                Route::get('/', [BillingController::class, 'index'])->name('index');
                Route::get('/plans', [BillingController::class, 'plans'])->name('plans');
                Route::get('/subscribe', [SubscribeController::class, 'index'])->name('subscribe');
                Route::post('/subscribe', [SubscribeController::class, 'store'])->name('subscribe.store');
            });

        Route::prefix('invoices')
            ->name('invoices.')
            ->group(function () {
                Route::get('/', [InvoiceController::class, 'index'])->name('index');
                Route::get('/{code}', [InvoiceController::class, 'show'])->name('show');
                Route::delete('/{code}/cancel', [InvoiceController::class, 'cancel'])->name('cancel');
                Route::get('/{code}/finish', [InvoiceController::class, 'finish'])->name('finish');
                Route::get('/{code}/error', [InvoiceController::class, 'error'])->name('error');
                // Route::get('/subscribe', [InvoiceController::class, 'index'])->name('subscribe');
            });

    });
