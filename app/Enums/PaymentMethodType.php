<?php

namespace App\Enums;

enum PaymentMethodType: string
{
    case CASH = 'cash';
    case QRIS = 'qris';
    case BANK_TRANSFER = 'bank_transfer';
    case EDC = 'edc';
    case EWALLET = 'ewallet';
    case CUSTOM = 'custom';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Tunai',
            self::QRIS => 'QRIS',
            self::BANK_TRANSFER => 'Transfer Bank',
            self::EDC => 'Kartu Debit / Kredit (EDC)',
            self::EWALLET => 'E-Wallet',
            self::CUSTOM => 'Kustom / Lainnya',
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
