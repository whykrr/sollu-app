<?php

namespace App\Enums;

enum DeviceTypeEnum: string
{
    case POS_TERMINAL = 'pos_terminal';
    case POS_MOBILE = 'pos_mobile';
    case KIOSK = 'kiosk';
    case KITCHEN_DISPLAY = 'kitchen_display';

    public function label(): string
    {
        return match ($this) {
            self::POS_TERMINAL => 'POS Terminal (Desktop)',
            self::POS_MOBILE => 'POS Mobile (Tablet/HP)',
            self::KIOSK => 'Kiosk / Self-Service',
            self::KITCHEN_DISPLAY => 'Kitchen Device',
        };
    }

    public function isAvailable(): bool
    {
        return match ($this) {
            self::POS_TERMINAL, self::POS_MOBILE => true,
            self::KIOSK, self::KITCHEN_DISPLAY => false,
        };
    }

    /**
     * @return array<int, array{value: string, label: string, disabled: bool}>
     */
    public static function options(): array
    {
        return array_map(fn (self $case) => [
            'value' => $case->value,
            'label' => $case->label().(! $case->isAvailable() ? ' (Segera Hadir)' : ''),
            'disabled' => ! $case->isAvailable(),
        ], self::cases());
    }

    public static function tryFromOrDefault(?string $value): self
    {
        if (! $value) {
            return self::POS_TERMINAL;
        }

        if ($value === 'pos') {
            return self::POS_TERMINAL;
        }

        if ($value === 'kds') {
            return self::KITCHEN_DISPLAY;
        }

        return self::tryFrom($value) ?? self::POS_TERMINAL;
    }
}
