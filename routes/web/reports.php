<?php

use App\Http\Controllers\Reports\CashierShiftReportController;
use App\Http\Controllers\Reports\CustomerReportController;
use App\Http\Controllers\Reports\ProductReportController;
use App\Http\Controllers\Reports\PromotionReportController;
use App\Http\Controllers\Reports\SalesReportController;
use App\Http\Controllers\Reports\StockAssetReportController;
use Illuminate\Support\Facades\Route;

Route::prefix('reports')->name('reports.')->group(function () {
    // 1. Laporan Penjualan (Sales)
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::get('/', [SalesReportController::class, 'index'])->name('index');
        Route::get('/export-pdf', [SalesReportController::class, 'exportPdf'])->name('export.pdf');
        Route::post('/export-csv', [SalesReportController::class, 'exportCsv'])->name('export.csv');
    });

    // 2. Laporan Produk
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/', [ProductReportController::class, 'index'])->name('index');
        Route::get('/export-pdf', [ProductReportController::class, 'exportPdf'])->name('export.pdf');
        Route::post('/export-csv', [ProductReportController::class, 'exportCsv'])->name('export.csv');
    });

    // 3. Laporan Stok & Aset
    Route::prefix('stocks')->name('stocks.')->group(function () {
        Route::get('/', [StockAssetReportController::class, 'index'])->name('index');
        Route::get('/export-pdf', [StockAssetReportController::class, 'exportPdf'])->name('export.pdf');
        Route::post('/export-csv', [StockAssetReportController::class, 'exportCsv'])->name('export.csv');
    });

    // 4. Laporan Kasir (Shift)
    Route::prefix('cashiers')->name('cashiers.')->group(function () {
        Route::get('/', [CashierShiftReportController::class, 'index'])->name('index');
        Route::get('/export-pdf', [CashierShiftReportController::class, 'exportPdf'])->name('export.pdf');
        Route::post('/export-csv', [CashierShiftReportController::class, 'exportCsv'])->name('export.csv');
    });

    // 5. Laporan Promosi
    Route::prefix('promotions')->name('promotions.')->group(function () {
        Route::get('/', [PromotionReportController::class, 'index'])->name('index');
        Route::get('/export-pdf', [PromotionReportController::class, 'exportPdf'])->name('export.pdf');
        Route::post('/export-csv', [PromotionReportController::class, 'exportCsv'])->name('export.csv');
    });

    // 6. Laporan Pelanggan
    Route::prefix('customers')->name('customers.')->group(function () {
        Route::get('/', [CustomerReportController::class, 'index'])->name('index');
        Route::get('/export-pdf', [CustomerReportController::class, 'exportPdf'])->name('export.pdf');
        Route::post('/export-csv', [CustomerReportController::class, 'exportCsv'])->name('export.csv');
    });
});
