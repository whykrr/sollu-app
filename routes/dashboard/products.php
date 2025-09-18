<?php

use App\Http\Controllers\Dashboard\Product\ProductCategoryController;
use App\Http\Controllers\Dashboard\Product\UnitController;
use Illuminate\Support\Facades\Route;

Route::prefix('products')
    ->name('products.')
    ->group(function () {
        Route::resource('units', UnitController::class)->except(['edit'])->withTrashed();
        Route::put('units/{unit}/restore', [UnitController::class, 'restore'])
            ->name('units.restore')
            ->withTrashed();
        Route::delete('units/{unit}/purge', [UnitController::class, 'purge'])
            ->name('units.purge')
            ->withTrashed();

        Route::resource('categories', ProductCategoryController::class);
    });
