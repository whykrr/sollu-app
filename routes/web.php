<?php

use App\Http\Controllers\OverviewController;
use App\Http\Controllers\User\ForgotPasswordController;
use App\Http\Controllers\User\LoginController;
use App\Http\Controllers\User\RegisterController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'index'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.attempt');

    Route::get('/forgot', [ForgotPasswordController::class, 'index'])->name('forgot');
    Route::post('/forgot', [ForgotPasswordController::class, 'attempt'])->name('forgot.attempt');

    Route::get('/register', [RegisterController::class, 'index'])->name('register');
    Route::post('/register', [RegisterController::class, 'store'])->name('register.store');
});

Route::middleware('auth:merchant')->group(function () {
    Route::get('/', OverviewController::class)->name('overview');
    Route::resource('users', UserController::class)->except('show');
    Route::put('users/{user}/restore', [UserController::class, 'restore'])
             ->name('users.restore')
             ->withTrashed();
    Route::delete('users/{user}/purge', [UserController::class, 'purge'])
        ->name('users.purge')
        ->withTrashed();
});
