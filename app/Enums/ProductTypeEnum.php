<?php

namespace App\Enums;

enum ProductTypeEnum: string
{
    case BASIC = 'basic';
    case SERVICE = 'service';
    case BUNDLE = 'bundle';

    public function label(): string
    {
        return match ($this) {
            self::BASIC => 'Produk Barang',
            self::SERVICE => 'Produk Layanan',
            self::BUNDLE => 'Paket Bundle',
        };
    }

    public function badgeColor(): string
    {
        return match ($this) {
            self::BASIC => 'badge-neutral-400',
            self::SERVICE => 'badge-info',
            self::BUNDLE => 'badge-warning',
        };
    }

    public function color(): string
    {
        return $this->badgeColor();
    }

    public function icon(): string
    {
        return match ($this) {
            self::BASIC => 'fa-box',
            self::SERVICE => 'fa-bell-concierge',
            self::BUNDLE => 'fa-boxes-stacked',
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
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $type) => [
                $type->value => $type->label(),
            ])
            ->toArray();
    }
}
