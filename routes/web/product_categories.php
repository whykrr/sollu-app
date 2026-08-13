<?php

use App\Http\Controllers\Dashboard\Product\ProductCategoryController;
use Illuminate\Support\Facades\Route;

Route::delete('products/categories/{category}/force', [ProductCategoryController::class, 'forceDelete'])
    ->name('products.categories.force-delete');

Route::resource('products/categories', ProductCategoryController::class)->parameters([
    'categories' => 'category',
]);
