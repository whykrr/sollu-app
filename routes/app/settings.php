<?php

use App\Enums\FeatureEnum;
use App\Http\Controllers\App\Settings\AccountController;
use App\Http\Controllers\App\Settings\BusinessInfoController;
use App\Http\Controllers\App\Settings\OutletController;
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

                Route::get('/features', [\App\Http\Controllers\App\Settings\FeatureSettingController::class, 'index'])->name('features');
                Route::put('/features', [\App\Http\Controllers\App\Settings\FeatureSettingController::class, 'save'])->name('features.save');
            });

        Route::prefix('inventory')
            ->name('inventory.')
            ->group(function () {
                Route::get('/', [\App\Http\Controllers\App\Settings\InventorySettingController::class, 'index'])->name('index');
                Route::put('/', [\App\Http\Controllers\App\Settings\InventorySettingController::class, 'update'])->name('update');
                Route::put('/sod', [\App\Http\Controllers\App\Settings\InventorySettingController::class, 'updateSod'])->name('sod.update');
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
                Route::put('/{outlet}/set-main', [OutletController::class, 'setMain'])->name('set-main');

                Route::prefix('{outlet}')->group(function () {
                    Route::put('settings', [\App\Http\Controllers\App\Settings\OutletSettingController::class, 'update'])->name('settings.update');
                    Route::put('operational-hours', [\App\Http\Controllers\App\Settings\OutletOperationalHourController::class, 'update'])->name('operational-hours.update');

                    Route::post('devices', [\App\Http\Controllers\App\Settings\OutletDeviceController::class, 'store'])->name('devices.store');
                    Route::post('devices/{device}/generate-otp', [\App\Http\Controllers\App\Settings\OutletDeviceController::class, 'generateOtp'])->name('devices.generate-otp');
                    Route::post('devices/{device}/unpair', [\App\Http\Controllers\App\Settings\OutletDeviceController::class, 'unpair'])->name('devices.unpair');
                    Route::put('devices/{device}', [\App\Http\Controllers\App\Settings\OutletDeviceController::class, 'update'])->name('devices.update');
                    Route::delete('devices/{device}', [\App\Http\Controllers\App\Settings\OutletDeviceController::class, 'destroy'])->name('devices.destroy');
                });
            });

        Route::middleware([
            'can:'.\App\Enums\PermissionEnum::ROLE_VIEW->value,
            'plan.feature:'.FeatureEnum::CUSTOM_ROLE->value,
        ])->group(function () {
            Route::post('roles/template', [\App\Http\Controllers\App\Settings\RoleController::class, 'storeTemplate'])->name('roles.template');
            Route::resource('roles', \App\Http\Controllers\App\Settings\RoleController::class)->except(['create', 'edit']);
        });

        Route::middleware(['can:'.\App\Enums\PermissionEnum::BUSINESS_BILLING->value])->group(function () {
            Route::prefix('billing')
                ->name('billing.')
                ->group(function () {
                    Route::get('/', [\App\Http\Controllers\App\Settings\BillingController::class, 'index'])->name('index');
                    Route::get('/plans', [\App\Http\Controllers\App\Settings\BillingController::class, 'plans'])->name('plans');
                    Route::get('/checkout/{plan_id}', [\App\Http\Controllers\App\Settings\BillingController::class, 'checkout'])->name('checkout');

                    Route::prefix('invoices')
                        ->name('invoices.')
                        ->group(function () {
                            Route::get('/{invoice_number}', [\App\Http\Controllers\App\Settings\InvoiceController::class, 'show'])->name('show');
                            Route::get('/{invoice_number}/download', [\App\Http\Controllers\App\Settings\InvoiceController::class, 'download'])->name('download');
                            Route::get('/{invoice_number}/finish', [\App\Http\Controllers\App\Settings\InvoiceController::class, 'finish'])->name('finish');
                            Route::get('/{invoice_number}/error', [\App\Http\Controllers\App\Settings\InvoiceController::class, 'error'])->name('error');
                            Route::delete('/{invoice_number}/cancel', [\App\Http\Controllers\App\Settings\InvoiceController::class, 'cancel'])->name('cancel');
                            Route::post('/{invoice_number}/change-method', [\App\Http\Controllers\App\Settings\InvoiceController::class, 'changeMethod'])->name('change-method');
                            Route::post('/{invoice_number}/upload-proof', [\App\Http\Controllers\App\Settings\InvoiceController::class, 'uploadProof'])->name('upload-proof');
                        });
                });
        });

        Route::middleware(['can:'.\App\Enums\PermissionEnum::BUSINESS_SUBSCRIPTION->value])->group(function () {
            Route::prefix('subscriptions')
                ->name('subscriptions.')
                ->group(function () {
                    Route::post('/subscribe', [\App\Http\Controllers\App\Settings\SubscriptionController::class, 'subscribe'])->name('subscribe');
                    Route::post('/change-plan', [\App\Http\Controllers\App\Settings\SubscriptionController::class, 'changePlan'])->name('change-plan');
                    Route::post('/renew', [\App\Http\Controllers\App\Settings\SubscriptionController::class, 'renew'])->name('renew');
                    Route::delete('/cancel', [\App\Http\Controllers\App\Settings\SubscriptionController::class, 'cancel'])->name('cancel');
                });
        });

        Route::middleware(['can:'.\App\Enums\PermissionEnum::SETTING_PAYMENT->value])->group(function () {
            Route::middleware('plan.feature:'.FeatureEnum::OUTLET_MANAGEMENT->value)
                ->prefix('payment-methods')
                ->name('payment-methods.')
                ->group(function () {
                    Route::get('/', [\App\Http\Controllers\App\Settings\PaymentMethodController::class, 'index'])->name('index');
                    Route::post('/', [\App\Http\Controllers\App\Settings\PaymentMethodController::class, 'store'])->name('store');
                    Route::patch('/reorder', [\App\Http\Controllers\App\Settings\PaymentMethodController::class, 'reorder'])->name('reorder');
                    Route::put('/{paymentMethod}', [\App\Http\Controllers\App\Settings\PaymentMethodController::class, 'update'])->name('update');
                    Route::patch('/{paymentMethod}/toggle-outlet/{outlet}', [\App\Http\Controllers\App\Settings\PaymentMethodController::class, 'toggleOutlet'])->name('toggle-outlet');
                    Route::delete('/{paymentMethod}', [\App\Http\Controllers\App\Settings\PaymentMethodController::class, 'destroy'])->name('destroy');
                });
        });

        Route::middleware('plan.feature:'.FeatureEnum::OUTLET_MANAGEMENT->value)
            ->prefix('receipt')
            ->name('receipt.')
            ->group(function () {
                Route::get('/', [\App\Http\Controllers\App\Settings\ReceiptSettingController::class, 'index'])->name('index');
                Route::put('/', [\App\Http\Controllers\App\Settings\ReceiptSettingController::class, 'update'])->name('update');
            });

        Route::middleware('plan.feature:'.FeatureEnum::OUTLET_MANAGEMENT->value)
            ->prefix('devices')
            ->name('devices.')
            ->group(function () {
                Route::get('/', [\App\Http\Controllers\App\Settings\DeviceSettingController::class, 'index'])->name('index');
                Route::post('/', [\App\Http\Controllers\App\Settings\DeviceSettingController::class, 'store'])->name('store');
                Route::put('/{device}', [\App\Http\Controllers\App\Settings\DeviceSettingController::class, 'update'])->name('update');
                Route::delete('/{device}', [\App\Http\Controllers\App\Settings\DeviceSettingController::class, 'destroy'])->name('destroy');
                Route::post('/{device}/generate-otp', [\App\Http\Controllers\App\Settings\DeviceSettingController::class, 'generateOtp'])->name('generate-otp');
                Route::post('/{device}/unpair', [\App\Http\Controllers\App\Settings\DeviceSettingController::class, 'unpair'])->name('unpair');
            });

        Route::middleware('plan.feature:'.FeatureEnum::OUTLET_MANAGEMENT->value)
            ->prefix('taxes')
            ->name('taxes.')
            ->group(function () {
                Route::get('/', [\App\Http\Controllers\App\Settings\TaxSettingController::class, 'index'])->name('index');
                Route::put('/', [\App\Http\Controllers\App\Settings\TaxSettingController::class, 'update'])->name('update');
            });

        Route::middleware('plan.feature:'.FeatureEnum::OUTLET_MANAGEMENT->value)
            ->prefix('operational')
            ->name('operational.')
            ->group(function () {
                Route::get('/', [\App\Http\Controllers\App\Settings\OperationalSettingController::class, 'index'])->name('index');
                Route::put('/', [\App\Http\Controllers\App\Settings\OperationalSettingController::class, 'update'])->name('update');
            });
    });
