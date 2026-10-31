<?php

namespace App\Enums;

enum PaymentReferencePolicy: string
{
    case OPTIONAL = 'optional';
    case MANDATORY = 'mandatory';
    case HIDDEN = 'hidden';

    public function label(): string
    {
        return match ($this) {
            self::OPTIONAL => 'Opsional',
            self::MANDATORY => 'Wajib Diisi',
            self::HIDDEN => 'Disembunyikan',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->map(fn ($item) => [
            'value' => $item->value,
            'label' => $item->label(),
        ])->toArray();
    }
}
