<?php

namespace App\Services\Transaction;

use App\Models\Sales\Transaction;
use App\Models\Master\InventoryBalance;
use App\Models\Master\InventoryMovement;

class InventoryDeductionService
{
    public function deductFromTransaction(Transaction $transaction): void
    {
        // Scaffold
        // For each item in transaction, update InventoryBalance and create InventoryMovement
    }
}
