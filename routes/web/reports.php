<?php

use App\Http\Controllers\Reports\CashierShiftReportController;
use App\Http\Controllers\Reports\CustomerReportController;
use App\Http\Controllers\Reports\ProductReportController;
use App\Http\Controllers\Reports\PromotionReportController;
use App\Http\Controllers\Reports\SalesReportController;
use App\Http\Controllers\Reports\StockAssetReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('reports')->name('reports.')->group(function () {
    Route::get('/sales', [SalesReportController::class, 'index'])->name('sales.index');
    Route::get('/products', [ProductReportController::class, 'index'])->name('products.index');
    Route::get('/stocks', [StockAssetReportController::class, 'index'])->name('stocks.index');
    Route::get('/cashiers', [CashierShiftReportController::class, 'index'])->name('cashiers.index');
    Route::get('/promotions', [PromotionReportController::class, 'index'])->name('promotions.index');
    Route::get('/customers', [CustomerReportController::class, 'index'])->name('customers.index');
});
