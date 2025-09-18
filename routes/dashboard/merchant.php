<?php

use App\Http\Controllers\Dashboard\Merchant\BillingController;
use App\Http\Controllers\Dashboard\Merchant\MerchantInfoController;
use App\Http\Controllers\Dashboard\Merchant\SubscribeController;
use Illuminate\Support\Facades\Route;

Route::prefix('merchant')
    ->name('merchant.')
    ->group(function () {
        Route::prefix('info')
            ->name('info.')
            ->group(function () {
                Route::get('/', [MerchantInfoController::class, 'index'])->name('detail');
                Route::put('/', [MerchantInfoController::class, 'save'])->name('detail.save');
                Route::post('/logo', [MerchantInfoController::class, 'saveLogo'])->name('detail.save.logo');
            });

        Route::prefix('billing')
            ->name('billing.')
            ->group(function () {
                Route::get('/', [BillingController::class, 'index'])->name('index');
                Route::get('/plans', [BillingController::class, 'plans'])->name('plans');
                Route::get('/subscribe', [SubscribeController::class, 'index'])->name('subscribe');
            });

    });
