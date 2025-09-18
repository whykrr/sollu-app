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
            'merchant.*'       => 'Management Merchant',
            'merchant.info'    => 'Akses Informasi',
            'merchant.billing' => 'Akses Langganan dan Tagihan',
            'merchant.setting' => 'Akses Pengaturan',
            'user.*'           => 'Management User',
            'user.view'        => 'View',
            'user.create'      => 'Create',
            'user.update'      => 'Update',
            'user.delete'      => 'Delete',
            'product.*'        => 'Management Product',
            'product.view'     => 'View',
            'product.create'   => 'Create',
            'product.update'   => 'Update',
            'product.delete'   => 'Delete',
        ];

        foreach ($permissions as $perm => $label) {
            Permission::create(['name' => $perm, 'label' => $label]);
        }

        $roles = [
            'owner' => [
                'label'      => 'Pemilik Bisnis',
                'permission' => [
                    'merchant.*',
                    'user.*',
                    'product.*',
                ],
            ],
            'manager' => [
                'label'      => 'Manajer',
                'permission' => [
                    'user.*',
                    'product.*',
                ],

            ],
            'supervisor' => [
                'label'      => 'Supervisi',
                'permission' => [
                    'user.view',
                    'product.view',
                    'product.update',
                ],

            ],
            'cashier' => [
                'label'      => 'Kasir',
                'permission' => [
                    'user.view',
                    'product.view',
                ],
            ],
            'staff' => [
                'label'      => 'Staf',
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
