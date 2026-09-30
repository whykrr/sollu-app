<?php

namespace App\Enums;

enum PromotionDiscountType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::Percentage => 'Persentase (%)',
            self::Fixed => 'Nominal Tetap (Rp)',
        };
    }
}
