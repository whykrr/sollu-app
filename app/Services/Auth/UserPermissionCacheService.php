<?php

namespace App\Services\Auth;

use App\Models\Business;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class UserPermissionCacheService
{
    public const CACHE_TTL = 3600; // 1 hour (60 minutes)

    /**
     * In-memory request-level cache to prevent redundant Redis/DB lookups in the same request lifecycle.
     *
     * @var array<string, array<int, string>>
     */
    private array $runtimePermissions = [];

    /**
     * Get the cache key for user permissions scoped by business.
     */
    public static function getCacheKey(string $userId, ?string $businessId): string
    {
        $businessKey = $businessId ?? 'global';

        return "auth:user:{$userId}:business:{$businessKey}:permissions";
    }

    /**
     * Retrieve all cached permission names for a user in a business context.
     *
     * @return array<int, string>
     */
    public function getPermissions(User $user, ?string $businessId = null): array
    {
        $businessId = $businessId ?? $user->business_id;
        $cacheKey = self::getCacheKey($user->id, $businessId);

        if (isset($this->runtimePermissions[$cacheKey])) {
            return $this->runtimePermissions[$cacheKey];
        }

        $permissions = Cache::remember($cacheKey, self::CACHE_TTL, function () use ($user, $businessId) {
            $currentTeamId = getPermissionsTeamId();
            if ($businessId && $currentTeamId !== $businessId) {
                setPermissionsTeamId($businessId);
            }

            try {
                $user->unsetRelation('roles');
                $user->unsetRelation('permissions');
                $permissions = $user->getAllPermissions()->pluck('name')->unique()->values()->toArray();
            } finally {
                if ($businessId && $currentTeamId !== $businessId) {
                    setPermissionsTeamId($currentTeamId);
                }
            }

            return $permissions;
        });

        $this->runtimePermissions[$cacheKey] = $permissions;

        return $permissions;
    }

    /**
     * Check if a user has a specific permission from cache (with wildcard support).
     */
    public function hasPermission(User $user, string $permission, ?string $businessId = null): bool
    {
        $permissions = $this->getPermissions($user, $businessId);

        if (in_array('*', $permissions, true) || in_array($permission, $permissions, true)) {
            return true;
        }

        foreach ($permissions as $cachedPerm) {
            if (str_ends_with($cachedPerm, '.*')) {
                $prefix = substr($cachedPerm, 0, -2);
                if (str_starts_with($permission, $prefix.'.') || $permission === $prefix) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Invalidate cached permissions for a specific user.
     */
    public function clearUserPermissions(string|User $user, ?string $businessId = null): void
    {
        $userId = $user instanceof User ? $user->id : $user;
        $resolvedBusinessId = $businessId ?? ($user instanceof User ? $user->business_id : null);

        if ($resolvedBusinessId) {
            $key = self::getCacheKey($userId, $resolvedBusinessId);
            unset($this->runtimePermissions[$key]);
            Cache::forget($key);
        }

        $globalKey = self::getCacheKey($userId, null);
        unset($this->runtimePermissions[$globalKey]);
        Cache::forget($globalKey);

        foreach (array_keys($this->runtimePermissions) as $key) {
            if (str_starts_with($key, "auth:user:{$userId}:business:")) {
                unset($this->runtimePermissions[$key]);
            }
        }

        Cache::forgetPattern("auth:user:{$userId}:business:*:permissions");
    }

    /**
     * Invalidate cached permissions for all users in a business.
     */
    public function clearBusinessPermissions(string|Business $business): void
    {
        $businessId = $business instanceof Business ? $business->id : $business;

        foreach (array_keys($this->runtimePermissions) as $key) {
            if (str_contains($key, ":business:{$businessId}:")) {
                unset($this->runtimePermissions[$key]);
            }
        }

        Cache::forgetPattern("auth:user:*:business:{$businessId}:permissions");

        if (config('cache.default') !== 'redis') {
            $users = User::where('business_id', $businessId)->get(['id', 'business_id']);
            foreach ($users as $user) {
                $this->clearUserPermissions($user->id, $user->business_id);
            }
        }
    }

    /**
     * Clear all in-memory runtime permissions.
     */
    public function clearRuntimeCache(): void
    {
        $this->runtimePermissions = [];
    }
}
