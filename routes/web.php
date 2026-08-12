<?php

use App\Helpers\SelectedOutlet;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OverviewController;
use App\Http\Controllers\User\ForgotPasswordController;
use App\Http\Controllers\User\LoginController;
use App\Http\Controllers\User\RegisterController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

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

Route::prefix('cockpit')
    ->name('cockpit.')
    ->group(function () {
        Route::middleware('guest:cockpit')->group(function () {
            Route::get('/login', [\App\Http\Controllers\Cockpit\AuthenticationController::class, 'index'])->name('login');
            Route::post('/login', [\App\Http\Controllers\Cockpit\AuthenticationController::class, 'store'])->name('login.attempt');
        });

        Route::middleware('auth:cockpit')->group(function () {
            Route::delete('/logout', [\App\Http\Controllers\Cockpit\AuthenticationController::class, 'destroy'])->name('logout');

            Route::get('/', [\App\Http\Controllers\Cockpit\DashboardController::class, 'index'])->name('dashboard');

            Route::get('/business', [\App\Http\Controllers\Cockpit\BusinessController::class, 'index'])->name('merchants.index');
            Route::post('/business/{id}/toggle-status', [\App\Http\Controllers\Cockpit\BusinessController::class, 'toggleStatus'])->name('merchants.toggle-status');
            Route::get('/business/{id}', [\App\Http\Controllers\Cockpit\BusinessController::class, 'show'])->name('merchants.show');

            Route::get('/subscriptions', [\App\Http\Controllers\Cockpit\SubscriptionController::class, 'index'])->name('subscriptions.index');
            Route::get('/subscriptions/{id}', [\App\Http\Controllers\Cockpit\SubscriptionController::class, 'show'])->name('subscriptions.show');

            Route::get('/invoices', [\App\Http\Controllers\Cockpit\InvoiceController::class, 'index'])->name('invoices.index');

            Route::get('/uoms', [\App\Http\Controllers\Cockpit\UomController::class, 'index'])->name('uoms.index');

            Route::get('/config', [\App\Http\Controllers\Cockpit\ConfigController::class, 'index'])->name('config.index');

            Route::get('/audit', [\App\Http\Controllers\Cockpit\AuditController::class, 'index'])->name('audit.index');
        });
    });

Route::middleware('auth:business')->group(function () {
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        /** @var <FormRequest> $request */
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
        Route::get('/inventory-items/search', [\App\Http\Controllers\API\InventoryItemController::class, 'search'])->name('inventory-items.search');
        Route::get('/inventory-items/partial', [\App\Http\Controllers\API\InventoryItemController::class, 'getPartialItems'])->name('inventory-items.partial');
        Route::get('/outlets', [\App\Http\Controllers\API\OutletController::class, 'index'])->name('outlets.index');
    });

    Route::get('/exports/download', [\App\Http\Controllers\ExportDownloadController::class, 'download'])->name('exports.download');

    Route::get('/', OverviewController::class)->name('overview');

    require __DIR__ . '/web/masters.php';
    require __DIR__ . '/web/inventories.php';
    require __DIR__ .'/web/employees.php';
    require __DIR__ .'/web/settings.php';
    require __DIR__ .'/web/transactions.php';

    Route::delete('/logout', [LoginController::class, 'destroy'])->name('logout');
});

/*
|--------------------------------------------------------------------------
| Development-Only Swagger API Docs Routes
|--------------------------------------------------------------------------
*/
Route::get('/docs/api', [\App\Http\Controllers\Docs\SwaggerController::class, 'index'])->name('docs.swagger');
Route::get('/docs/openapi.yaml', [\App\Http\Controllers\Docs\SwaggerController::class, 'yaml'])->name('docs.openapi');
