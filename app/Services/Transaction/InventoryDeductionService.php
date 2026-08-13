<?php

namespace App\Services\Transaction;

use App\Models\Master\InventoryBalance;
use App\Models\Master\InventoryMovement;
use App\Models\Sales\Transaction;

class InventoryDeductionService
{
    public function deductFromTransaction(Transaction $transaction): void
    {
        // Scaffold
        // For each item in transaction, update InventoryBalance and create InventoryMovement
    }
}
