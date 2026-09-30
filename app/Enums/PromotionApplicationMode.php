<?php

namespace App\Enums;

enum PromotionApplicationMode: string
{
    case Automatic = 'automatic';
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::Automatic => 'Otomatis',
            self::Manual => 'Kode Promo (Manual)',
        };
    }
}
