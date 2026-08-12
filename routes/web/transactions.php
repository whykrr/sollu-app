<?php

use App\Http\Controllers\Transaction\InvoiceController;
use App\Http\Controllers\Transaction\SalesController;
use App\Http\Controllers\Transaction\ShiftController;
use Illuminate\Support\Facades\Route;

Route::prefix('transactions')->name('transactions.')->group(function () {
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::post('export', [SalesController::class, 'export'])->name('export');
        Route::get('/', [SalesController::class, 'index'])->name('index');
        Route::resource('invoices', InvoiceController::class)->except(['edit', 'update', 'destroy']);
        Route::get('/{transaction}', [SalesController::class, 'show'])->name('show');
    });

    Route::resource('shifts', ShiftController::class)->only(['index', 'show']);
});
