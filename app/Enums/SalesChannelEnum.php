<?php

declare(strict_types=1);

namespace App\Enums;

enum SalesChannelEnum: string
{
    case Wholesale = 'wholesale';
    case Direct = 'direct';

    public function label(): string
    {
        return match ($this) {
            self::Wholesale => 'Grosir (Wholesale)',
            self::Direct => 'Penjualan Langsung (Direct Sales)',
        };
    }
}
