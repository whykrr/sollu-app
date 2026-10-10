<?php

declare(strict_types=1);

namespace App\Enums;

enum SalesChannelEnum: string
{
    case POS = 'pos';
    case Wholesale = 'wholesale';

    public function label(): string
    {
        return match ($this) {
            self::POS => 'Kasir POS',
            self::Wholesale => 'Wholesale (Penjualan)',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::POS => 'primary',
            self::Wholesale => 'purple',
        };
    }

    public function initial(): string
    {
        return match ($this) {
            self::POS => 'POS',
            self::Wholesale => 'WS',
        };
    }
}
