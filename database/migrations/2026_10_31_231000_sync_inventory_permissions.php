<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Ensure inventory.update permission exists
        $exists = DB::table('permissions')->where('name', 'inventory.update')->exists();
        if (! $exists) {
            DB::table('permissions')->insert([
                'name' => 'inventory.update',
                'guard_name' => 'business',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // 2. Remove deprecated warehouse permissions
        $deprecatedWarehousePermissions = [
            'inventory.warehouse.view',
            'inventory.warehouse.create',
            'inventory.warehouse.update',
            'inventory.warehouse.delete',
        ];

        $deprecatedIds = DB::table('permissions')
            ->whereIn('name', $deprecatedWarehousePermissions)
            ->pluck('id');

        if ($deprecatedIds->isNotEmpty()) {
            DB::table('role_has_permissions')->whereIn('permission_id', $deprecatedIds)->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', $deprecatedIds)->delete();
            DB::table('permissions')->whereIn('id', $deprecatedIds)->delete();
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Remove inventory.update
        $updatePermission = DB::table('permissions')->where('name', 'inventory.update')->first();
        if ($updatePermission) {
            DB::table('role_has_permissions')->where('permission_id', $updatePermission->id)->delete();
            DB::table('model_has_permissions')->where('permission_id', $updatePermission->id)->delete();
            DB::table('permissions')->where('id', $updatePermission->id)->delete();
        }

        // 2. Re-insert deprecated warehouse permissions
        $warehousePermissions = [
            'inventory.warehouse.view',
            'inventory.warehouse.create',
            'inventory.warehouse.update',
            'inventory.warehouse.delete',
        ];

        foreach ($warehousePermissions as $perm) {
            if (! DB::table('permissions')->where('name', $perm)->exists()) {
                DB::table('permissions')->insert([
                    'name' => $perm,
                    'guard_name' => 'business',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
