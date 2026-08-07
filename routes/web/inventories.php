<?php

use App\Http\Controllers\Inventory\InventoryMovementController;
use App\Http\Controllers\Inventory\RawMaterialController;
use App\Http\Controllers\Inventory\StockAdjustmentController;
use App\Http\Controllers\Inventory\StockController;
use App\Http\Controllers\Inventory\StockPurchasesController;
use App\Http\Controllers\Inventory\StockOpnameController;
use App\Http\Controllers\Inventory\StockTransferController;
use App\Http\Controllers\Inventory\SupplierController;
use Illuminate\Support\Facades\Route;

Route::prefix('inventories')->group(function () {
    // inventories.* group
    Route::name('inventories.')->group(function () {
        Route::get('stocks', [StockController::class, 'index'])->name('stocks.index');
        Route::get('stocks/export-csv', [StockController::class, 'exportCsv'])->name('stocks.export-csv');
        Route::get('stocks/export-pdf-list', [StockController::class, 'exportPdfList'])->name('stocks.export-pdf-list');
        Route::get('stocks/{id}', [StockController::class, 'show'])->name('stocks.show');
        Route::patch('stocks/{id}/barcode', [StockController::class, 'updateBarcode'])->name('stocks.barcode.update');
        Route::post('stocks/{id}/initial-stock', [StockController::class, 'storeInitialStock'])->name('stocks.initial-stock.store');
        Route::get('stocks/{id}/export-pdf', [StockController::class, 'exportPdf'])->name('stocks.export.pdf');

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

        // Stock Opnames
        Route::get('stock-opnames', [StockOpnameController::class, 'index'])->name('opnames.index');
        Route::post('stock-opnames', [StockOpnameController::class, 'store'])->name('opnames.store');
        Route::put('stock-opnames/{id}', [StockOpnameController::class, 'update'])->name('opnames.update');
        Route::delete('stock-opnames/{id}', [StockOpnameController::class, 'destroy'])->name('opnames.destroy');
        Route::post('stock-opnames/{id}/approve', [StockOpnameController::class, 'approve'])->name('opnames.approve');
        Route::post('stock-opnames/{id}/reject', [StockOpnameController::class, 'reject'])->name('opnames.reject');
        Route::get('stock-opnames/{id}/pdf', [StockOpnameController::class, 'exportPdf'])->name('opnames.export.pdf');
        Route::get('stock-opnames/{id}', [StockOpnameController::class, 'show'])->name('opnames.show');

        // Adjustments
        Route::get('adjustments', [StockAdjustmentController::class, 'index'])->name('adjustments.index');
        Route::get('adjustments/{id}/pdf', [StockAdjustmentController::class, 'exportPdf'])->name('adjustments.export.pdf');
        Route::get('adjustments/{id}', [StockAdjustmentController::class, 'show'])->name('adjustments.show');

        // Outlets (Generic Inventory Outlet Actions)
        Route::post('outlets/freeze', [\App\Http\Controllers\Inventory\OutletFreezeController::class, 'freeze'])->name('outlets.freeze');
        Route::post('outlets/unfreeze', [\App\Http\Controllers\Inventory\OutletFreezeController::class, 'unfreeze'])->name('outlets.unfreeze');

        Route::middleware(['stock.not.frozen'])->group(function () {
            Route::post('adjustments', [StockAdjustmentController::class, 'store'])->name('adjustments.store');
            Route::post('adjustments/{stock_adjustment}/approve', [StockAdjustmentController::class, 'approve'])->name('adjustments.approve');
            Route::post('adjustments/{stock_adjustment}/void', [StockAdjustmentController::class, 'void'])->name('adjustments.void');
        });

        Route::post('adjustments/{stock_adjustment}/reject', [StockAdjustmentController::class, 'reject'])->name('adjustments.reject');

        // Transfers
        Route::get('transfers', [StockTransferController::class, 'index'])->name('transfers.index');
        Route::post('transfers', [StockTransferController::class, 'store'])->name('transfers.store');
        Route::get('transfers/{transfer}', [StockTransferController::class, 'show'])->name('transfers.show');
        Route::get('transfers/{transfer}/pdf', [StockTransferController::class, 'exportPdf'])->name('transfers.export.pdf');
        Route::put('transfers/{transfer}', [StockTransferController::class, 'update'])->name('transfers.update');
        Route::post('transfers/{transfer}/approve', [StockTransferController::class, 'approve'])->name('transfers.approve');
        Route::post('transfers/{transfer}/reject', [StockTransferController::class, 'reject'])->name('transfers.reject');
        Route::post('transfers/{transfer}/ship', [StockTransferController::class, 'ship'])->name('transfers.ship');
        Route::post('transfers/{transfer}/receive', [StockTransferController::class, 'receive'])->name('transfers.receive');
    });
});
