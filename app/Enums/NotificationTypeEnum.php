<?php

namespace App\Enums;

enum NotificationTypeEnum: string
{
    case INFO = 'info';
    case SUCCESS = 'success';
    case WARNING = 'warning';
    case DANGER = 'danger';

    public function label(): string
    {
        return match ($this) {
            self::INFO => 'Informasi',
            self::SUCCESS => 'Sukses',
            self::WARNING => 'Peringatan',
            self::DANGER => 'Bahaya / Gagal',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::INFO => 'badge-info',
            self::SUCCESS => 'badge-success',
            self::WARNING => 'badge-warning',
            self::DANGER => 'badge-danger',
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
