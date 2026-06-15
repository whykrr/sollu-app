<?php

use App\Helpers\SelectedOutlet;
use App\Http\Controllers\OverviewController;
use App\Http\Controllers\User\ForgotPasswordController;
use App\Http\Controllers\User\LoginController;
use App\Http\Controllers\User\RegisterController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/cockpit', fn () => Inertia::render('Cockpit/Index'))->name('cockpit.index');

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
        Route::post('/all', function () {
            SelectedOutlet::make()->all();

            return back();
        })->name('all');
        Route::post('/{id}', function (Request $request, $id) {
            SelectedOutlet::make()->change($id);

            return back();
        })->where('id', '[0-9a-fA-F\-]{36}')->name('outlet');
    });

    Route::get('/', OverviewController::class)->name('overview');

    // require __DIR__ . '/web/products.php';
    require __DIR__ .'/web/employees.php';
    require __DIR__ .'/web/settings.php';
    require __DIR__ .'/web/template.php';

    Route::delete('/logout', [LoginController::class, 'destroy'])->name('logout');


    Route::get('/preview-mail', function () {
        $invoice = \App\Models\SubscriptionInvoice::first(); // contoh data
        // return new InvoiceMail($invoice);        // akan render Blade view email
    });
});
