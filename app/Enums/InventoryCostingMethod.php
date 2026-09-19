<?php

namespace App\Enums;

enum InventoryCostingMethod: string
{
    case FIFO = 'fifo';
    case AVERAGE = 'average';

    public function label(): string
    {
        return match ($this) {
            self::FIFO => 'FIFO (First-In, First-Out)',
            self::AVERAGE => 'Rata-Rata Bergerak (Moving Average)',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return array_map(fn (self $case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }
}
