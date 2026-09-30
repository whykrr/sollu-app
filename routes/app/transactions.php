<?php

use App\Enums\FeatureEnum;
use App\Http\Controllers\App\Transaction\InvoiceController;
use App\Http\Controllers\App\Transaction\SalesTransactionController;
use App\Http\Controllers\App\Transaction\ShiftController;
use Illuminate\Support\Facades\Route;

Route::prefix('transactions')->name('transactions.')->group(function () {
    Route::prefix('sales')
        ->name('sales.')
        ->middleware(['plan.feature:'.FeatureEnum::INVOICE_DEBT->value])
        ->group(function () {
            Route::get('/', [SalesTransactionController::class, 'index'])
                ->middleware('permission:transaction.view')
                ->name('index');

            Route::post('/', [SalesTransactionController::class, 'store'])
                ->middleware('permission:transaction.create')
                ->name('store');

            Route::get('/{transaction}', [SalesTransactionController::class, 'show'])
                ->middleware('permission:transaction.view')
                ->name('show');

            Route::put('/{transaction}', [SalesTransactionController::class, 'update'])
                ->name('update');

            Route::delete('/{transaction}', [SalesTransactionController::class, 'destroy'])
                ->name('destroy');

            Route::post('/{transaction}/issue', [SalesTransactionController::class, 'issue'])
                ->middleware('permission:transaction.issue_invoice')
                ->name('issue');

            Route::post('/{transaction}/payment', [SalesTransactionController::class, 'recordPayment'])
                ->middleware('permission:transaction.record_payment')
                ->name('record-payment');

            Route::put('/{transaction}/due-date', [SalesTransactionController::class, 'updateDueDate'])
                ->middleware('permission:transaction.edit_due_date')
                ->name('update-due-date');

            Route::post('/{transaction}/cancel', [SalesTransactionController::class, 'cancel'])
                ->middleware('permission:transaction.cancel')
                ->name('cancel');

            // Download PDF
            Route::get('/{transaction}/pdf', [InvoiceController::class, 'downloadPdf'])
                ->middleware('permission:transaction.view')
                ->name('pdf');
        });

    Route::middleware('plan.feature:'.FeatureEnum::SHIFT_MANAGEMENT->value)->group(function () {
        Route::resource('shifts', ShiftController::class)->only(['index', 'show']);
    });
});
