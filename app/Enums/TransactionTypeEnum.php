<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionTypeEnum: string
{
    case Invoice = 'invoice';
    case WalkIn = 'walk_in';
    case DineIn = 'dine_in';
    case TakeAway = 'take_away';

    public function label(): string
    {
        return match ($this) {
            self::Invoice => 'Penjualan',
            self::WalkIn => 'Walk-In',
            self::DineIn => 'Dine In',
            self::TakeAway => 'Take Away',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Invoice => 'info',
            self::WalkIn => 'primary',
            self::DineIn => 'amber',
            self::TakeAway => 'purple',
        };
    }

    public function isPosType(): bool
    {
        return match ($this) {
            self::WalkIn, self::DineIn, self::TakeAway => true,
            self::Invoice => false,
        };
    }

    public function isWholesaleType(): bool
    {
        return $this === self::Invoice;
    }
}
