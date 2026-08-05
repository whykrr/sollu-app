<?php

use App\Http\Controllers\Inventory\InventoryMovementController;
use App\Http\Controllers\Inventory\RawMaterialController;
use App\Http\Controllers\Inventory\StockAdjustmentController;
use App\Http\Controllers\Inventory\StockController;
use App\Http\Controllers\Inventory\StockPurchasesController;
use App\Http\Controllers\Inventory\StockTakingController;
use App\Http\Controllers\Inventory\StockTransferController;
use App\Http\Controllers\Inventory\SupplierController;
use Illuminate\Support\Facades\Route;

Route::prefix('inventories')->group(function () {
    // inventories.* group
    Route::name('inventories.')->group(function () {
        Route::get('stocks', [StockController::class, 'index'])->name('stocks.index');
        Route::get('stocks/{id}', [StockController::class, 'show'])->name('stocks.show');
        Route::get('stocks/{id}/movements', [StockController::class, 'movements'])->name('stocks.movements');
        Route::get('stocks/{id}/chart', [StockController::class, 'chart'])->name('stocks.chart');
        Route::get('movements', [InventoryMovementController::class, 'index'])->name('movements.index');
    });

    // inventory.* group
    Route::name('inventory.')->group(function () {
        // Raw Materials
        Route::get('raw-materials/export', [RawMaterialController::class, 'export'])->name('raw-materials.export');
        Route::get('raw-materials/import/template', [RawMaterialController::class, 'importTemplate'])->name('raw-materials.importTemplate');
        Route::post('raw-materials/import', [RawMaterialController::class, 'import'])->name('raw-materials.import');

        Route::get('raw-materials', [RawMaterialController::class, 'index'])->name('raw-materials.index');
        Route::post('raw-materials', [RawMaterialController::class, 'store'])->name('raw-materials.store');
        Route::put('raw-materials/{id}', [RawMaterialController::class, 'update'])->name('raw-materials.update');
        Route::delete('raw-materials/{id}', [RawMaterialController::class, 'destroy'])->name('raw-materials.destroy');

        // Suppliers
        Route::get('suppliers/search-items', [SupplierController::class, 'searchItems'])->name('suppliers.search-items');
        Route::get('suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
        Route::post('suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::put('suppliers/{id}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::delete('suppliers/{id}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');

        // Purchases
        Route::get('purchases/search-items', [StockPurchasesController::class, 'searchItems'])->name('purchases.search-items');
        Route::get('purchases', [StockPurchasesController::class, 'index'])->name('purchases.index');
        Route::post('purchases', [StockPurchasesController::class, 'store'])->name('purchases.store');
        Route::get('purchases/{id}', [StockPurchasesController::class, 'show'])->name('purchases.show');
        Route::put('purchases/{id}', [StockPurchasesController::class, 'update'])->name('purchases.update');
        Route::delete('purchases/{id}', [StockPurchasesController::class, 'destroy'])->name('purchases.destroy');

        Route::post('purchases/{id}/order', [StockPurchasesController::class, 'order'])->name('purchases.order');
        Route::post('purchases/{id}/receive', [StockPurchasesController::class, 'receive'])->name('purchases.receive');
        Route::post('purchases/{id}/cancel', [StockPurchasesController::class, 'cancel'])->name('purchases.cancel');
        Route::post('purchases/{id}/void', [StockPurchasesController::class, 'void'])->name('purchases.void');
        Route::get('purchases/{id}/pdf', [StockPurchasesController::class, 'pdf'])->name('purchases.pdf');

        // Stock Taking (Opnames)
        Route::get('stock-taking', [StockTakingController::class, 'index'])->name('stocktaking.index');
        Route::post('stock-taking', [StockTakingController::class, 'store'])->name('opnames.store');
        Route::put('stock-taking/{id}', [StockTakingController::class, 'update'])->name('opnames.update');
        Route::delete('stock-taking/{id}', [StockTakingController::class, 'destroy'])->name('opnames.destroy');
        Route::post('stock-taking/{id}/approve', [StockTakingController::class, 'approve'])->name('opnames.approve');

        // Adjustments
        Route::get('adjustments', [StockAdjustmentController::class, 'index'])->name('adjustments.index');
        Route::get('adjustments/{id}/pdf', [StockAdjustmentController::class, 'exportPdf'])->name('adjustments.export.pdf');
        Route::get('adjustments/{id}', [StockAdjustmentController::class, 'show'])->name('adjustments.show');
        Route::post('adjustments/freeze', [StockAdjustmentController::class, 'freeze'])->name('adjustments.freeze');
        Route::post('adjustments/unfreeze', [StockAdjustmentController::class, 'unfreeze'])->name('adjustments.unfreeze');

        Route::middleware(['stock.not.frozen'])->group(function () {
            Route::post('adjustments', [StockAdjustmentController::class, 'store'])->name('adjustments.store');
            Route::post('adjustments/{stock_adjustment}/approve', [StockAdjustmentController::class, 'approve'])->name('adjustments.approve');
            Route::post('adjustments/{stock_adjustment}/void', [StockAdjustmentController::class, 'void'])->name('adjustments.void');
        });

        Route::post('adjustments/{stock_adjustment}/reject', [StockAdjustmentController::class, 'reject'])->name('adjustments.reject');

        // Transfers
        Route::get('transfers', [StockTransferController::class, 'index'])->name('transfers.index');
    });
});
