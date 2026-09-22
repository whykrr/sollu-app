<?php

use App\Enums\FeatureEnum;
use App\Http\Controllers\App\Reports\CashierShiftReportController;
use App\Http\Controllers\App\Reports\CustomerReportController;
use App\Http\Controllers\App\Reports\ProductReportController;
use App\Http\Controllers\App\Reports\PromotionReportController;
use App\Http\Controllers\App\Reports\SalesReportController;
use App\Http\Controllers\App\Reports\StockAssetReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('reports')->name('reports.')->group(function () {
    // 1. Laporan Penjualan (Sales)
    Route::prefix('sales')->name('sales.')->middleware('plan.feature:'.FeatureEnum::SALES_REPORTS->value)->group(function () {
        Route::get('/', [SalesReportController::class, 'index'])->name('index');
        Route::match(['get', 'post'], '/export-pdf', [SalesReportController::class, 'exportPdf'])->name('export.pdf');
        Route::match(['get', 'post'], '/export-csv', [SalesReportController::class, 'exportCsv'])->name('export.csv');
    });

    // 2. Laporan Produk
    Route::prefix('products')->name('products.')->middleware('plan.feature:'.FeatureEnum::PRODUCT_REPORTS->value)->group(function () {
        Route::get('/', [ProductReportController::class, 'index'])->name('index');
        Route::match(['get', 'post'], '/export-pdf', [ProductReportController::class, 'exportPdf'])->name('export.pdf');
        Route::match(['get', 'post'], '/export-csv', [ProductReportController::class, 'exportCsv'])->name('export.csv');
    });

    // 3. Laporan Stok & Aset
    Route::prefix('stocks')->name('stocks.')->middleware('plan.feature:'.FeatureEnum::STOCK_REPORTS->value)->group(function () {
        Route::get('/', [StockAssetReportController::class, 'index'])->name('index');
        Route::match(['get', 'post'], '/export-pdf', [StockAssetReportController::class, 'exportPdf'])->name('export.pdf');
        Route::match(['get', 'post'], '/export-csv', [StockAssetReportController::class, 'exportCsv'])->name('export.csv');
    });

    // 4. Laporan Kasir (Shift)
    Route::prefix('cashiers')->name('cashiers.')->middleware('plan.feature:'.FeatureEnum::CASHIER_REPORTS->value)->group(function () {
        Route::get('/', [CashierShiftReportController::class, 'index'])->name('index');
        Route::match(['get', 'post'], '/export-pdf', [CashierShiftReportController::class, 'exportPdf'])->name('export.pdf');
        Route::match(['get', 'post'], '/export-csv', [CashierShiftReportController::class, 'exportCsv'])->name('export.csv');
    });

    // 5. Laporan Promosi
    Route::prefix('promotions')->name('promotions.')->middleware('plan.feature:'.FeatureEnum::PROMO_REPORTS->value)->group(function () {
        Route::get('/', [PromotionReportController::class, 'index'])->name('index');
        Route::match(['get', 'post'], '/export-pdf', [PromotionReportController::class, 'exportPdf'])->name('export.pdf');
        Route::match(['get', 'post'], '/export-csv', [PromotionReportController::class, 'exportCsv'])->name('export.csv');
    });

    // 6. Laporan Pelanggan
    Route::prefix('customers')->name('customers.')->middleware('plan.feature:'.FeatureEnum::CUSTOMER_REPORTS->value)->group(function () {
        Route::get('/', [CustomerReportController::class, 'index'])->name('index');
        Route::match(['get', 'post'], '/export-pdf', [CustomerReportController::class, 'exportPdf'])->name('export.pdf');
        Route::match(['get', 'post'], '/export-csv', [CustomerReportController::class, 'exportCsv'])->name('export.csv');
    });
});
