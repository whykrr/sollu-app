<?php

namespace App\Enums;

enum AuditModuleEnum: string
{
    case POS = 'pos_and_transactions';
    case PRODUCTS = 'products_and_menu';
    case INVENTORY = 'inventory_and_supply';
    case PROMOTIONS = 'promotions_and_crm';
    case EMPLOYEES = 'employees_and_roles';
    case SETTINGS = 'settings_and_business';
    case AUTH = 'auth_and_security';

    public function label(): string
    {
        return match ($this) {
            self::POS => 'Penjualan & Kasir',
            self::PRODUCTS => 'Produk & Menu',
            self::INVENTORY => 'Inventori & Rantai Pasok',
            self::PROMOTIONS => 'Promosi & Pelanggan',
            self::EMPLOYEES => 'Pegawai & Hak Akses',
            self::SETTINGS => 'Pengaturan & Bisnis',
            self::AUTH => 'Keamanan & Autentikasi',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $module) => [
                $module->value => $module->label(),
            ])
            ->toArray();
    }
}
