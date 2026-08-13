<?php

namespace App\Enums;

enum PromoType: string
{
    case Percentage = 'percentage';
    case Fixed = 'fixed';

    public function label(): string
    {
        return match ($this) {
            self::Percentage => 'Persentase',
            self::Fixed => 'Nominal Tetap',
        };
    }
}
