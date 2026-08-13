<?php

use App\Http\Controllers\Transaction\InvoiceController;
use App\Http\Controllers\Transaction\SalesController;
use App\Http\Controllers\Transaction\ShiftController;
use Illuminate\Support\Facades\Route;

Route::prefix('transactions')->name('transactions.')->group(function () {
    Route::prefix('sales')->name('sales.')->group(function () {
        Route::post('export', [SalesController::class, 'export'])->name('export');
        Route::get('/', [SalesController::class, 'index'])->name('index');

        Route::post('/', [SalesController::class, 'store'])->name('store');
        Route::post('/{transaction}/issue', [SalesController::class, 'issue'])->name('issue');
        Route::post('/{transaction}/payment', [SalesController::class, 'recordPayment'])->name('record-payment');
        Route::post('/{transaction}/cancel', [SalesController::class, 'cancel'])->name('cancel');
        Route::post('/{transaction}/void', [SalesController::class, 'void'])->name('void');
        Route::get('/{transaction}/pdf', [SalesController::class, 'pdf'])->name('pdf');

        Route::resource('invoices', InvoiceController::class)->except(['edit', 'update', 'destroy']);
        Route::get('/{transaction}', [SalesController::class, 'show'])->name('show');
    });

    Route::resource('shifts', ShiftController::class)->only(['index', 'show']);
});
