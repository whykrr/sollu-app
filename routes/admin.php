<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\ContentSingleController;
use App\Http\Controllers\Admin\ContentTypeController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MessageResponseController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\LanguageController;
use App\Http\Controllers\Admin\MessageController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\UserChangePasswordController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/login', [AuthController::class, 'index'])->name('login')->middleware('guest');
Route::get('/logout', [AuthController::class, 'destroy'])->name('logout')->middleware('auth');
Route::post('/login', [AuthController::class, 'store'])->name('login.attempt')->middleware('guest');

Route::get('/', DashboardController::class)->name('dashboard')->middleware('auth');

Route::middleware('auth')
    ->group(function () {

        Route::resource('users', UserController::class)->except(['show', 'edit']);
        Route::resource('users', UserController::class)->only(['edit'])->withTrashed();
        Route::put('users/{user}/restore', [UserController::class, 'restore'])
            ->name('users.restore')
            ->withTrashed();
        Route::delete('users/{user}/permanent-delete', [UserController::class, 'permanentDelete'])
            ->name('users.destroy.permanent')
            ->withTrashed();

        Route::prefix('change_password')
            ->name('change_password.')
            ->group(function () {
                Route::get('/', [UserChangePasswordController::class, 'index'])->name('index');
                Route::post('/{user}', [UserChangePasswordController::class, 'store'])->name('store');
            });


        Route::resource('languages', LanguageController::class)->except(['edit']);
        Route::put('languages/{language}/default', [LanguageController::class, 'setDefault'])
            ->name('languages.default');


        Route::resource('content-types', ContentTypeController::class);
        Route::delete('content-types/{content_type}/delete-field/{id}', [ContentTypeController::class, 'destroyField'])->name('content-types.delete-field');

        Route::prefix('contents')
            ->name('contents.')
            ->group(function () {
                Route::get('/{content_type}', [ContentController::class, 'showSingle'])->name('index');
                Route::get('/{content_type}/listed', [ContentController::class, 'showListed'])->name('listed');
                Route::get('/{content_type}/listed/create', [ContentController::class, 'createContentListed'])->name('create');
                Route::get('/{content_type}/listed/content/{id}', [ContentController::class, 'editContentListed'])->name('edit');
                Route::post('/{content_type}', [ContentController::class, 'store'])->name('store');
                Route::post('/{content_type}/content/{content}', [ContentController::class, 'update'])->name('update');
                Route::delete('/{content_type}/listed/content/{content}', [ContentController::class, 'delete'])->name('delete');
            });

        Route::prefix('message')
            ->name('message.')
            ->group(function () {
                Route::get('/', [MessageController::class, 'index'])->name('index');
                Route::get('/{message}', [MessageController::class, 'show'])->name('show');
                Route::post('response/{message}', MessageResponseController::class)->name('response');
            });

        Route::prefix('settings')
            ->name('settings.')
            ->group(function () {
                Route::get('/', [SettingController::class, 'index'])->name('index');
                Route::post('/{setting}', [SettingController::class, 'update'])->name('update');
            });
    });
