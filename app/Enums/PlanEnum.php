<?php

namespace App\Enums;

enum PlanEnum: string
{
    case MICRO = 'micro';
    case BASIC = 'basic';
    case PRO = 'pro';

    public function label(): string
    {
        return match ($this) {
            self::MICRO => 'Paket Mikro',
            self::BASIC => 'Paket Basic',
            self::PRO => 'Paket Pro',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
