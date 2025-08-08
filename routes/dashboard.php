<?php

use App\Http\Controllers\Dashboard\EmployeeController;
use App\Http\Controllers\Dashboard\OverviewController;
use App\Http\Controllers\Dashboard\User\ForgotPasswordController;
use App\Http\Controllers\Dashboard\User\LoginController;
use App\Http\Controllers\Dashboard\User\RegisterController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'index'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.attempt')->middleware('throttle:login');

    Route::get('/forgot', [ForgotPasswordController::class, 'index'])->name('forgot');
    Route::post('/forgot', [ForgotPasswordController::class, 'sendEmailReset'])->name('forgot.email')->middleware('throttle:6,5');
    Route::get('/reset-password', [ForgotPasswordController::class, 'reset'])->name('password.reset');
    Route::post('/reset-password', [ForgotPasswordController::class, 'doReset'])->name('password.reset.attempt');

    Route::get('/register', [RegisterController::class, 'index'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
});

Route::middleware('auth:merchant')->group(function () {
    Route::get('/email/verify/{id}/{hash}', function (EmailVerificationRequest $request) {
        $request->fulfill();

        return redirect()->route('dashboard.overview')->with('success', 'Email berhasil di verifikasi!');
    })->middleware(['signed'])->name('verification.verify');

    Route::post('/email/verification-notification', function (Request $request) {
        $request->user()->sendEmailVerificationNotification();

        return back()->with('success', 'Link verifikasi telah dikirim ulang!');
    })->middleware(['throttle:6,5'])->name('verification.send');

    Route::get('/', OverviewController::class)->name('overview');

    Route::resource('employees', EmployeeController::class)
    ->except('edit');
    Route::put('employees/{employee}/restore', [EmployeeController::class, 'restore'])
             ->name('employees.restore')
             ->withTrashed();
    Route::delete('employees/{employee}/purge', [EmployeeController::class, 'purge'])
        ->name('employees.purge')
        ->withTrashed();

    Route::delete('/logout', [LoginController::class, 'destroy'])->name('logout');
});
