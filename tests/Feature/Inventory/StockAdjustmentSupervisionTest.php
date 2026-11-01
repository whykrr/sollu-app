<?php

namespace Tests\Feature\Inventory;

use App\Enums\AdjustmentReason;
use App\Enums\AdjustmentStatus;
use App\Enums\FeatureEnum;
use App\Enums\PlanEnum;
use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryItem;
use App\Models\Outlet;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Uom;
use App\Models\User;
use App\Services\App\Inventory\StockAdjustmentService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StockAdjustmentSupervisionTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;

    protected Outlet $outlet;

    protected InventoryItem $item;

    protected Uom $uom;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            [
                'name' => 'Retail',
                'sort_order' => 1,
                'is_visible' => true,
                'features' => [
                    FeatureEnum::INVENTORY_MANAGEMENT->value,
                    FeatureEnum::STOCK_ADJUSTMENTS->value,
                    FeatureEnum::DOUBLE_AUTHORIZATION->value,
                ],
            ]
        );

        $this->business = Business::create([
            'name' => 'Supervision Test Business',
            'owner_name' => 'Owner Person',
            'email' => 'owner_supervision@test.com',
            'phone' => '08123456789',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
            'settings' => [
                'active_features' => [
                    FeatureEnum::INVENTORY_MANAGEMENT->value,
                    FeatureEnum::STOCK_ADJUSTMENTS->value,
                    FeatureEnum::DOUBLE_AUTHORIZATION->value,
                ],
                'inventory_sod' => [
                    'enabled' => true,
                    'allow_owner_bypass' => true,
                    'rules' => [
                        'stock_adjustment' => true,
                    ],
                ],
            ],
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
        $this->business->clearMemoizedFeatures();

        $this->outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Supervision',
            'is_active' => true,
            'is_stock_frozen' => false,
        ]);

        $this->uom = Uom::where('code', 'pcs')->first() ?? Uom::first();

        $this->item = InventoryItem::create([
            'business_id' => $this->business->id,
            'name' => 'Barang Supervisi',
            'uom_id' => $this->uom->id,
            'is_active' => true,
        ]);
    }

    public function test_standard_employee_adjustment_forces_draft_when_supervision_enabled(): void
    {
        $employee = User::factory()->create([
            'business_id' => $this->business->id,
            'email' => 'staff@test.com',
            'name' => 'Staff Biasa',
            'is_root_user' => false,
        ]);

        setPermissionsTeamId($this->business->id);
        $employee->givePermissionTo(Permission::all());

        $service = app(StockAdjustmentService::class);
        $adj = $service->create([
            'outlet_id' => $this->outlet->id,
            'reason' => AdjustmentReason::Correction->value,
            'notes' => 'Adjustment oleh staff',
            'is_draft' => false, // Attempt to directly approve
            'items' => [
                [
                    'inventory_item_id' => $this->item->id,
                    'qty_change' => 5,
                    'unit_cost' => 1000,
                ],
            ],
        ], $employee);

        $adj->refresh();
        $this->assertEquals(AdjustmentStatus::Draft, $adj->status);
        $this->assertNull($adj->approved_by);
        $this->assertNull($adj->approved_at);

        // Stock should not be modified
        $balance = InventoryBalance::where('inventory_item_id', $this->item->id)
            ->where('outlet_id', $this->outlet->id)
            ->first();
        $this->assertNull($balance);
    }

    public function test_root_user_bypasses_supervision(): void
    {
        $rootUser = User::factory()->create([
            'business_id' => $this->business->id,
            'email' => 'root@test.com',
            'name' => 'Root Superuser',
            'is_root_user' => true,
        ]);

        $service = app(StockAdjustmentService::class);
        $adj = $service->create([
            'outlet_id' => $this->outlet->id,
            'reason' => AdjustmentReason::Correction->value,
            'notes' => 'Adjustment oleh root user',
            'is_draft' => false,
            'items' => [
                [
                    'inventory_item_id' => $this->item->id,
                    'qty_change' => 10,
                    'unit_cost' => 1000,
                ],
            ],
        ], $rootUser);

        $adj->refresh();
        $this->assertEquals(AdjustmentStatus::Approved, $adj->status);
        $this->assertEquals($rootUser->id, $adj->approved_by);
        $this->assertNotNull($adj->approved_at);

        // Stock must be updated
        $balance = InventoryBalance::where('inventory_item_id', $this->item->id)
            ->where('outlet_id', $this->outlet->id)
            ->first();
        $this->assertNotNull($balance);
        $this->assertEquals(10, (float) $balance->current_stock);
    }

    public function test_owner_bypasses_supervision_when_bypass_setting_enabled(): void
    {
        $owner = User::factory()->create([
            'business_id' => $this->business->id,
            'email' => $this->business->email,
            'name' => $this->business->owner_name,
            'is_root_user' => false,
        ]);

        setPermissionsTeamId($this->business->id);
        $owner->givePermissionTo(Permission::all());

        $service = app(StockAdjustmentService::class);
        $adj = $service->create([
            'outlet_id' => $this->outlet->id,
            'reason' => AdjustmentReason::Correction->value,
            'notes' => 'Adjustment oleh owner',
            'is_draft' => false,
            'items' => [
                [
                    'inventory_item_id' => $this->item->id,
                    'qty_change' => 15,
                    'unit_cost' => 1000,
                ],
            ],
        ], $owner);

        $adj->refresh();
        $this->assertEquals(AdjustmentStatus::Approved, $adj->status);
        $this->assertEquals($owner->id, $adj->approved_by);
        $this->assertNotNull($adj->approved_at);

        $balance = InventoryBalance::where('inventory_item_id', $this->item->id)
            ->where('outlet_id', $this->outlet->id)
            ->first();
        $this->assertNotNull($balance);
        $this->assertEquals(15, (float) $balance->current_stock);
    }

    public function test_owner_cannot_bypass_supervision_when_bypass_setting_disabled(): void
    {
        $settings = $this->business->settings;
        $settings['inventory_sod']['allow_owner_bypass'] = false;
        $this->business->update(['settings' => $settings]);

        $owner = User::factory()->create([
            'business_id' => $this->business->id,
            'email' => $this->business->email,
            'name' => $this->business->owner_name,
            'is_root_user' => false,
        ]);

        setPermissionsTeamId($this->business->id);
        $owner->givePermissionTo(Permission::all());

        $service = app(StockAdjustmentService::class);
        $adj = $service->create([
            'outlet_id' => $this->outlet->id,
            'reason' => AdjustmentReason::Correction->value,
            'notes' => 'Adjustment oleh owner saat bypass off',
            'is_draft' => false,
            'items' => [
                [
                    'inventory_item_id' => $this->item->id,
                    'qty_change' => 20,
                    'unit_cost' => 1000,
                ],
            ],
        ], $owner);

        $adj->refresh();
        $this->assertEquals(AdjustmentStatus::Draft, $adj->status);
        $this->assertNull($adj->approved_by);
    }
}
