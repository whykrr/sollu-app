<?php

use App\Http\Controllers\Sales\InvoiceController;
use Illuminate\Support\Facades\Route;

Route::prefix('sales')->name('sales.')->group(function () {
    Route::resource('invoices', InvoiceController::class)->except(['edit', 'update', 'destroy']);
});
