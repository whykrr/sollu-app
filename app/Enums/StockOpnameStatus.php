<?php

namespace App\Enums;

enum StockOpnameStatus: string
{
    case InProgress = 'in_progress';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::InProgress => 'Sedang Berjalan',
            self::PendingApproval => 'Menunggu Persetujuan',
            self::Approved => 'Disetujui',
            self::Rejected => 'Ditolak',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::InProgress => 'badge-warning',
            self::PendingApproval => 'badge-info',
            self::Approved => 'badge-success',
            self::Rejected => 'badge-danger',
        };
    }
}
