<?php

use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Http\Controllers\App\Settings\AccountController;
use App\Http\Controllers\App\Settings\ActivityLogController;
use App\Http\Controllers\App\Settings\BillingController;
use App\Http\Controllers\App\Settings\BusinessInfoController;
use App\Http\Controllers\App\Settings\DeviceSettingController;
use App\Http\Controllers\App\Settings\FeatureSettingController;
use App\Http\Controllers\App\Settings\InventorySettingController;
use App\Http\Controllers\App\Settings\InvoiceController;
use App\Http\Controllers\App\Settings\OperationalSettingController;
use App\Http\Controllers\App\Settings\OutletController;
use App\Http\Controllers\App\Settings\OutletDeviceController;
use App\Http\Controllers\App\Settings\OutletOperationalHourController;
use App\Http\Controllers\App\Settings\OutletSettingController;
use App\Http\Controllers\App\Settings\PaymentMethodController;
use App\Http\Controllers\App\Settings\ReceiptSettingController;
use App\Http\Controllers\App\Settings\RoleController;
use App\Http\Controllers\App\Settings\SalesSettingController;
use App\Http\Controllers\App\Settings\SubscriptionController;
use App\Http\Controllers\App\Settings\TaxSettingController;
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

                Route::get('/features', [FeatureSettingController::class, 'index'])->name('features');
                Route::put('/features', [FeatureSettingController::class, 'save'])->name('features.save');
            });

        Route::prefix('inventory')
            ->name('inventory.')
            ->group(function () {
                Route::get('/', [InventorySettingController::class, 'index'])->name('index');
                Route::put('/', [InventorySettingController::class, 'update'])->name('update');
                Route::put('/sod', [InventorySettingController::class, 'updateSod'])->name('sod.update');
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
                    Route::put('settings', [OutletSettingController::class, 'update'])->name('settings.update');
                    Route::put('operational-hours', [OutletOperationalHourController::class, 'update'])->name('operational-hours.update');

                    Route::post('devices', [OutletDeviceController::class, 'store'])->name('devices.store');
                    Route::post('devices/{device}/generate-otp', [OutletDeviceController::class, 'generateOtp'])->name('devices.generate-otp');
                    Route::post('devices/{device}/unpair', [OutletDeviceController::class, 'unpair'])->name('devices.unpair');
                    Route::put('devices/{device}', [OutletDeviceController::class, 'update'])->name('devices.update');
                    Route::delete('devices/{device}', [OutletDeviceController::class, 'destroy'])->name('devices.destroy');
                });
            });

        Route::middleware([
            'can:'.PermissionEnum::ROLE_VIEW->value,
            'plan.feature:'.FeatureEnum::CUSTOM_ROLE->value,
        ])->group(function () {
            Route::post('roles/template', [RoleController::class, 'storeTemplate'])->name('roles.template');
            Route::resource('roles', RoleController::class)->except(['create', 'edit']);
        });

        Route::middleware(['can:'.PermissionEnum::BUSINESS_BILLING->value])->group(function () {
            Route::prefix('billing')
                ->name('billing.')
                ->group(function () {
                    Route::get('/', [BillingController::class, 'index'])->name('index');
                    Route::get('/plans', [BillingController::class, 'plans'])->name('plans');
                    Route::get('/checkout/{plan_id}', [BillingController::class, 'checkout'])->name('checkout');

                    Route::prefix('invoices')
                        ->name('invoices.')
                        ->group(function () {
                            Route::get('/{invoice_number}', [InvoiceController::class, 'show'])->name('show');
                            Route::get('/{invoice_number}/download', [InvoiceController::class, 'download'])->name('download');
                            Route::get('/{invoice_number}/finish', [InvoiceController::class, 'finish'])->name('finish');
                            Route::get('/{invoice_number}/error', [InvoiceController::class, 'error'])->name('error');
                            Route::delete('/{invoice_number}/cancel', [InvoiceController::class, 'cancel'])->name('cancel');
                            Route::post('/{invoice_number}/change-method', [InvoiceController::class, 'changeMethod'])->name('change-method');
                            Route::post('/{invoice_number}/upload-proof', [InvoiceController::class, 'uploadProof'])->name('upload-proof');
                        });
                });
        });

        Route::middleware(['can:'.PermissionEnum::BUSINESS_SUBSCRIPTION->value])->group(function () {
            Route::prefix('subscriptions')
                ->name('subscriptions.')
                ->group(function () {
                    Route::post('/subscribe', [SubscriptionController::class, 'subscribe'])->name('subscribe');
                    Route::post('/change-plan', [SubscriptionController::class, 'changePlan'])->name('change-plan');
                    Route::post('/renew', [SubscriptionController::class, 'renew'])->name('renew');
                    Route::delete('/cancel', [SubscriptionController::class, 'cancel'])->name('cancel');
                });
        });

        Route::middleware([
            'can:'.PermissionEnum::SETTING_SALES->value,
            'plan.feature:'.FeatureEnum::INVOICE_DEBT->value,
        ])
            ->prefix('sales')
            ->name('sales.')
            ->group(function () {
                Route::get('/', [SalesSettingController::class, 'index'])->name('index');
                Route::put('/', [SalesSettingController::class, 'update'])->name('update');
            });

        Route::middleware(['can:'.PermissionEnum::SETTING_PAYMENT->value])->group(function () {
            Route::middleware('plan.feature:'.FeatureEnum::OUTLET_MANAGEMENT->value)
                ->prefix('payment-methods')
                ->name('payment-methods.')
                ->group(function () {
                    Route::get('/', [PaymentMethodController::class, 'index'])->name('index');
                    Route::post('/', [PaymentMethodController::class, 'store'])->name('store');
                    Route::patch('/reorder', [PaymentMethodController::class, 'reorder'])->name('reorder');
                    Route::put('/{paymentMethod}', [PaymentMethodController::class, 'update'])->name('update');
                    Route::patch('/{paymentMethod}/toggle-outlet/{outlet}', [PaymentMethodController::class, 'toggleOutlet'])->name('toggle-outlet');
                    Route::delete('/{paymentMethod}', [PaymentMethodController::class, 'destroy'])->name('destroy');
                });
        });

        Route::middleware('plan.feature:'.FeatureEnum::OUTLET_MANAGEMENT->value)
            ->prefix('receipt')
            ->name('receipt.')
            ->group(function () {
                Route::get('/', [ReceiptSettingController::class, 'index'])->name('index');
                Route::put('/', [ReceiptSettingController::class, 'update'])->name('update');
            });

        Route::middleware('plan.feature:'.FeatureEnum::OUTLET_MANAGEMENT->value)
            ->prefix('devices')
            ->name('devices.')
            ->group(function () {
                Route::get('/', [DeviceSettingController::class, 'index'])->name('index');
                Route::post('/', [DeviceSettingController::class, 'store'])->name('store');
                Route::put('/{device}', [DeviceSettingController::class, 'update'])->name('update');
                Route::delete('/{device}', [DeviceSettingController::class, 'destroy'])->name('destroy');
                Route::post('/{device}/generate-otp', [DeviceSettingController::class, 'generateOtp'])->name('generate-otp');
                Route::post('/{device}/unpair', [DeviceSettingController::class, 'unpair'])->name('unpair');
            });

        Route::middleware('plan.feature:'.FeatureEnum::OUTLET_MANAGEMENT->value)
            ->prefix('taxes')
            ->name('taxes.')
            ->group(function () {
                Route::get('/', [TaxSettingController::class, 'index'])->name('index');
                Route::put('/', [TaxSettingController::class, 'update'])->name('update');
            });

        Route::middleware('plan.feature:'.FeatureEnum::OUTLET_MANAGEMENT->value)
            ->prefix('operational')
            ->name('operational.')
            ->group(function () {
                Route::get('/', [OperationalSettingController::class, 'index'])->name('index');
                Route::put('/', [OperationalSettingController::class, 'update'])->name('update');
            });

        Route::middleware([
            'can:'.PermissionEnum::SETTING_AUDIT->value,
            'plan.feature:'.FeatureEnum::AUDIT_LOGS->value,
        ])
            ->prefix('activity-logs')
            ->name('activity-logs.')
            ->group(function () {
                Route::get('/', [ActivityLogController::class, 'index'])->name('index');
                Route::get('/{activityLog}', [ActivityLogController::class, 'show'])->name('show');
            });
    });
