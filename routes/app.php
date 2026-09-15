<?php

use App\Helpers\SelectedOutlet;
use App\Http\Controllers\App\Notification\NotificationController;
use App\Http\Controllers\App\Overview\OverviewController;
use App\Http\Controllers\App\User\ForgotPasswordController;
use App\Http\Controllers\App\User\ImpersonateController;
use App\Http\Controllers\App\User\LoginController;
use App\Http\Controllers\App\User\RegisterController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

Route::get('/csrf-token', [\App\Http\Controllers\Support\CsrfTokenController::class, 'show'])->name('csrf.token');
Route::get('/impersonate/{token}', [ImpersonateController::class, 'authenticate'])->name('impersonate.authenticate');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'index'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.attempt')->middleware('throttle:login');

    Route::get('/forgot', [ForgotPasswordController::class, 'index'])->name('forgot');
    Route::post('/forgot', [ForgotPasswordController::class, 'sendEmailReset'])->name('forgot.email')->middleware('throttle:5,5');
    Route::get('/reset-password', [ForgotPasswordController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [ForgotPasswordController::class, 'doReset'])->name('password.reset.attempt');

    Route::get('/register', [RegisterController::class, 'index'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store')->middleware('throttle:5,5');
});

Route::middleware('auth:business')->group(function () {
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        /** @var FormRequest $request */
        Cache::forgetPattern("auth:user:{$request->user()->id}:*");
        $request->fulfill();

        return redirect()->route('overview')->with('success', 'Email berhasil di verifikasi!');
    })->middleware(['signed'])->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Link verifikasi telah dikirim ulang!');
    })->middleware(['throttle:6,5'])->name('verification.send');

    Route::prefix('switch-outlet')->name('switch.')->group(function () {
        Route::get('/dashboard', function () {
            return inertia('Dashboard');
        })->name('dashboard');

        Route::post('/all', function () {
            SelectedOutlet::make()->all();

            return back();
        })->name('all');
        Route::post('/{id}', function (Request $request, $id) {
            SelectedOutlet::make()->change($id);

            return back();
        })->where('id', '[0-9a-fA-F\-]{36}')->name('outlet');
    });

    // Internal APIs
    Route::prefix('api/internal')->name('api.internal.')->group(function () {
        Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
        Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.markAsRead');
        Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
        Route::delete('/notifications/{id}', [NotificationController::class, 'destroy'])->name('notifications.destroy');

        // For reusable components that need to search for products or inventory items
        Route::get('/products/search', [\App\Http\Controllers\API\ProductController::class, 'search'])->name('products.search');
        Route::get('/products/search-by-inventory', [\App\Http\Controllers\API\ProductController::class, 'searchByInventoryItem'])->name('products.search-by-inventory');
        Route::get('/inventory-items/search', [\App\Http\Controllers\API\InventoryItemController::class, 'search'])->name('inventory-items.search');
        Route::get('/inventory-items/partial', [\App\Http\Controllers\API\InventoryItemController::class, 'getPartialItems'])->name('inventory-items.partial');
        Route::get('/outlets', [\App\Http\Controllers\API\OutletController::class, 'index'])->name('outlets.index');
        Route::get('/customers/search', [\App\Http\Controllers\API\CustomerController::class, 'search'])->name('customers.search');
        Route::get('/promos/search', [\App\Http\Controllers\API\PromoController::class, 'search'])->name('promos.search');
        Route::get('/payment-methods', [\App\Http\Controllers\API\PaymentMethodController::class, 'index'])->name('payment-methods.index');
        Route::get('/business-info', [\App\Http\Controllers\API\BusinessInfoController::class, 'index'])->name('business-info');
    });

    Route::get('/exports/download', [App\Http\Controllers\App\Reports\ExportDownloadController::class, 'download'])->name('exports.download');

    Route::get('/', OverviewController::class)->name('overview');

    require __DIR__.'/app/masters.php';
    require __DIR__.'/app/inventories.php';
    require __DIR__.'/app/employees.php';
    require __DIR__.'/app/settings.php';
    require __DIR__.'/app/transactions.php';
    require __DIR__.'/app/customers.php';
    require __DIR__.'/app/promotions.php';
    require __DIR__.'/app/reports.php';

    Route::delete('/logout', [LoginController::class, 'destroy'])->name('logout');
});

if (app()->environment('local', 'development')) {
    Route::get('/bypass-auth', function () {
        $user = \App\Models\User::first();
        if ($user) {
            \Illuminate\Support\Facades\Auth::login($user);
        }

        return redirect('/');
    })->name('bypass.auth');
}

/*
|--------------------------------------------------------------------------
| Legacy API Route Bridge (Backward Compatibility)
|--------------------------------------------------------------------------
|
| Provides seamless fallback for legacy POS apps and webhooks sending
| requests to http://app.sollu.test/api/pos/... or http://app.sollu.test/api/midtrans/...
|
*/
Route::prefix('api')->middleware(['api', \App\Http\Middleware\AttachApiDeprecationHeader::class])->group(function () {
    Route::get('/health', [\App\Http\Controllers\API\HealthCheckController::class, 'index'])->name('legacy.api.health');

    Route::post('midtrans/notification', \App\Http\Controllers\API\Midtrans\NotificationController::class)
        ->name('legacy.midtrans.notification');

    Route::prefix('pos')->name('legacy.api.pos.')->group(function () {
        Route::post('/device/connect', [\App\Http\Controllers\API\POS\DeviceController::class, 'connect'])
            ->name('legacy.device.connect');

        Route::middleware(['auth:sanctum', 'pos.device'])->group(function () {
            Route::get('/device/status', [\App\Http\Controllers\API\POS\DeviceController::class, 'checkStatus'])->name('legacy.device.status');
            Route::get('/sync/master', [\App\Http\Controllers\API\POS\SyncController::class, 'masterData'])->name('legacy.sync.master');
            Route::get('/employees', [\App\Http\Controllers\API\POS\EmployeeController::class, 'index'])->name('legacy.employees.index');
            Route::put('/employees/pin', [\App\Http\Controllers\API\POS\EmployeeController::class, 'updatePin'])->name('legacy.employees.pin.update');
            Route::post('/transactions', [\App\Http\Controllers\API\POS\TransactionController::class, 'store'])->name('legacy.transactions.store');

            Route::prefix('shifts')->name('legacy.shifts.')->group(function () {
                Route::post('/sync', [\App\Http\Controllers\API\POS\ShiftController::class, 'sync'])->name('legacy.sync');
                Route::post('/open', [\App\Http\Controllers\API\POS\ShiftController::class, 'open'])->name('legacy.open');
                Route::post('/close', [\App\Http\Controllers\API\POS\ShiftController::class, 'close'])->name('legacy.close');
                Route::post('/cash-log', [\App\Http\Controllers\API\POS\ShiftController::class, 'cashLog'])->name('legacy.cash-log');
            });

            Route::put('/settings/printer', [\App\Http\Controllers\API\POS\SettingController::class, 'updatePrinter'])->name('legacy.settings.printer.update');
            Route::post('/logs/error', [\App\Http\Controllers\API\POS\LogController::class, 'error'])->name('legacy.logs.error');
        });
    });
});
Route::get('/test-flash', function () {
    return redirect()->route('overview')->with('feature_locked', 'promo_management');
});
