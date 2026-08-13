<?php

namespace App\Enums;

enum StockTransferStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case InTransit = 'in_transit';
    case Completed = 'completed';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Approved => 'Disetujui',
            self::InTransit => 'Dalam Perjalanan',
            self::Completed => 'Selesai',
            self::Rejected => 'Ditolak',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'info',
            self::InTransit => 'purple',
            self::Completed => 'success',
            self::Rejected => 'danger',
        };
    }
}
