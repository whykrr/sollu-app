<?php

namespace Database\Seeders;

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
        $permissions = [
            'business.*'       => 'Business Management',
            'business.info'    => 'Business Info',
            'business.billing' => 'Business Billing',
            'business.setting' => 'Business Setting',
            'outlet.*'         => 'Outlet Management',
            'outlet.view'      => 'View',
            'outlet.create'    => 'Create',
            'outlet.update'    => 'Update',
            'outlet.delete'    => 'Delete',
            'user.*'           => 'User Management',
            'user.view'        => 'View',
            'user.create'      => 'Create',
            'user.update'      => 'Update',
            'user.delete'      => 'Delete',
            'product.*'        => 'Product Management',
            'product.view'     => 'View',
            'product.create'   => 'Create',
            'product.update'   => 'Update',
            'product.delete'   => 'Delete',
            'inventory.*'      => 'Inventory Management',
            'inventory.view'   => 'View',
            'inventory.create' => 'Create',
            'inventory.update' => 'Update',
            'inventory.delete' => 'Delete',
            'promo.*'          => 'Promo Management',
            'promo.view'       => 'View',
            'promo.create'     => 'Create',
            'promo.update'     => 'Update',
            'promo.delete'     => 'Delete',
        ];

        foreach ($permissions as $perm => $label) {
            Permission::create(['name' => $perm, 'label' => $label]);
        }

        $roles = [
            'owner' => [
                'label'      => 'Business Owner',
                'permission' => [
                    'business.*',
                    'user.*',
                    'product.*',
                    'inventory.*',
                    'promo.*',
                ],
            ],
            'manager' => [
                'label'      => 'Manager',
                'permission' => [
                    'user.*',
                    'product.*',
                    'inventory.*',
                    'promo.*',
                ],

            ],
            'supervisor' => [
                'label'      => 'Supervisor',
                'permission' => [
                    'user.view',
                    'product.view',
                    'product.update',
                    'inventory.view',
                    'inventory.create',
                    'inventory.update',
                ],

            ],
            'cashier' => [
                'label'      => 'Cashier',
                'permission' => [
                    'user.view',
                    'product.view',
                ],
            ],
            'staff' => [
                'label'      => 'Staff',
                'permission' => [
                    'user.view',
                    'product.view',
                ],

            ],
        ];

        foreach ($roles as $role => $item) {
            $role = Role::create(['name' => $role, 'label' => $item['label']]);
            $role->givePermissionTo($item['permission']);
        }
    }
}
