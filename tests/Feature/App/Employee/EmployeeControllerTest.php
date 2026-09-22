<?php

namespace Tests\Feature\App\Employee;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Enums\PlanEnum;
use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Outlet;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class EmployeeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

    protected Outlet $outlet;

    protected Role $role;

    protected string $appDomain;

    protected function setUp(): void
    {
        parent::setUp();

        config(['inertia.testing.page_paths' => [
            resource_path('js/Pages'),
            resource_path('js/Pages/App'),
        ]]);

        $this->seed(DatabaseSeeder::class);
        $this->appDomain = config('domain.app', 'app.sollu.test');

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true, 'features' => [FeatureEnum::EMPLOYEE_MANAGEMENT->value]]
        );

        $this->business = Business::create([
            'name' => 'Test Business',
            'owner_name' => 'Business Owner',
            'email' => 'owner_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
            'settings' => [
                'active_features' => [FeatureEnum::EMPLOYEE_MANAGEMENT->value],
            ],
        ]);

        $basicPlan = SubscriptionPlan::where('code', PlanEnum::BASIC->value)->first();
        if ($basicPlan) {
            Subscription::create([
                'business_id' => $this->business->id,
                'plan_id' => $basicPlan->id,
                'status' => SubscriptionStatus::Active,
                'billing_cycle' => 'monthly',
                'started_at' => now()->subDay(),
                'expired_at' => now()->addMonth(),
            ]);
        }
        $this->business->clearMemoizedFeatures();

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Owner User',
            'email' => 'user_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
            'is_root_user' => true,
        ]);

        $this->outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Main Outlet',
            'is_main_outlet' => true,
        ]);

        $this->user->outlets()->attach($this->outlet->id);

        setPermissionsTeamId($this->business->id);

        foreach ([
            PermissionEnum::USER_VIEW->value,
            PermissionEnum::USER_CREATE->value,
            PermissionEnum::USER_UPDATE->value,
            PermissionEnum::USER_DELETE->value,
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'business']);
            $this->user->givePermissionTo($perm);
        }

        $this->role = Role::firstOrCreate(
            ['business_id' => $this->business->id, 'name' => 'cashier', 'guard_name' => 'business'],
            ['label' => 'Kasir Toko', 'is_default' => false]
        );
    }

    public function test_it_can_render_employee_index_page()
    {
        $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/employees")
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Employee/Index')
                ->has('users.data')
                ->has('params')
                ->has('roles')
            );
    }

    public function test_it_can_get_employee_detail_on_demand_via_show()
    {
        $employee = User::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'Staff Kasir',
            'email' => 'kasir@test.test',
            'is_root_user' => false,
        ]);
        $employee->assignRole('cashier');
        $employee->outlets()->attach($this->outlet->id);

        $response = $this->actingAs($this->user, 'business')
            ->getJson("http://{$this->appDomain}/employees/{$employee->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $employee->id)
            ->assertJsonPath('data.name', 'Staff Kasir')
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'email',
                    'roles',
                    'outlets',
                ],
            ]);
    }

    public function test_it_prevents_viewing_other_tenant_employee()
    {
        $otherBusiness = Business::create([
            'name' => 'Other Merchant',
            'owner_name' => 'Other Owner',
            'email' => 'other_'.uniqid().'@test.test',
            'phone' => '08999999999',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $this->business->business_type_id,
        ]);

        $otherEmployee = User::factory()->create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other Staff',
        ]);

        $this->actingAs($this->user, 'business')
            ->getJson("http://{$this->appDomain}/employees/{$otherEmployee->id}")
            ->assertForbidden();
    }

    public function test_it_can_create_new_employee()
    {
        $payload = [
            'name' => 'Karyawan Baru',
            'email' => 'baru_'.uniqid().'@test.test',
            'phone' => '081298765432',
            'role' => 'cashier',
            'outlets' => [$this->outlet->id],
            'pin' => '123456',
        ];

        $response = $this->actingAs($this->user, 'business')
            ->from("http://{$this->appDomain}/employees")
            ->post("http://{$this->appDomain}/employees", $payload);

        $response->assertRedirect("http://{$this->appDomain}/employees")
            ->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::CREATE_SUCCESS);

        $this->assertDatabaseHas('users', [
            'business_id' => $this->business->id,
            'name' => 'Karyawan Baru',
            'email' => $payload['email'],
            'is_root_user' => false,
        ]);
    }

    public function test_it_can_update_existing_employee()
    {
        $employee = User::factory()->create([
            'business_id' => $this->business->id,
            'name' => 'Old Name',
            'is_root_user' => false,
        ]);
        $employee->assignRole('cashier');
        $employee->outlets()->attach($this->outlet->id);

        $payload = [
            'name' => 'Updated Name',
            'email' => $employee->email,
            'phone' => '081299998888',
            'role' => 'cashier',
            'outlets' => [$this->outlet->id],
            'pin' => '654321',
        ];

        $response = $this->actingAs($this->user, 'business')
            ->from("http://{$this->appDomain}/employees")
            ->put("http://{$this->appDomain}/employees/{$employee->id}", $payload);

        $response->assertRedirect("http://{$this->appDomain}/employees")
            ->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::UPDATE_SUCCESS);

        $this->assertDatabaseHas('users', [
            'id' => $employee->id,
            'name' => 'Updated Name',
            'phone' => '081299998888',
        ]);
    }

    public function test_it_can_soft_delete_restore_and_destroy_employee()
    {
        $employee = User::factory()->create([
            'business_id' => $this->business->id,
            'is_root_user' => false,
        ]);

        // 1. Soft Delete
        $this->actingAs($this->user, 'business')
            ->from("http://{$this->appDomain}/employees")
            ->delete("http://{$this->appDomain}/employees/{$employee->id}")
            ->assertRedirect("http://{$this->appDomain}/employees")
            ->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::DELETE_SUCCESS);

        $this->assertSoftDeleted('users', ['id' => $employee->id]);

        // 2. Restore
        $this->actingAs($this->user, 'business')
            ->from("http://{$this->appDomain}/employees")
            ->put("http://{$this->appDomain}/employees/{$employee->id}/restore")
            ->assertRedirect("http://{$this->appDomain}/employees")
            ->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::RESTORE_SUCCESS);

        $this->assertDatabaseHas('users', ['id' => $employee->id, 'deleted_at' => null]);

        // 3. Permanent Destroy
        $this->actingAs($this->user, 'business')
            ->from("http://{$this->appDomain}/employees")
            ->delete("http://{$this->appDomain}/employees/{$employee->id}/destroy")
            ->assertRedirect("http://{$this->appDomain}/employees")
            ->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::PURGE_SUCCESS);

        $this->assertDatabaseMissing('users', ['id' => $employee->id]);
    }

    public function test_it_prevents_deleting_root_user_via_controller()
    {
        $response = $this->actingAs($this->user, 'business')
            ->from("http://{$this->appDomain}/employees")
            ->delete("http://{$this->appDomain}/employees/{$this->user->id}");

        $response->assertRedirect("http://{$this->appDomain}/employees")
            ->assertSessionHas(FlashDataVariable::FAILED->value);

        $this->assertDatabaseHas('users', ['id' => $this->user->id, 'deleted_at' => null]);
    }
}
