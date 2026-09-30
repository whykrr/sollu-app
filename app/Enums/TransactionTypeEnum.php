<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionTypeEnum: string
{
    case Invoice = 'invoice';
    case Pos = 'pos';

    public function label(): string
    {
        return match ($this) {
            self::Invoice => 'Faktur Penjualan',
            self::Pos => 'Kasir POS',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Invoice => 'info',
            self::Pos => 'primary',
        };
    }
}
