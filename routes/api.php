<?php

use App\Http\Controllers\API\HealthCheckController;
use App\Http\Controllers\API\Midtrans\NotificationController;
use App\Http\Controllers\API\V1\POS\DeviceController;
use App\Http\Controllers\API\V1\POS\EmployeeController;
use App\Http\Controllers\API\V1\POS\LogController;
use App\Http\Controllers\API\V1\POS\SettingController;
use App\Http\Controllers\API\V1\POS\ShiftController;
use App\Http\Controllers\API\V1\POS\SyncController;
use App\Http\Controllers\API\V1\POS\TransactionController;
use App\Http\Controllers\Docs\SwaggerController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| System & Root API Endpoints
|--------------------------------------------------------------------------
*/
Route::get('/health', [HealthCheckController::class, 'index'])->name('api.health');

// Webhook compatibility
Route::post('midtrans/notification', NotificationController::class)->name('midtrans.notification');

/*
|--------------------------------------------------------------------------
| Versioned API Routes (Canonical)
|--------------------------------------------------------------------------
|
| Main canonical routes versioned under /v1 prefix.
| Protected by AttachApiVersionHeader middleware.
|
*/
Route::prefix('v1')
    ->name('api.v1.')
    ->middleware(['api.version:v1'])
    ->group(base_path('routes/api/v1.php'));

/*
|--------------------------------------------------------------------------
| Unversioned Fallback API Routes (Deprecated)
|--------------------------------------------------------------------------
|
| Fallback alias for older POS clients hitting unversioned /pos/* paths.
| Emits RFC 8594 Deprecation & Sunset headers and maps to V1 controllers.
|
*/
Route::prefix('pos')
    ->name('api.pos.')
    ->middleware(['api.deprecation', 'api.version:v1'])
    ->group(function () {
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
