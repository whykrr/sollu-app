<?php

namespace Database\Seeders\V1_0;

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
            'user.*'       => 'Management User',
            'user.view'    => 'View', 'user.create' => 'Create', 'user.update' => 'Update', 'user.delete' => 'Delete',
            'product.*'    => 'Management Product',
            'product.view' => 'View', 'product.create' => 'Create', 'product.update' => 'Update', 'product.delete' => 'Delete',
        ];

        foreach ($permissions as $perm => $label) {
            Permission::firstOrCreate(['name' => $perm, 'label' => $label]);
        }

        $roles = [
            'owner' => [
                'label'      => 'Owner',
                'permission' => [
                    'user.*',
                    'product.*',
                ],
            ],
            'manager' => [
                'label'      => 'Manager',
                'permission' => [
                    'user.view',
                    'user.create',
                    'user.update',
                    'product.*',
                ],

            ],
            'cashier' => [
                'label'      => 'Cashier',
                'permission' => [
                    'user.view',
                    'product.view',
                    'product.update',
                ],

            ],
        ];

        foreach ($roles as $role => $item) {
            $role = Role::create(['name' => $role, 'label' => $item['label']]);
            $role->givePermissionTo($item['permission']);
        }
    }
}
