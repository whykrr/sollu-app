<?php

use App\Http\Controllers\Master\ModifierGroupController;
use App\Http\Controllers\Master\ProductCategoryController;
use App\Http\Controllers\Master\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('master')
    ->name('master.')
    ->group(function () {
        Route::post('categories/reorder', [ProductCategoryController::class, 'reorder'])->name('categories.reorder');
        Route::resource('categories', ProductCategoryController::class)->except(['create', 'edit', 'show']);
        Route::get('products/export', [ProductController::class, 'export'])->name('products.export');
        Route::get('products/import-template', [ProductController::class, 'importTemplate'])->name('products.importTemplate');
        Route::post('products/import', [ProductController::class, 'import'])->name('products.import');
        Route::resource('products', ProductController::class);
        Route::resource('modifiers', ModifierGroupController::class)->except(['create', 'edit', 'show']);
    });
