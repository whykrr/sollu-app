<?php

namespace Database\Seeders\Production;

use App\Enums\PermissionEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();
        $records = array_map(fn (PermissionEnum $permission) => [
            'name' => $permission->value,
            'guard_name' => 'business',
            'created_at' => $now,
            'updated_at' => $now,
        ], PermissionEnum::cases());

        Permission::upsert(
            $records,
            ['name', 'guard_name'],
            ['updated_at']
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
