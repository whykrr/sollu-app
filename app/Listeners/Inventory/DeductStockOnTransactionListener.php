<?php

namespace App\Listeners\Inventory;

use App\Contracts\Inventory\InventoryDeductionServiceInterface;
use App\Events\Transaction\TransactionCompleted;
use Illuminate\Contracts\Queue\ShouldQueue;

class DeductStockOnTransactionListener implements ShouldQueue
{
    public int $tries = 3;

    public bool $afterCommit = true;

    public function __construct(protected InventoryDeductionServiceInterface $stockDeductionService) {}

    public function handle(TransactionCompleted $event): void
    {
        $this->stockDeductionService->deductFromTransaction($event->transaction);
    }
}
