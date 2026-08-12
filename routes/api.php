<?php

use App\Http\Controllers\API\Midtrans\NotificationController;
use Illuminate\Support\Facades\Route;

Route::post('midtrans/notification', NotificationController::class)->name('midtrans.notification');

Route::prefix('pos')->name('api.pos.')->group(function () {
    // Device Pairing
    Route::post('/device/connect', [\App\Http\Controllers\API\POS\DeviceController::class, 'connect'])->name('device.connect');
    
    Route::middleware(['auth:sanctum', 'pos.device'])->group(function () {
        Route::get('/device/status', [\App\Http\Controllers\API\POS\DeviceController::class, 'checkStatus'])->name('device.status');
        
        Route::get('/sync/master', [\App\Http\Controllers\API\POS\SyncController::class, 'masterData'])->name('sync.master');
        
        Route::get('/employees', [\App\Http\Controllers\API\POS\EmployeeController::class, 'index'])->name('employees.index');
        
        Route::post('/transactions', [\App\Http\Controllers\API\POS\TransactionController::class, 'store'])->name('transactions.store');
        
        Route::prefix('shifts')->name('shifts.')->group(function () {
            Route::post('/open', [\App\Http\Controllers\API\POS\ShiftController::class, 'open'])->name('open');
            Route::post('/close', [\App\Http\Controllers\API\POS\ShiftController::class, 'close'])->name('close');
            Route::post('/cash-log', [\App\Http\Controllers\API\POS\ShiftController::class, 'cashLog'])->name('cash-log');
        });
    });
});
