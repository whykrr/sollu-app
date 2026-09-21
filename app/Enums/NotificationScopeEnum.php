<?php

namespace App\Enums;

enum NotificationScopeEnum: string
{
    case USER = 'user';
    case BUSINESS = 'business';
    case OUTLET = 'outlet';

    public function label(): string
    {
        return match ($this) {
            self::USER => 'Pengguna',
            self::BUSINESS => 'Bisnis / Merchant',
            self::OUTLET => 'Outlet',
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
