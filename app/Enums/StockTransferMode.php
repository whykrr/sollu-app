<?php

namespace App\Enums;

enum StockTransferMode: string
{
    case DIRECT = 'direct';
    case TWO_STEP = 'two_step';

    public function label(): string
    {
        return match ($this) {
            self::DIRECT => 'Transfer Langsung (1-Langkah)',
            self::TWO_STEP => 'Kirim & Terima (2-Langkah)',
        };
    }

    public function shortLabel(): string
    {
        return match ($this) {
            self::DIRECT => '1-Langkah',
            self::TWO_STEP => '2-Langkah',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DIRECT => 'badge-info',
            self::TWO_STEP => 'badge-purple',
        };
    }
}
