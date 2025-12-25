<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\CategoryController;

Route::delete('categories/{category}/force', [CategoryController::class, 'forceDelete'])
    ->name('categories.force-delete');

Route::resource('categories', CategoryController::class);
