<?php

declare(strict_types=1);

namespace App\Enums;

enum PaymentTermEnum: string
{
    case Cash = 'cash';
    case Credit = 'credit';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Tunai (Langsung Lunas / COD)',
            self::Credit => 'Termin / Kredit (Tempo)',
        };
    }
}
