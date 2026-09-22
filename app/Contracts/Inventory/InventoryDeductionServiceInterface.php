<?php

namespace App\Contracts\Inventory;

use App\Models\Sales\Transaction;

interface InventoryDeductionServiceInterface
{
    /**
     * Deduct inventory stock based on a completed transaction.
     */
    public function deductFromTransaction(Transaction $transaction): void;

    /**
     * Restore inventory stock based on a voided/reversed transaction.
     */
    public function restoreFromTransaction(Transaction $transaction): void;
}
