<?php

declare(strict_types=1);

namespace App\Observers\POS;

use App\Events\Pos\PosCatalogNudgeEvent;
use App\Models\Inventory\InventoryBalance;

class InventoryBalanceObserver
{
    public function saved(InventoryBalance $balance): void
    {
        if ($balance->outlet_id) {
            event(new PosCatalogNudgeEvent($balance->outlet_id, 'inventory_balance'));
        }
    }

    public function deleted(InventoryBalance $balance): void
    {
        if ($balance->outlet_id) {
            event(new PosCatalogNudgeEvent($balance->outlet_id, 'inventory_balance'));
        }
    }
}
