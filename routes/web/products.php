<?php

use App\Http\Controllers\Product\ProductCategoryController;
use App\Http\Controllers\Product\UnitController;
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

        Route::delete('categories/{category}/force', [ProductCategoryController::class, 'forceDelete'])
            ->name('categories.force-delete');

        Route::resource('categories', ProductCategoryController::class)->parameters([
            'categories' => 'category',
        ]);
    });
