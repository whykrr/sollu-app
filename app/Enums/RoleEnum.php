<?php

namespace App\Enums;

enum RoleEnum: string
{
    // Management
    case OWNER = 'owner';
    case GENERAL_MANAGER = 'general_manager';
    case OUTLET_MANAGER = 'outlet_manager';
    case SUPERVISOR = 'supervisor';

    // Operational
    case CASHIER = 'cashier';
    case BARISTA = 'barista';
    case KITCHEN = 'kitchen';
    case WAITER = 'waiter';

    // Inventory
    case INVENTORY_ADMIN = 'inventory_admin';

    public function label(): string
    {
        return match ($this) {
            self::OWNER => 'Pemilik Usaha',
            self::GENERAL_MANAGER => 'General Manager',
            self::OUTLET_MANAGER => 'Manager Outlet',
            self::SUPERVISOR => 'Supervisor',

            self::CASHIER => 'Kasir',
            self::BARISTA => 'Barista',
            self::KITCHEN => 'Kitchen',
            self::WAITER => 'Waiter',

            self::INVENTORY_ADMIN => 'Admin Inventori',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $role) => [
                $role->value => $role->label(),
            ])
            ->toArray();
    }
}
