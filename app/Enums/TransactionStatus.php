<?php

declare(strict_types=1);

namespace App\Enums;

enum TransactionStatus: string
{
    case Draft = 'draft';
    case Hold = 'hold';
    case Completed = 'completed';
    case Void = 'void';
    case Cancel = 'cancel';
    case Paid = 'paid';
    case Unpaid = 'unpaid';
    case Partial = 'partial';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Hold => 'Ditahan',
            self::Completed => 'Selesai',
            self::Void => 'Dibatalkan (Void)',
            self::Cancel => 'Batal',
            self::Paid => 'Lunas',
            self::Unpaid => 'Belum Dibayar',
            self::Partial => 'Dibayar Sebagian',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Draft => 'neutral',
            self::Hold => 'warning',
            self::Completed, self::Paid => 'success',
            self::Void, self::Cancel => 'danger',
            self::Unpaid => 'neutral',
            self::Partial => 'info',
        };
    }
}
