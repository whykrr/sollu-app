<?php

namespace App\Notifications;

use App\Enums\NotificationCategoryEnum;
use App\Enums\NotificationScopeEnum;
use App\Enums\NotificationTypeEnum;

class NewOrderNotification extends BaseNotification
{
    /**
     * Create a new order notification.
     */
    public function __construct(
        string $orderNumber,
        string $sourceName,
        float $totalAmount,
        ?string $outletId = null,
        ?string $businessId = null,
        ?string $detailUrl = null
    ) {
        $this->afterCommit = true;
        $this->category = NotificationCategoryEnum::ORDER;
        $this->type = NotificationTypeEnum::INFO;
        $this->scope = NotificationScopeEnum::OUTLET;
        $this->businessId = $businessId;
        $this->outletId = $outletId;
        $this->title = "Pesanan Baru #{$orderNumber}";
        $this->message = "Pesanan masuk dari {$sourceName} dengan total Rp ".number_format($totalAmount, 0, ',', '.').'.';
        $this->actionUrl = $detailUrl;
        $this->actionText = 'Lihat Pesanan';
        $this->meta = [
            'order_number' => $orderNumber,
            'source' => $sourceName,
            'total_amount' => $totalAmount,
        ];
    }
}
