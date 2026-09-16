<?php

namespace Tests\Feature\Settings;

use App\Enums\PermissionEnum;
use App\Enums\PlanEnum;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class RoleControllerTest extends TestCase
{
    use RefreshDatabase;

    protected string $appDomain;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.testing.page_paths' => [resource_path('js/Pages/App')]]);
        $this->seed(DatabaseSeeder::class);
        $this->appDomain = config('domain.app', 'app.sollu.test');
    }

    protected function createMerchantUser(): User
    {
        $type = \App\Models\BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = \App\Models\Business::create([
            'name' => 'Merchant Test Business',
            'owner_name' => 'Merchant Owner',
            'email' => 'merchant_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $user = User::create([
            'business_id' => $business->id,
            'name' => 'Merchant User',
            'email' => 'user_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        setPermissionsTeamId($business->id);

        \App\Models\Role::create([
            'business_id' => $business->id,
            'name' => 'cashier',
            'label' => 'Kasir',
            'guard_name' => 'business',
            'is_default' => true,
        ]);

        $ownerRole = \App\Models\Role::create([
            'business_id' => $business->id,
            'name' => 'owner',
            'label' => 'Owner',
            'guard_name' => 'business',
            'is_default' => true,
        ]);

        $user->assignRole($ownerRole);
        $user->givePermissionTo(PermissionEnum::ROLE_VIEW->value);

        return $user;
    }

    protected function subscribeBusinessToPlan(User $user, PlanEnum $planEnum = PlanEnum::BASIC): void
    {
        $plan = SubscriptionPlan::where('code', $planEnum->value)->first();
        Subscription::create([
            'business_id' => $user->business_id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'started_at' => Carbon::now()->subDays(1),
            'expired_at' => Carbon::now()->addDays(29),
        ]);
    }

    public function test_unauthenticated_user_cannot_access_role_page(): void
    {
        $response = $this->get("http://{$this->appDomain}/settings/roles");

        $response->assertStatus(302);
    }

    public function test_user_without_permission_cannot_access_role_page(): void
    {
        $user = $this->createMerchantUser();
        $this->subscribeBusinessToPlan($user);
        $user->syncPermissions([]);
        $user->syncRoles([]);

        $response = $this->actingAs($user, 'business')->get("http://{$this->appDomain}/settings/roles");

        $response->assertRedirect();
        $response->assertSessionHas('failed');
    }

    public function test_user_without_plan_feature_is_redirected_with_feature_locked(): void
    {
        $user = $this->createMerchantUser();
        setPermissionsTeamId($user->business_id);

        // User business is on trial (Micro) which does not have ROLE_PERMISSIONS
        $response = $this->actingAs($user, 'business')->get("http://{$this->appDomain}/settings/roles");

        $response->assertRedirect();
        $response->assertSessionHas('feature_locked');
    }

    public function test_authorized_user_can_access_role_page_with_permission_enum(): void
    {
        $user = $this->createMerchantUser();
        $this->subscribeBusinessToPlan($user);
        setPermissionsTeamId($user->business_id);

        $response = $this->actingAs($user, 'business')->get("http://{$this->appDomain}/settings/roles");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Settings/Role/Index')
            ->has('roles')
            ->has('enums.PermissionEnum')
            ->where('enums.PermissionEnum.ROLE_CREATE', PermissionEnum::ROLE_CREATE->value)
            ->has('enums.PermissionEnum._grouped')
        );
    }

    public function test_user_can_create_custom_role(): void
    {
        $user = $this->createMerchantUser();
        $this->subscribeBusinessToPlan($user);
        setPermissionsTeamId($user->business_id);

        $response = $this->actingAs($user, 'business')->post("http://{$this->appDomain}/settings/roles", [
            'label' => 'Staff Keuangan',
            'permissions' => [
                PermissionEnum::REPORT_SALES->value,
                PermissionEnum::REPORT_INVENTORY->value,
            ],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('roles', [
            'business_id' => $user->business_id,
            'label' => 'Staff Keuangan',
            'is_default' => false,
        ]);
    }

    public function test_authorized_user_can_search_roles_by_query(): void
    {
        $user = $this->createMerchantUser();
        $this->subscribeBusinessToPlan($user);
        setPermissionsTeamId($user->business_id);

        $response = $this->actingAs($user, 'business')->get("http://{$this->appDomain}/settings/roles?search=Kasir");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Settings/Role/Index')
            ->has('roles', 1)
            ->where('roles.0.name', 'cashier')
            ->where('filters.search', 'Kasir')
        );
    }

    public function test_authorized_user_can_view_role_details_with_permissions(): void
    {
        $user = $this->createMerchantUser();
        $this->subscribeBusinessToPlan($user);
        setPermissionsTeamId($user->business_id);

        $role = \App\Models\Role::where('business_id', $user->business_id)->first();

        $response = $this->actingAs($user, 'business')->get("http://{$this->appDomain}/settings/roles/{$role->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'label',
            'name',
            'is_default',
            'permissions',
        ]);
        $this->assertEquals($role->id, $response->json('id'));
    }

    public function test_user_cannot_view_role_of_another_business(): void
    {
        $user = $this->createMerchantUser();
        $this->subscribeBusinessToPlan($user);
        setPermissionsTeamId($user->business_id);

        $otherBusinessId = (string) \Illuminate\Support\Str::uuid();
        $otherRole = \App\Models\Role::create([
            'business_id' => $otherBusinessId,
            'name' => 'other-role',
            'label' => 'Other Role',
            'guard_name' => 'business',
            'is_default' => false,
        ]);

        $response = $this->actingAs($user, 'business')->get("http://{$this->appDomain}/settings/roles/{$otherRole->id}");

        $response->assertStatus(403);
    }
}
