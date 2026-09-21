<?php

namespace App\Notifications;

use App\Enums\NotificationCategoryEnum;
use App\Enums\NotificationScopeEnum;
use App\Enums\NotificationTypeEnum;

class LowStockAlertNotification extends BaseNotification
{
    /**
     * Create a new low stock alert notification.
     */
    public function __construct(
        string $itemName,
        float $currentStock,
        float $minimumStock,
        string $unitName,
        string $outletName,
        ?string $outletId = null,
        ?string $businessId = null,
        ?string $actionUrl = null
    ) {
        $this->afterCommit = true;
        $this->category = NotificationCategoryEnum::INVENTORY;
        $this->type = NotificationTypeEnum::WARNING;
        $this->scope = NotificationScopeEnum::OUTLET;
        $this->businessId = $businessId;
        $this->outletId = $outletId;
        $this->title = 'Peringatan Stok Menipis';
        $this->message = "Stok {$itemName} di {$outletName} tersisa {$currentStock} {$unitName} (batas minimum {$minimumStock} {$unitName}). Segera lakukan restock ya!";
        $this->actionUrl = $actionUrl;
        $this->actionText = 'Lihat Stok';
        $this->meta = [
            'item_name' => $itemName,
            'current_stock' => $currentStock,
            'minimum_stock' => $minimumStock,
            'unit' => $unitName,
            'outlet_name' => $outletName,
        ];
    }
}
