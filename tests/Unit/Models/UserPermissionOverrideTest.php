<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Enums\PermissionEnum;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Permission;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserPermissionOverrideTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;

    protected User $rootUser;

    protected User $regularUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $this->business = Business::create([
            'name' => 'Test Business',
            'owner_name' => 'Test Owner',
            'email' => 'owner_override_'.uniqid().'@test.com',
            'phone' => '08123456789',
            'status' => 'active',
            'business_type_id' => $type->id,
        ]);

        $this->rootUser = User::create([
            'business_id' => $this->business->id,
            'name' => 'Root User',
            'email' => 'root_override_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
            'is_root_user' => true,
        ]);

        $this->regularUser = User::create([
            'business_id' => $this->business->id,
            'name' => 'Regular User',
            'email' => 'reg_override_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
            'is_root_user' => false,
        ]);

        setPermissionsTeamId($this->business->id);
    }

    public function test_root_user_has_wildcard_permission_and_bypasses_all_checks(): void
    {
        // hasPermissionTo returns true for any permission
        $this->assertTrue($this->rootUser->hasPermissionTo('any.arbitrary.permission'));
        $this->assertTrue($this->rootUser->hasPermissionTo(PermissionEnum::TRANSACTION_VALIDATION_SUPERVISION->value));

        // can returns true for any ability
        $this->assertTrue($this->rootUser->can('some.wildcard.ability'));
        $this->assertTrue($this->rootUser->can(PermissionEnum::TRANSACTION_VALIDATION_SUPERVISION->value));

        // hasAnyPermission returns true
        $this->assertTrue($this->rootUser->hasAnyPermission('non.existent.perm.a', 'non.existent.perm.b'));

        // hasAllPermissions returns true
        $this->assertTrue($this->rootUser->hasAllPermissions('perm.1', 'perm.2', 'perm.3'));

        // hasDirectPermission returns true
        $this->assertTrue($this->rootUser->hasDirectPermission('direct.perm'));

        // getAllPermissions returns collection containing all permissions
        $allPermissions = $this->rootUser->getAllPermissions();
        $this->assertGreaterThan(0, $allPermissions->count());
        $this->assertTrue($allPermissions->contains('name', PermissionEnum::TRANSACTION_VALIDATION_SUPERVISION->value));
    }

    public function test_regular_user_without_permission_fails_checks(): void
    {
        $this->assertFalse($this->regularUser->hasPermissionTo(PermissionEnum::TRANSACTION_VALIDATION_SUPERVISION->value));
        $this->assertFalse($this->regularUser->can(PermissionEnum::TRANSACTION_VALIDATION_SUPERVISION->value));
        $this->assertFalse($this->regularUser->hasDirectPermission(PermissionEnum::TRANSACTION_VALIDATION_SUPERVISION->value));
        $this->assertCount(0, $this->regularUser->getAllPermissions());
    }

    public function test_regular_user_with_assigned_permission_passes(): void
    {
        $this->regularUser->givePermissionTo(PermissionEnum::TRANSACTION_VIEW->value);

        $this->assertTrue($this->regularUser->hasPermissionTo(PermissionEnum::TRANSACTION_VIEW->value));
        $this->assertTrue($this->regularUser->can(PermissionEnum::TRANSACTION_VIEW->value));
        $this->assertTrue($this->regularUser->hasDirectPermission(PermissionEnum::TRANSACTION_VIEW->value));
        $this->assertFalse($this->regularUser->hasPermissionTo(PermissionEnum::TRANSACTION_VALIDATION_SUPERVISION->value));

        $permissions = $this->regularUser->getAllPermissions();
        $this->assertTrue($permissions->contains('name', PermissionEnum::TRANSACTION_VIEW->value));
        $this->assertFalse($permissions->contains('name', PermissionEnum::TRANSACTION_VALIDATION_SUPERVISION->value));
    }
}
