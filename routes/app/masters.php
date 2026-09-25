<?php

use App\Enums\FeatureEnum;
use App\Http\Controllers\App\Master\ModifierGroupController;
use App\Http\Controllers\App\Master\ProductCategoryController;
use App\Http\Controllers\App\Master\ProductController;
use App\Http\Controllers\App\Master\ServiceProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('master')
    ->name('master.')
    ->group(function () {
        Route::middleware('plan.feature:'.FeatureEnum::PRODUCT_CATEGORIES->value)
            ->group(function () {
                Route::post('categories/reorder', [ProductCategoryController::class, 'reorder'])->name('categories.reorder');
                Route::resource('categories', ProductCategoryController::class)->except(['create', 'edit', 'show']);
            });

        Route::middleware('plan.feature:'.FeatureEnum::PRODUCT_CATALOG->value)
            ->group(function () {
                Route::get('products/form-options', [ProductController::class, 'formOptions'])->name('products.formOptions');
                Route::get('products/export', [ProductController::class, 'export'])->name('products.export');
                Route::get('products/import-template', [ProductController::class, 'importTemplate'])->name('products.importTemplate');
                Route::post('products/import', [ProductController::class, 'import'])->name('products.import');

                Route::resource('products', ProductController::class);
            });

        Route::middleware('plan.feature:'.FeatureEnum::SERVICE_CATALOG->value)
            ->group(function () {
                Route::get('services/form-options', [ServiceProductController::class, 'formOptions'])->name('services.formOptions');
                Route::get('services/export', [ServiceProductController::class, 'export'])->name('services.export');
                Route::get('services/import-template', [ServiceProductController::class, 'importTemplate'])->name('services.importTemplate');
                Route::post('services/import', [ServiceProductController::class, 'import'])->name('services.import');

                Route::resource('services', ServiceProductController::class);
            });

        Route::middleware('plan.feature:'.FeatureEnum::PRODUCT_MODIFIERS->value)
            ->group(function () {
                Route::resource('modifiers', ModifierGroupController::class)->except(['create', 'edit']);
            });
    });
