<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Dashboard\Product\ProductCategoryController;

Route::delete('products/categories/{category}/force', [ProductCategoryController::class, 'forceDelete'])
    ->name('products.categories.force-delete');

Route::resource('products/categories', ProductCategoryController::class)->parameters([
    'categories' => 'category'
]);
