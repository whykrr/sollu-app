<?php

declare(strict_types=1);

namespace App\Observers\POS;

use App\Models\Inventory\InventoryBalance;
use App\Services\Pos\PosNudgeQueueService;

class InventoryBalanceObserver
{
    public function __construct(
        protected PosNudgeQueueService $nudgeQueue
    ) {}

    public function saved(InventoryBalance $balance): void
    {
        if ($balance->outlet_id) {
            $this->nudgeQueue->queueSignal($balance->outlet_id, 'inventory_balance');
        }
    }

    public function deleted(InventoryBalance $balance): void
    {
        if ($balance->outlet_id) {
            $this->nudgeQueue->queueSignal($balance->outlet_id, 'inventory_balance');
        }
    }
}
