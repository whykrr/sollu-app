<?php

use App\Enums\PermissionEnum;
use App\Models\Role;
use App\Services\Auth\UserPermissionCacheService;
use App\Support\Enums\FrontendEnumProvider;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create a reversible backup table to store removed deprecated permissions and pivot relations
        if (! Schema::hasTable('permissions_deprecated_backup')) {
            Schema::create('permissions_deprecated_backup', function (Blueprint $table) {
                $table->unsignedBigInteger('id')->primary();
                $table->string('name');
                $table->string('guard_name');
                $table->json('roles_payload')->nullable();
                $table->json('models_payload')->nullable();
                $table->timestamps();
            });
        }

        // 2. Identify deprecated permissions not in PermissionEnum or with wrong guard (e.g. guard web)
        $validPermissionValues = PermissionEnum::values();

        $deprecatedPermissions = DB::table('permissions')
            ->whereNotIn('name', $validPermissionValues)
            ->orWhere('guard_name', '!=', 'business')
            ->get();

        $deprecatedIds = $deprecatedPermissions->pluck('id')->all();

        if (! empty($deprecatedIds)) {
            $rolesPivot = DB::table('role_has_permissions')
                ->whereIn('permission_id', $deprecatedIds)
                ->get()
                ->groupBy('permission_id');

            $modelsPivot = DB::table('model_has_permissions')
                ->whereIn('permission_id', $deprecatedIds)
                ->get()
                ->groupBy('permission_id');

            $backupRows = $deprecatedPermissions->map(function ($perm) use ($rolesPivot, $modelsPivot) {
                $rolesData = $rolesPivot->get($perm->id, collect())->map(fn ($item) => [
                    'permission_id' => $item->permission_id,
                    'role_id' => $item->role_id,
                ])->values()->all();

                $modelsData = $modelsPivot->get($perm->id, collect())->map(fn ($item) => [
                    'permission_id' => $item->permission_id,
                    'model_type' => $item->model_type,
                    'model_id' => $item->model_id,
                    'business_id' => $item->business_id,
                ])->values()->all();

                return [
                    'id' => $perm->id,
                    'name' => $perm->name,
                    'guard_name' => $perm->guard_name,
                    'roles_payload' => ! empty($rolesData) ? json_encode($rolesData) : null,
                    'models_payload' => ! empty($modelsData) ? json_encode($modelsData) : null,
                    'created_at' => $perm->created_at ?? now(),
                    'updated_at' => $perm->updated_at ?? now(),
                ];
            })->all();

            DB::table('permissions_deprecated_backup')->insertOrIgnore($backupRows);

            // Remove relations and deprecated permissions
            DB::table('role_has_permissions')->whereIn('permission_id', $deprecatedIds)->delete();
            DB::table('model_has_permissions')->whereIn('permission_id', $deprecatedIds)->delete();
            DB::table('permissions')->whereIn('id', $deprecatedIds)->delete();
        }

        // 3. Synchronize all tenant default owner roles to have all 101 current permissions
        $ownerRoles = Role::where('name', 'owner')
            ->where('is_default', true)
            ->get();

        $allPermissions = PermissionEnum::values();

        foreach ($ownerRoles as $role) {
            setPermissionsTeamId($role->business_id);
            $role->syncPermissions($allPermissions);
        }

        // 4. Invalidate permission and enum caches
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if (app()->bound(UserPermissionCacheService::class)) {
            app(UserPermissionCacheService::class)->clearRuntimeCache();
        }

        FrontendEnumProvider::clearCache();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('permissions_deprecated_backup')) {
            $backupRows = DB::table('permissions_deprecated_backup')->get();

            foreach ($backupRows as $row) {
                DB::table('permissions')->insertOrIgnore([
                    'id' => $row->id,
                    'name' => $row->name,
                    'guard_name' => $row->guard_name,
                    'created_at' => $row->created_at,
                    'updated_at' => $row->updated_at,
                ]);

                if (! empty($row->roles_payload)) {
                    $roles = json_decode($row->roles_payload, true) ?: [];
                    if (! empty($roles)) {
                        DB::table('role_has_permissions')->insertOrIgnore($roles);
                    }
                }

                if (! empty($row->models_payload)) {
                    $models = json_decode($row->models_payload, true) ?: [];
                    if (! empty($models)) {
                        DB::table('model_has_permissions')->insertOrIgnore($models);
                    }
                }
            }

            Schema::dropIfExists('permissions_deprecated_backup');
        }

        // Invalidate permission and enum caches
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        if (app()->bound(UserPermissionCacheService::class)) {
            app(UserPermissionCacheService::class)->clearRuntimeCache();
        }

        FrontendEnumProvider::clearCache();
    }
};
