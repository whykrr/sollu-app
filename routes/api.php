<?php

use App\Http\Controllers\API\HealthCheckController;
use App\Http\Controllers\API\Midtrans\NotificationController;
use App\Http\Controllers\API\POS\DeviceController;
use App\Http\Controllers\API\POS\EmployeeController;
use App\Http\Controllers\API\POS\LogController;
use App\Http\Controllers\API\POS\SettingController;
use App\Http\Controllers\API\POS\ShiftController;
use App\Http\Controllers\API\POS\SyncController;
use App\Http\Controllers\API\POS\TransactionController;
use App\Http\Controllers\Docs\SwaggerController;
use Illuminate\Support\Facades\Route;

Route::get('/health', [HealthCheckController::class, 'index'])->name('api.health');

Route::post('midtrans/notification', NotificationController::class)->name('midtrans.notification');

Route::prefix('pos')->name('api.pos.')->group(function () {
    // Device Pairing
    Route::post('/device/connect', [DeviceController::class, 'connect'])->name('device.connect');

    Route::middleware(['auth:sanctum', 'pos.device'])->group(function () {
        Route::get('/device/status', [DeviceController::class, 'checkStatus'])->name('device.status');
        Route::post('/device/unpair', [DeviceController::class, 'unpair'])->name('device.unpair');

        Route::get('/sync/master', [SyncController::class, 'masterData'])->name('sync.master');

        Route::get('/employees', [EmployeeController::class, 'index'])->name('employees.index');
        Route::put('/employees/pin', [EmployeeController::class, 'updatePin'])->name('employees.pin.update');

        Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');

        Route::prefix('shifts')->name('shifts.')->group(function () {
            Route::post('/sync', [ShiftController::class, 'sync'])->name('sync');
            Route::post('/open', [ShiftController::class, 'open'])->name('open');
            Route::post('/close', [ShiftController::class, 'close'])->name('close');
            Route::post('/cash-log', [ShiftController::class, 'cashLog'])->name('cash-log');
        });

        Route::put('/settings/printer', [SettingController::class, 'updatePrinter'])->name('settings.printer.update');

        Route::post('/logs/error', [LogController::class, 'error'])->name('logs.error');
    });
});

/*
|--------------------------------------------------------------------------
| Development-Only Swagger API Docs Routes
|--------------------------------------------------------------------------
*/
Route::get('/docs', [SwaggerController::class, 'index'])->name('docs.swagger');
Route::get('/docs/openapi.yaml', [SwaggerController::class, 'yaml'])->name('docs.openapi');
