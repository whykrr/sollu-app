<?php

namespace App\Enums;

enum PromoStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case Inactive = 'inactive';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Active => 'Aktif',
            self::Inactive => 'Nonaktif',
        };
    }
}
