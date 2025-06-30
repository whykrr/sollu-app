<?php

use App\Http\Controllers\OverviewController;
use App\Http\Controllers\User\LoginController;
use App\Http\Controllers\User\RegisterController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'index'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->name('login.attempt');

    // Route::get('/forgot', [LoginController::class, 'index'])->name('login');

    Route::get('/register', [RegisterController::class, 'index'])->name('register');
    Route::post('/register', [RegisterController::class, 'index'])->name('register.store');
});

Route::middleware('auth')->group(function () {
    Route::get('/', OverviewController::class)->name('overview');
});
