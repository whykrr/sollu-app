<?php

use App\Http\Controllers\Settings\AccountController;
use App\Http\Controllers\Settings\BusinessInfoController;
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
                Route::delete('/{outlet}/destroy', [OutletController::class, 'destroy'])->name('destroy');
                Route::put('/{outlet}/restore', [OutletController::class, 'restore'])->name('restore');

                Route::prefix('{outlet}')->group(function () {
                    Route::put('settings', [\App\Http\Controllers\Settings\OutletSettingController::class, 'update'])->name('settings.update');
                    Route::put('operational-hours', [\App\Http\Controllers\Settings\OutletOperationalHourController::class, 'update'])->name('operational-hours.update');

                    Route::post('devices', [\App\Http\Controllers\Settings\OutletDeviceController::class, 'store'])->name('devices.store');
                    Route::post('devices/{device}/generate-otp', [\App\Http\Controllers\Settings\OutletDeviceController::class, 'generateOtp'])->name('devices.generate-otp');
                    Route::post('devices/{device}/unpair', [\App\Http\Controllers\Settings\OutletDeviceController::class, 'unpair'])->name('devices.unpair');
                    Route::put('devices/{device}', [\App\Http\Controllers\Settings\OutletDeviceController::class, 'update'])->name('devices.update');
                    Route::delete('devices/{device}', [\App\Http\Controllers\Settings\OutletDeviceController::class, 'destroy'])->name('devices.destroy');
                });
            });

        Route::middleware(['can:'.\App\Enums\PermissionEnum::BUSINESS_BILLING->value])->group(function () {
            Route::prefix('billing')
                ->name('billing.')
                ->group(function () {
                    Route::get('/', [\App\Http\Controllers\Settings\BillingController::class, 'index'])->name('index');
                    Route::get('/plans', [\App\Http\Controllers\Settings\BillingController::class, 'plans'])->name('plans');
                    Route::get('/checkout/{plan_id}', [\App\Http\Controllers\Settings\BillingController::class, 'checkout'])->name('checkout');

                    Route::prefix('invoices')
                        ->name('invoices.')
                        ->group(function () {
                            Route::get('/{invoice_number}', [\App\Http\Controllers\Settings\InvoiceController::class, 'show'])->name('show');
                            Route::get('/{invoice_number}/download', [\App\Http\Controllers\Settings\InvoiceController::class, 'download'])->name('download');
                            Route::get('/{invoice_number}/finish', [\App\Http\Controllers\Settings\InvoiceController::class, 'finish'])->name('finish');
                            Route::get('/{invoice_number}/error', [\App\Http\Controllers\Settings\InvoiceController::class, 'error'])->name('error');
                            Route::delete('/{invoice_number}/cancel', [\App\Http\Controllers\Settings\InvoiceController::class, 'cancel'])->name('cancel');
                            Route::post('/{invoice_number}/change-method', [\App\Http\Controllers\Settings\InvoiceController::class, 'changeMethod'])->name('change-method');
                            Route::post('/{invoice_number}/upload-proof', [\App\Http\Controllers\Settings\InvoiceController::class, 'uploadProof'])->name('upload-proof');
                        });
                });
        });

        Route::middleware(['can:'.\App\Enums\PermissionEnum::BUSINESS_SUBSCRIPTION->value])->group(function () {
            Route::prefix('subscriptions')
                ->name('subscriptions.')
                ->group(function () {
                    Route::post('/subscribe', [\App\Http\Controllers\Settings\SubscriptionController::class, 'subscribe'])->name('subscribe');
                    Route::post('/change-plan', [\App\Http\Controllers\Settings\SubscriptionController::class, 'changePlan'])->name('change-plan');
                    Route::delete('/cancel', [\App\Http\Controllers\Settings\SubscriptionController::class, 'cancel'])->name('cancel');
                });
        });

    });
