<?php

namespace Tests\Unit\Services;

use App\Enums\PermissionEnum;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Role;
use App\Models\User;
use App\Services\Auth\UserPermissionCacheService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class UserPermissionCacheServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UserPermissionCacheService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->service = app(UserPermissionCacheService::class);
    }

    protected function createMerchantUser(): array
    {
        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = Business::create([
            'name' => 'Cache Test Business',
            'owner_name' => 'Cache Owner',
            'email' => 'cache_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $user = User::create([
            'business_id' => $business->id,
            'name' => 'Cache Test User',
            'email' => 'cache_user_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        setPermissionsTeamId($business->id);

        $role = Role::create([
            'business_id' => $business->id,
            'name' => 'manager_'.uniqid(),
            'label' => 'Manager',
            'guard_name' => 'business',
            'is_default' => false,
        ]);

        $role->syncPermissions([
            PermissionEnum::PRODUCT_VIEW->value,
            PermissionEnum::CUSTOMER_VIEW->value,
        ]);

        $user->assignRole($role);

        return [$user, $business, $role];
    }

    public function test_it_caches_user_permissions(): void
    {
        [$user, $business] = $this->createMerchantUser();

        $cacheKey = UserPermissionCacheService::getCacheKey($user->id, $business->id);
        $this->assertFalse(Cache::has($cacheKey));

        $permissions = $this->service->getPermissions($user, $business->id);

        $this->assertContains(PermissionEnum::PRODUCT_VIEW->value, $permissions);
        $this->assertContains(PermissionEnum::CUSTOMER_VIEW->value, $permissions);
        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_has_permission_checks_cache_and_wildcard(): void
    {
        [$user, $business, $role] = $this->createMerchantUser();

        $this->assertTrue($this->service->hasPermission($user, PermissionEnum::PRODUCT_VIEW->value, $business->id));
        $this->assertFalse($this->service->hasPermission($user, PermissionEnum::USER_DELETE->value, $business->id));

        // Test wildcard
        $role->syncPermissions(['transaction.*']);
        $this->service->clearUserPermissions($user, $business->id);

        $this->assertTrue($this->service->hasPermission($user, PermissionEnum::TRANSACTION_VIEW->value, $business->id));
        $this->assertTrue($this->service->hasPermission($user, PermissionEnum::TRANSACTION_CREATE->value, $business->id));
        $this->assertFalse($this->service->hasPermission($user, PermissionEnum::PRODUCT_VIEW->value, $business->id));
    }

    public function test_gate_before_uses_cached_permissions(): void
    {
        [$user, $business] = $this->createMerchantUser();
        setPermissionsTeamId($business->id);

        $this->actingAs($user, 'business');

        $this->assertTrue(Gate::allows(PermissionEnum::PRODUCT_VIEW->value));
        $this->assertFalse(Gate::allows(PermissionEnum::USER_DELETE->value));
    }

    public function test_root_user_bypasses_all_permission_checks(): void
    {
        [$user, $business] = $this->createMerchantUser();
        $user->update(['is_root_user' => true]);

        $this->actingAs($user, 'business');

        $this->assertTrue(Gate::allows(PermissionEnum::USER_DELETE->value));
        $this->assertTrue(Gate::allows('any.nonexistent.permission'));
    }

    public function test_invalidation_clears_user_permission_cache(): void
    {
        [$user, $business] = $this->createMerchantUser();

        // Warm cache
        $this->service->getPermissions($user, $business->id);
        $cacheKey = UserPermissionCacheService::getCacheKey($user->id, $business->id);
        $this->assertTrue(Cache::has($cacheKey));

        // Invalidate
        $this->service->clearUserPermissions($user, $business->id);
        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_role_update_triggers_cache_invalidation(): void
    {
        [$user, $business, $role] = $this->createMerchantUser();

        // Warm cache
        $this->service->getPermissions($user, $business->id);
        $cacheKey = UserPermissionCacheService::getCacheKey($user->id, $business->id);
        $this->assertTrue(Cache::has($cacheKey));

        // Update role
        $role->update(['label' => 'Updated Manager Label']);

        // Cache for user in that business should be invalidated
        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_runtime_memoization_returns_cached_permissions_without_hitting_external_cache(): void
    {
        [$user, $business] = $this->createMerchantUser();

        $perms1 = $this->service->getPermissions($user, $business->id);
        $this->assertContains(PermissionEnum::PRODUCT_VIEW->value, $perms1);

        // Manually flush underlying cache store to verify runtime in-memory cache
        $cacheKey = UserPermissionCacheService::getCacheKey($user->id, $business->id);
        Cache::forget($cacheKey);
        $this->assertFalse(Cache::has($cacheKey));

        // Calling getPermissions should still return from runtime memory
        $perms2 = $this->service->getPermissions($user, $business->id);
        $this->assertSame($perms1, $perms2);

        // Clear runtime cache
        $this->service->clearRuntimeCache();
        // Now getting permissions should repopulate cache
        $perms3 = $this->service->getPermissions($user, $business->id);
        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_user_non_structural_update_does_not_invalidate_permission_cache(): void
    {
        [$user, $business] = $this->createMerchantUser();

        // Warm cache
        $this->service->getPermissions($user, $business->id);
        $cacheKey = UserPermissionCacheService::getCacheKey($user->id, $business->id);
        $this->assertTrue(Cache::has($cacheKey));

        // Update user profile/photo/login
        $user->update([
            'name' => 'Updated User Name',
            'last_login_at' => now(),
        ]);

        // Permission cache should still be intact
        $this->assertTrue(Cache::has($cacheKey));
    }

    public function test_user_structural_change_invalidates_permission_cache(): void
    {
        [$user, $business] = $this->createMerchantUser();

        // Warm cache
        $this->service->getPermissions($user, $business->id);
        $cacheKey = UserPermissionCacheService::getCacheKey($user->id, $business->id);
        $this->assertTrue(Cache::has($cacheKey));

        // Update structural auth attribute
        $user->update(['is_root_user' => true]);

        // Permission cache must be invalidated
        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_gate_authorization_executes_zero_database_queries_when_cache_is_warmed(): void
    {
        [$user, $business] = $this->createMerchantUser();
        setPermissionsTeamId($business->id);

        // Warm cache initially
        $this->service->getPermissions($user, $business->id);
        $this->service->clearRuntimeCache(); // Clear in-memory memoization to verify external cache read

        $this->actingAs($user, 'business');

        DB::flushQueryLog();
        DB::enableQueryLog();

        // Perform multiple authorization checks
        $this->assertTrue(Gate::allows(PermissionEnum::PRODUCT_VIEW->value));
        $this->assertTrue(Gate::allows(PermissionEnum::CUSTOMER_VIEW->value));
        $this->assertFalse(Gate::allows(PermissionEnum::USER_DELETE->value));
        $this->assertTrue($user->can(PermissionEnum::PRODUCT_VIEW->value));

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        $this->assertEmpty($queries, 'Expected 0 database queries for authorization when cache is warm, but queries were executed: '.json_encode($queries));
    }
}
