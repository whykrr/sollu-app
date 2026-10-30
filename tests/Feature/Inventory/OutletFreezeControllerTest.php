<?php

namespace Tests\Feature\Inventory;

use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Enums\PlanEnum;
use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Outlet;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class OutletFreezeControllerTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;

    protected Outlet $outlet;

    protected User $validatorOpname;

    protected User $validatorAdjust;

    protected User $staffWithoutPerm;

    protected string $appDomain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $this->appDomain = config('domain.app', 'app.sollu.test');

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            [
                'name' => 'Retail',
                'sort_order' => 1,
                'is_visible' => true,
                'features' => [
                    FeatureEnum::INVENTORY_MANAGEMENT->value,
                    FeatureEnum::STOCK_OPNAME->value,
                    FeatureEnum::STOCK_ADJUSTMENTS->value,
                ],
            ]
        );

        $this->business = Business::create([
            'name' => 'Freeze Test Business',
            'owner_name' => 'Owner Freeze',
            'email' => 'freeze_'.uniqid().'@test.com',
            'phone' => '08123456789',
            'status' => 'active',
            'business_type_id' => $type->id,
        ]);

        $proPlan = SubscriptionPlan::where('code', PlanEnum::PRO->value)->first();
        Subscription::create([
            'business_id' => $this->business->id,
            'plan_id' => $proPlan->id,
            'status' => SubscriptionStatus::Active,
            'billing_cycle' => 'monthly',
            'started_at' => now()->subDay(),
            'expired_at' => now()->addMonth(),
        ]);

        $this->outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Freeze Test',
            'is_active' => true,
            'is_stock_frozen' => false,
        ]);

        $this->validatorOpname = User::create([
            'business_id' => $this->business->id,
            'name' => 'Opname Validator',
            'email' => 'opname_val_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->validatorAdjust = User::create([
            'business_id' => $this->business->id,
            'name' => 'Adjust Validator',
            'email' => 'adj_val_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->staffWithoutPerm = User::create([
            'business_id' => $this->business->id,
            'name' => 'Regular Staff',
            'email' => 'staff_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->validatorOpname->outlets()->attach($this->outlet->id);
        $this->validatorAdjust->outlets()->attach($this->outlet->id);
        $this->staffWithoutPerm->outlets()->attach($this->outlet->id);

        setPermissionsTeamId($this->business->id);

        Permission::findOrCreate(PermissionEnum::INVENTORY_OPNAME_VALIDATE->value, 'web');
        Permission::findOrCreate(PermissionEnum::INVENTORY_ADJUST_VALIDATE->value, 'web');

        $this->validatorOpname->givePermissionTo(PermissionEnum::INVENTORY_OPNAME_VALIDATE->value);
        $this->validatorAdjust->givePermissionTo(PermissionEnum::INVENTORY_ADJUST_VALIDATE->value);
    }

    public function test_user_with_opname_validate_permission_can_freeze_and_unfreeze_outlet(): void
    {
        $response = $this->actingAs($this->validatorOpname)
            ->post("http://{$this->appDomain}/inventories/outlets/freeze", [
                'outlet_id' => $this->outlet->id,
            ]);

        $response->assertRedirect();
        $this->assertTrue($this->outlet->fresh()->is_stock_frozen);

        $responseUnfreeze = $this->actingAs($this->validatorOpname)
            ->post("http://{$this->appDomain}/inventories/outlets/unfreeze", [
                'outlet_id' => $this->outlet->id,
            ]);

        $responseUnfreeze->assertRedirect();
        $this->assertFalse($this->outlet->fresh()->is_stock_frozen);
    }

    public function test_user_with_adjustment_validate_permission_can_freeze_and_unfreeze_outlet(): void
    {
        $response = $this->actingAs($this->validatorAdjust)
            ->post("http://{$this->appDomain}/inventories/outlets/freeze", [
                'outlet_id' => $this->outlet->id,
            ]);

        $response->assertRedirect();
        $this->assertTrue($this->outlet->fresh()->is_stock_frozen);

        $responseUnfreeze = $this->actingAs($this->validatorAdjust)
            ->post("http://{$this->appDomain}/inventories/outlets/unfreeze", [
                'outlet_id' => $this->outlet->id,
            ]);

        $responseUnfreeze->assertRedirect();
        $this->assertFalse($this->outlet->fresh()->is_stock_frozen);
    }

    public function test_user_without_validate_permission_cannot_freeze_outlet(): void
    {
        $response = $this->actingAs($this->staffWithoutPerm)
            ->post("http://{$this->appDomain}/inventories/outlets/freeze", [
                'outlet_id' => $this->outlet->id,
            ]);

        $response->assertForbidden();
        $this->assertFalse($this->outlet->fresh()->is_stock_frozen);
    }

    public function test_user_cannot_freeze_outlet_belonging_to_another_business(): void
    {
        $otherBusiness = Business::create([
            'name' => 'Other Business',
            'owner_name' => 'Other Owner',
            'email' => 'other_'.uniqid().'@test.com',
            'phone' => '08123456789',
            'status' => 'active',
            'business_type_id' => $this->business->business_type_id,
        ]);

        $otherOutlet = Outlet::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Foreign Outlet',
            'is_active' => true,
            'is_stock_frozen' => false,
        ]);

        $response = $this->actingAs($this->validatorOpname)
            ->post("http://{$this->appDomain}/inventories/outlets/freeze", [
                'outlet_id' => $otherOutlet->id,
            ]);

        $response->assertNotFound();
        $this->assertFalse($otherOutlet->fresh()->is_stock_frozen);
    }
}
