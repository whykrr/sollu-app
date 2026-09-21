<?php

namespace App\Enums;

enum PurchaseOrderStatus: string
{
    case Draft = 'draft';
    case Ordered = 'ordered';
    case PartialReceived = 'partial_received';
    case Received = 'received';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Ordered => 'Dipesan',
            self::PartialReceived => 'Diterima Sebagian',
            self::Received => 'Diterima',
            self::Cancelled => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'badge-gray',
            self::Ordered => 'badge-info',
            self::PartialReceived => 'badge-warning',
            self::Received => 'badge-success',
            self::Cancelled => 'badge-danger',
        };
    }
}
