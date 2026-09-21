<?php

namespace App\Enums;

enum PurchaseReturnStatus: string
{
    case Completed = 'completed';
    case Voided = 'voided';

    public function label(): string
    {
        return match ($this) {
            self::Completed => 'Selesai',
            self::Voided => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Completed => 'badge-success',
            self::Voided => 'badge-danger',
        };
    }
}
