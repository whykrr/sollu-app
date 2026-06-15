<?php

use App\Http\Controllers\Settings\AccountController;
use App\Http\Controllers\Settings\BillingController;
use App\Http\Controllers\Settings\BusinessInfoController;
use App\Http\Controllers\Settings\InvoiceController;
use App\Http\Controllers\Settings\OutletController;
use Illuminate\Support\Facades\Route;

Route::prefix('settings')
    ->name('settings.')
    ->group(function () {
        Route::prefix('account')
            ->name('account.')
            ->group(function () {
                Route::get('/', [AccountController::class, 'index'])->name('profile');
                Route::put('/', [AccountController::class, 'save'])->name('profile.save');
                Route::put('/password', [AccountController::class, 'changePassword'])->name('profile.save.password');
                Route::post('/photo', [AccountController::class, 'savePhoto'])->name('profile.save.photo');
                Route::delete('/photo', [AccountController::class, 'removePhoto'])->name('profile.destroy.photo');
            });

        Route::prefix('business')
            ->name('business.')
            ->group(function () {
                Route::get('/', [BusinessInfoController::class, 'index'])->name('detail');
                Route::put('/', [BusinessInfoController::class, 'save'])->name('detail.save');
                Route::post('/logo', [BusinessInfoController::class, 'saveLogo'])->name('detail.save.logo');
            });

        Route::prefix('outlets')
            ->name('outlets.')
            ->group(function () {
                Route::get('/', [OutletController::class, 'index'])->name('index');
                Route::get('/{outlet}', [OutletController::class, 'index'])->name('show');
                Route::post('/', [OutletController::class, 'store'])->name('store');
                Route::put('/{outlet}', [OutletController::class, 'update'])->name('update');
                Route::put('/{outlet}/enabled', [OutletController::class, 'enabled'])->name('enabled');
                Route::delete('/{outlet}', [OutletController::class, 'disabled'])->name('disabled');
            });


        Route::prefix('billing')
            ->name('billing.')
            ->group(function () {
                Route::get('/', [BillingController::class, 'index'])->name('index');
                Route::get('/{id}', [InvoiceController::class, 'show'])->name('show');
                Route::get('/{id}', [InvoiceController::class, 'index`'])->name('show');
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
