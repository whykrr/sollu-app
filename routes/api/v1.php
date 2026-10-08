<?php

use App\Http\Controllers\API\V1\Midtrans\NotificationController;
use App\Http\Controllers\API\V1\POS\DeviceController;
use App\Http\Controllers\API\V1\POS\EmployeeController;
use App\Http\Controllers\API\V1\POS\LogController;
use App\Http\Controllers\API\V1\POS\SettingController;
use App\Http\Controllers\API\V1\POS\ShiftController;
use App\Http\Controllers\API\V1\POS\SyncController;
use App\Http\Controllers\API\V1\POS\TransactionController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Version 1 (V1) Routes
|--------------------------------------------------------------------------
|
| Canonical versioned routes under /v1 prefix.
| Protected by AttachApiVersionHeader middleware.
|
*/

// Webhooks
Route::post('webhooks/midtrans', NotificationController::class)->name('webhooks.midtrans');

// POS Endpoints
Route::prefix('pos')->name('pos.')->group(function () {
    // Device Pairing (Public)
    Route::post('/device/connect', [DeviceController::class, 'connect'])->name('device.connect');

    // Protected POS Device Routes
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
