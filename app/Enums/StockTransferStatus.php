<?php

namespace App\Enums;

enum StockTransferStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case InTransit = 'in_transit';
    case Completed = 'completed';
    case Rejected = 'rejected';
    case Void = 'void';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Approved => 'Disetujui',
            self::InTransit => 'Dalam Perjalanan',
            self::Completed => 'Selesai',
            self::Rejected => 'Ditolak',
            self::Void => 'Dibatalkan',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'badge-warning',
            self::Approved => 'badge-info',
            self::InTransit => 'badge-purple',
            self::Completed => 'badge-success',
            self::Rejected => 'badge-danger',
            self::Void => 'badge-danger',
        };
    }
}
