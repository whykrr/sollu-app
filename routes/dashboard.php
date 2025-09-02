<?php

use App\Helpers\SelectedOutlet;
use App\Http\Controllers\Dashboard\EmployeeController;
use App\Http\Controllers\Dashboard\OverviewController;
use App\Http\Controllers\Dashboard\User\ForgotPasswordController;
use App\Http\Controllers\Dashboard\User\LoginController;
use App\Http\Controllers\Dashboard\User\RegisterController;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
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
        /** @var <FormRequest> $request */
        Cache::forgetPattern("auth:user:{$request->user()->id}:*");
        $request->fulfill();

        return redirect()->route('dashboard.overview')->with('success', 'Email berhasil di verifikasi!');
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

    Route::resource('employees', EmployeeController::class)
        ->except(['edit', 'show'])
        ->parameters([
            'employees' => 'user',
        ]);

    Route::resource('employees', EmployeeController::class)
        ->only('show')
        ->parameters([
            'employees' => 'user',
        ])->withTrashed();

    Route::put('employees/{user}/restore', [EmployeeController::class, 'restore'])
        ->name('employees.restore')
        ->withTrashed();
    Route::delete('employees/{user}/purge', [EmployeeController::class, 'purge'])
        ->name('employees.purge')
        ->withTrashed();

    Route::prefix('template')->name('template.')->group(function () {
        Route::get('/form', function () {
            return inertia('Dashboard/Template/Form');
        })->name('form');

        Route::get('/cards', function () {
            return inertia('Dashboard/Template/Cards');
        })->name('cards');

        Route::get('/navigation', function () {
            return inertia('Dashboard/Template/Navigation');
        })->name('navigation');

        Route::get('/buttons', function () {
            return inertia('Dashboard/Template/Buttons');
        })->name('buttons');

        Route::get('/charts', function () {
            return inertia('Dashboard/Template/Charts');
        })->name('charts');

        Route::get('/notifications', function () {
            return inertia('Dashboard/Template/Notifications');
        })->name('notifications');

        Route::get('/widgets', function () {
            return inertia('Dashboard/Template/Widgets');
        })->name('widgets');
    });


    Route::delete('/logout', [LoginController::class, 'destroy'])->name('logout');
});
