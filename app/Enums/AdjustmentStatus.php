<?php

namespace App\Enums;

enum AdjustmentStatus: string
{
    case Draft = 'draft';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Voided = 'voided';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
            self::Voided => 'Dibatalkan',
        };
    }
}
