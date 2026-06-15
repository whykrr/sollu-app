<?php

namespace Database\Seeders;

use App\Enum\PermissionEnum;
use App\Enum\RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        /*
        * Create Permissions
        */
        foreach (PermissionEnum::cases() as $permission) {
            Permission::findOrCreate(
                $permission->value,
                'business'
            );
        }

        /*
        * Create Roles
        */
        foreach (RoleEnum::cases() as $role) {
            Role::findOrCreate(
                $role->value,
                'business'
            );
        }

        /*
        * Owner
        */
        $owner = Role::findOrCreate(
            RoleEnum::OWNER->value,
            'business'
        );

        $owner->syncPermissions(PermissionEnum::values());

        /*
        * General Manager
        */
        $generalManager = Role::findOrCreate(
            RoleEnum::GENERAL_MANAGER->value,
            'business'
        );

        $generalManager->syncPermissions([
            PermissionEnum::BUSINESS_VIEW->value,
            PermissionEnum::OUTLET_ALL->value,
            PermissionEnum::USER_ALL->value,
            PermissionEnum::ROLE_VIEW->value,

            PermissionEnum::TRANSACTION_ALL->value,

            PermissionEnum::PRODUCT_ALL->value,
            PermissionEnum::CATEGORY_ALL->value,

            PermissionEnum::INVENTORY_ALL->value,

            PermissionEnum::SUPPLIER_ALL->value,
            PermissionEnum::PURCHASE_ORDER_ALL->value,

            PermissionEnum::PROMO_ALL->value,
            PermissionEnum::CUSTOMER_ALL->value,

            PermissionEnum::REPORT_ALL->value,
        ]);

        /*
        * Outlet Manager
        */
        $outletManager = Role::findOrCreate(
            RoleEnum::OUTLET_MANAGER->value,
            'business'
        );

        $outletManager->syncPermissions([
            PermissionEnum::OUTLET_VIEW->value,

            PermissionEnum::TRANSACTION_ALL->value,

            PermissionEnum::PRODUCT_VIEW->value,
            PermissionEnum::PRODUCT_UPDATE->value,

            PermissionEnum::CATEGORY_VIEW->value,

            PermissionEnum::INVENTORY_VIEW->value,
            PermissionEnum::INVENTORY_ADJUST->value,
            PermissionEnum::INVENTORY_TRANSFER->value,
            PermissionEnum::INVENTORY_STOCK_OPNAME->value,

            PermissionEnum::PROMO_VIEW->value,

            PermissionEnum::CUSTOMER_VIEW->value,

            PermissionEnum::REPORT_SALES->value,
            PermissionEnum::REPORT_INVENTORY->value,
            PermissionEnum::REPORT_SHIFT->value,
            PermissionEnum::REPORT_PRODUCT->value,
        ]);

        /*
        * Supervisor
        */
        $supervisor = Role::findOrCreate(
            RoleEnum::SUPERVISOR->value,
            'business'
        );

        $supervisor->syncPermissions([
            PermissionEnum::TRANSACTION_VIEW->value,
            PermissionEnum::TRANSACTION_CREATE->value,
            PermissionEnum::TRANSACTION_HOLD->value,
            PermissionEnum::TRANSACTION_REPRINT->value,

            PermissionEnum::PRODUCT_VIEW->value,

            PermissionEnum::INVENTORY_VIEW->value,

            PermissionEnum::REPORT_SALES->value,
        ]);

        /*
        * Cashier
        */
        $cashier = Role::findOrCreate(
            RoleEnum::CASHIER->value,
            'business'
        );

        $cashier->syncPermissions([
            PermissionEnum::TRANSACTION_VIEW->value,
            PermissionEnum::TRANSACTION_CREATE->value,
            PermissionEnum::TRANSACTION_HOLD->value,
            PermissionEnum::TRANSACTION_REPRINT->value,

            PermissionEnum::PRODUCT_VIEW->value,

            PermissionEnum::CUSTOMER_VIEW->value,
            PermissionEnum::CUSTOMER_CREATE->value,
        ]);

        /*
        * Barista
        */
        $barista = Role::findOrCreate(
            RoleEnum::BARISTA->value,
            'business'
        );

        $barista->syncPermissions([
            PermissionEnum::TRANSACTION_VIEW->value,

            PermissionEnum::PRODUCT_VIEW->value,
        ]);

        /*
        * Kitchen
        */

        $kitchen = Role::findOrCreate(
            RoleEnum::KITCHEN->value,
            'business'
        );

        $kitchen->syncPermissions([
            PermissionEnum::TRANSACTION_VIEW->value,

            PermissionEnum::PRODUCT_VIEW->value,
        ]);

        /*
        * Waiter
        */
        $waiter = Role::findOrCreate(
            RoleEnum::WAITER->value,
            'business'
        );

        $waiter->syncPermissions([
            PermissionEnum::TRANSACTION_VIEW->value,
            PermissionEnum::TRANSACTION_CREATE->value,

            PermissionEnum::PRODUCT_VIEW->value,

            PermissionEnum::CUSTOMER_VIEW->value,
        ]);

        /*
        * Inventory Admin
        */
        $inventoryAdmin = Role::findOrCreate(
            RoleEnum::INVENTORY_ADMIN->value,
            'business'
        );

        $inventoryAdmin->syncPermissions([
            PermissionEnum::PRODUCT_VIEW->value,

            PermissionEnum::CATEGORY_VIEW->value,

            PermissionEnum::INVENTORY_ALL->value,

            PermissionEnum::SUPPLIER_ALL->value,

            PermissionEnum::PURCHASE_ORDER_ALL->value,

            PermissionEnum::REPORT_INVENTORY->value,
        ]);
    }
}
