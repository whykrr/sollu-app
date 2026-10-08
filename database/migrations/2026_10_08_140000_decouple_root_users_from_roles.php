<?php

use App\Enums\RoleEnum;
use App\Models\Role;
use App\Models\User;
use App\Services\Auth\UserPermissionCacheService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Decouple root users from any assigned roles or direct permissions.
     */
    public function up(): void
    {
        $rootUserIds = User::where('is_root_user', true)->pluck('id')->toArray();

        if (! empty($rootUserIds)) {
            DB::table('model_has_roles')
                ->where('model_type', User::class)
                ->whereIn('model_id', $rootUserIds)
                ->delete();

            DB::table('model_has_permissions')
                ->where('model_type', User::class)
                ->whereIn('model_id', $rootUserIds)
                ->delete();
        }

        if (app()->bound(UserPermissionCacheService::class)) {
            app(UserPermissionCacheService::class)->clearRuntimeCache();
        }
    }

    /**
     * Reverse the migrations.
     * Restore owner role assignment to root users for absolute rollback symmetry.
     */
    public function down(): void
    {
        $rootUsers = User::where('is_root_user', true)->get(['id', 'business_id']);

        foreach ($rootUsers as $rootUser) {
            if (! $rootUser->business_id) {
                continue;
            }

            $ownerRole = Role::where('business_id', $rootUser->business_id)
                ->where('name', RoleEnum::OWNER->value)
                ->first();

            if ($ownerRole) {
                DB::table('model_has_roles')->updateOrInsert(
                    [
                        'role_id' => $ownerRole->id,
                        'model_type' => User::class,
                        'model_id' => $rootUser->id,
                    ],
                    [
                        'business_id' => $rootUser->business_id,
                    ]
                );
            }
        }

        if (app()->bound(UserPermissionCacheService::class)) {
            app(UserPermissionCacheService::class)->clearRuntimeCache();
        }
    }
};
