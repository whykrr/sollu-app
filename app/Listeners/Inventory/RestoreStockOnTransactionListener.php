<?php

namespace App\Listeners\Inventory;

use App\Contracts\Inventory\InventoryDeductionServiceInterface;
use App\Events\Transaction\TransactionReversed;
use Illuminate\Contracts\Queue\ShouldQueue;

class RestoreStockOnTransactionListener implements ShouldQueue
{
    public int $tries = 3;

    public bool $afterCommit = true;

    public function __construct(protected InventoryDeductionServiceInterface $stockDeductionService) {}

    public function handle(TransactionReversed $event): void
    {
        if ($event->requiresStockRestoration) {
            $this->stockDeductionService->restoreFromTransaction($event->transaction);
        }
    }
}
