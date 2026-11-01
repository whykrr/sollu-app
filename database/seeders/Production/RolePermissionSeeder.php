<?php

namespace Database\Seeders\Production;

use App\Enums\PermissionEnum;
use App\Models\Permission;
use App\Services\Auth\UserPermissionCacheService;
use App\Support\Enums\FrontendEnumProvider;
use Illuminate\Database\Seeder;
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

        foreach (array_chunk($records, 50) as $chunk) {
            Permission::upsert(
                $chunk,
                ['name', 'guard_name'],
                ['updated_at']
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if (app()->bound(UserPermissionCacheService::class)) {
            app(UserPermissionCacheService::class)->clearRuntimeCache();
        }

        FrontendEnumProvider::clearCache();
    }
}
