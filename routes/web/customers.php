<?php

use App\Http\Controllers\CustomerController;
use Illuminate\Support\Facades\Route;

Route::prefix('customers')->name('customers.')->group(function () {
    Route::get('/', [CustomerController::class, 'index'])->name('index');
    Route::post('/', [CustomerController::class, 'store'])->name('store');
    Route::put('/{customer}', [CustomerController::class, 'update'])->name('update');
    Route::delete('/{customer}', [CustomerController::class, 'destroy'])->name('destroy');
    Route::get('/import/template', [CustomerController::class, 'importTemplate'])->name('importTemplate');
    Route::post('/import', [CustomerController::class, 'import'])->name('import');
    Route::get('/search', [CustomerController::class, 'search'])->name('search');
    Route::get('/export', [CustomerController::class, 'export'])->name('export');
    Route::get('/{customer}', [CustomerController::class, 'show'])->name('show');
});
