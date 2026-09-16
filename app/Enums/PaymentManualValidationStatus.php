<?php

namespace App\Enums;

enum PaymentManualValidationStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Verifikasi',
            self::Approved => 'Terverifikasi',
            self::Rejected => 'Ditolak',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'badge-warning',
            self::Approved => 'badge-success',
            self::Rejected => 'badge-danger',
        };
    }

    /**
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * @return array<int, array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $case) => [
            'value' => $case->value,
            'label' => $case->label(),
        ], self::cases());
    }
}
