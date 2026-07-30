<?php

namespace App\Enums;

enum AdjustmentReason: string
{
    case Waste      = 'waste';
    case Expired    = 'expired';
    case Lost       = 'lost';
    case Correction = 'correction';
    case Production = 'production';
    case Other      = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Waste      => 'Rusak / Terbuang',
            self::Expired    => 'Kedaluwarsa',
            self::Lost       => 'Hilang',
            self::Correction => 'Koreksi',
            self::Production => 'Produksi',
            self::Other      => 'Lainnya',
        };
    }
}
