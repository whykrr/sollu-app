<?php

namespace Tests\Feature\Inventory;

use App\Enums\AdjustmentReason;
use App\Enums\AdjustmentStatus;
use App\Enums\FeatureEnum;
use App\Enums\InventoryMovementType;
use App\Enums\PermissionEnum;
use App\Enums\PlanEnum;
use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\StockAdjustment;
use App\Models\Outlet;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StockAdjustmentFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $supervisor;

    protected Business $business;

    protected Outlet $outlet;

    protected InventoryItem $inventoryItem;

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
            'name' => 'Adjustment Test Business',
            'owner_name' => 'Owner Test',
            'email' => 'owner_adj_'.uniqid().'@test.com',
            'phone' => '08123456789',
            'status' => 'active',
            'trial_end_at' => now()->addDays(30),
            'business_type_id' => $type->id,
            'settings' => [
                'active_features' => [
                    FeatureEnum::INVENTORY_MANAGEMENT->value,
                    FeatureEnum::STOCK_ADJUSTMENTS->value,
                    FeatureEnum::DOUBLE_AUTHORIZATION->value,
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
            'name' => 'Warehouse Outlet',
            'is_active' => true,
            'is_stock_frozen' => false,
        ]);

        $this->owner = User::create([
            'business_id' => $this->business->id,
            'name' => 'Business Owner',
            'email' => 'owner_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->supervisor = User::create([
            'business_id' => $this->business->id,
            'name' => 'Supervisor Staff',
            'email' => 'supervisor_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->owner->outlets()->attach($this->outlet->id);
        $this->supervisor->outlets()->attach($this->outlet->id);

        $this->inventoryItem = InventoryItem::firstOrCreate([
            'business_id' => $this->business->id,
        ], [
            'name' => 'Raw Sugar',
            'sku' => 'SUGAR-001',
            'item_type' => 'raw_material',
            'track_inventory' => true,
            'is_active' => true,
        ]);

        setPermissionsTeamId($this->business->id);

        foreach ([
            PermissionEnum::INVENTORY_ADJUST_VIEW->value,
            PermissionEnum::INVENTORY_ADJUST_CREATE->value,
            PermissionEnum::INVENTORY_ADJUST_UPDATE->value,
            PermissionEnum::INVENTORY_ADJUST_VALIDATE->value,
            PermissionEnum::INVENTORY_ADJUST_DELETE->value,
        ] as $p) {
            Permission::findOrCreate($p, 'web');
        }

        $this->owner->givePermissionTo([
            PermissionEnum::INVENTORY_ADJUST_VIEW->value,
            PermissionEnum::INVENTORY_ADJUST_CREATE->value,
            PermissionEnum::INVENTORY_ADJUST_UPDATE->value,
            PermissionEnum::INVENTORY_ADJUST_VALIDATE->value,
            PermissionEnum::INVENTORY_ADJUST_DELETE->value,
        ]);

        $this->supervisor->givePermissionTo([
            PermissionEnum::INVENTORY_ADJUST_VIEW->value,
            PermissionEnum::INVENTORY_ADJUST_CREATE->value,
            PermissionEnum::INVENTORY_ADJUST_VALIDATE->value,
        ]);
    }

    public function test_user_can_view_stock_adjustments_list()
    {
        $response = $this->actingAs($this->owner)
            ->get("http://{$this->appDomain}/inventories/adjustments");

        $response->assertOk();
    }

    public function test_user_can_create_draft_stock_adjustment()
    {
        $payload = [
            'outlet_id' => $this->outlet->id,
            'reason' => AdjustmentReason::Correction->value,
            'notes' => 'Pemeriksaan rutin mingguan',
            'is_draft' => true,
            'items' => [
                [
                    'inventory_item_id' => $this->inventoryItem->id,
                    'qty_change' => 20,
                    'unit_cost' => 15000,
                    'description' => 'Penambahan stok fisik',
                ],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->post("http://{$this->appDomain}/inventories/adjustments", $payload);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('stock_adjustments', [
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'status' => AdjustmentStatus::Draft->value,
            'reason' => AdjustmentReason::Correction->value,
            'created_by' => $this->owner->id,
        ]);

        $this->assertDatabaseHas('stock_adjustment_items', [
            'inventory_item_id' => $this->inventoryItem->id,
            'qty_change' => 20,
            'unit_cost' => 15000,
        ]);
    }

    public function test_user_can_create_and_apply_stock_adjustment_in_flexible_mode()
    {
        $payload = [
            'outlet_id' => $this->outlet->id,
            'reason' => AdjustmentReason::Correction->value,
            'notes' => 'Simpan dan terapkan langsung',
            'is_draft' => false,
            'items' => [
                [
                    'inventory_item_id' => $this->inventoryItem->id,
                    'qty_change' => 10,
                    'unit_cost' => 15000,
                    'description' => 'Terapkan langsung',
                ],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->post("http://{$this->appDomain}/inventories/adjustments", $payload);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success', 'Penyesuaian stok berhasil disimpan dan diterapkan.');

        $this->assertDatabaseHas('stock_adjustments', [
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'status' => AdjustmentStatus::Approved->value,
            'created_by' => $this->owner->id,
            'approved_by' => $this->owner->id,
        ]);

        $balance = InventoryBalance::where('inventory_item_id', $this->inventoryItem->id)
            ->where('outlet_id', $this->outlet->id)
            ->first();

        $this->assertEquals(10, (float) $balance->current_stock);
    }

    public function test_owner_can_approve_draft_adjustment()
    {
        $adjustment = StockAdjustment::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'adjustment_number' => 'ADJ-20260922-001',
            'status' => AdjustmentStatus::Draft,
            'reason' => AdjustmentReason::Correction,
            'created_by' => $this->supervisor->id,
        ]);

        $item = $adjustment->items()->create([
            'inventory_item_id' => $this->inventoryItem->id,
            'movement_type' => InventoryMovementType::Adjustment,
            'qty_change' => 15,
            'unit_cost' => 12000,
            'description' => 'Tambah stok koreksi',
        ]);

        $response = $this->actingAs($this->owner)
            ->post("http://{$this->appDomain}/inventories/adjustments/{$adjustment->id}/approve");

        $response->assertSessionHas('success');

        $adjustment->refresh();
        $this->assertEquals(AdjustmentStatus::Approved, $adjustment->status);
        $this->assertEquals($this->owner->id, $adjustment->approved_by);

        $balance = InventoryBalance::where('inventory_item_id', $this->inventoryItem->id)
            ->where('outlet_id', $this->outlet->id)
            ->first();

        $this->assertEquals(15, (float) $balance->current_stock);

        $item->refresh();
        $this->assertEquals(0, (float) $item->stock_before);
        $this->assertEquals(15, (float) $item->stock_after);
    }

    public function test_staff_with_update_permission_can_approve_draft_adjustment_in_flexible_mode(): void
    {
        $staff = User::create([
            'business_id' => $this->business->id,
            'name' => 'Operator Staff',
            'email' => 'operator_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
        ]);
        $staff->outlets()->attach($this->outlet->id);
        $staff->givePermissionTo(PermissionEnum::INVENTORY_ADJUST_UPDATE->value);

        $adjustment = StockAdjustment::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'adjustment_number' => 'ADJ-20260922-099',
            'status' => AdjustmentStatus::Draft,
            'reason' => AdjustmentReason::Correction,
            'created_by' => $this->supervisor->id,
        ]);

        $adjustment->items()->create([
            'inventory_item_id' => $this->inventoryItem->id,
            'movement_type' => InventoryMovementType::Adjustment,
            'qty_change' => 5,
            'unit_cost' => 10000,
            'description' => 'Koreksi draf fleksibel',
        ]);

        $response = $this->actingAs($staff)
            ->post("http://{$this->appDomain}/inventories/adjustments/{$adjustment->id}/approve");

        $response->assertSessionHas('success');
        $this->assertEquals(AdjustmentStatus::Approved, $adjustment->fresh()->status);
    }

    public function test_staff_without_validate_permission_cannot_approve_draft_adjustment_when_supervision_is_enabled(): void
    {
        $this->business->settings = [
            'inventory_sod' => [
                'enabled' => true,
                'allow_owner_bypass' => false,
            ],
        ];
        $this->business->save();

        $staff = User::create([
            'business_id' => $this->business->id,
            'name' => 'Staff Without Validate',
            'email' => 'staff_noval_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
        ]);
        $staff->outlets()->attach($this->outlet->id);
        $staff->givePermissionTo(PermissionEnum::INVENTORY_ADJUST_UPDATE->value);

        $adjustment = StockAdjustment::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'adjustment_number' => 'ADJ-20260922-100',
            'status' => AdjustmentStatus::Draft,
            'reason' => AdjustmentReason::Correction,
            'created_by' => $this->supervisor->id,
        ]);

        $adjustment->items()->create([
            'inventory_item_id' => $this->inventoryItem->id,
            'movement_type' => InventoryMovementType::Adjustment,
            'qty_change' => 5,
            'description' => 'Koreksi draf',
        ]);

        $response = $this->actingAs($staff)
            ->post("http://{$this->appDomain}/inventories/adjustments/{$adjustment->id}/approve");

        $response->assertForbidden();
        $this->assertEquals(AdjustmentStatus::Draft, $adjustment->fresh()->status);
    }

    public function test_supervisor_cannot_approve_their_own_adjustment()
    {
        $settings = $this->business->settings ?? [];
        $settings['inventory_sod'] = [
            'enabled' => true,
            'allow_owner_bypass' => false,
            'rules' => [
                'stock_adjustment' => true,
            ],
        ];
        $this->business->update(['settings' => $settings]);

        $adjustment = StockAdjustment::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'adjustment_number' => 'ADJ-20260922-002',
            'status' => AdjustmentStatus::Draft,
            'reason' => AdjustmentReason::Waste,
            'created_by' => $this->supervisor->id,
        ]);

        $adjustment->items()->create([
            'inventory_item_id' => $this->inventoryItem->id,
            'movement_type' => InventoryMovementType::Waste,
            'qty_change' => 5,
            'description' => 'Barang rusak',
        ]);

        $response = $this->actingAs($this->supervisor)
            ->post("http://{$this->appDomain}/inventories/adjustments/{$adjustment->id}/approve");

        $response->assertSessionHas('failed');

        $adjustment->refresh();
        $this->assertEquals(AdjustmentStatus::Draft, $adjustment->status);
    }

    public function test_owner_can_void_approved_adjustment_and_reverse_stock()
    {
        $adjustment = StockAdjustment::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'adjustment_number' => 'ADJ-20260922-003',
            'status' => AdjustmentStatus::Draft,
            'reason' => AdjustmentReason::Correction,
            'created_by' => $this->supervisor->id,
        ]);

        $adjustment->items()->create([
            'inventory_item_id' => $this->inventoryItem->id,
            'movement_type' => InventoryMovementType::Adjustment,
            'qty_change' => 10,
            'unit_cost' => 10000,
            'description' => 'Penyesuaian masuk',
        ]);

        // Approve first
        $this->actingAs($this->owner)
            ->post("http://{$this->appDomain}/inventories/adjustments/{$adjustment->id}/approve");

        $balance = InventoryBalance::where('inventory_item_id', $this->inventoryItem->id)->first();
        $this->assertEquals(10, (float) $balance->current_stock);

        // Void
        $response = $this->actingAs($this->owner)
            ->post("http://{$this->appDomain}/inventories/adjustments/{$adjustment->id}/void");

        $response->assertSessionHas('success');

        $adjustment->refresh();
        $this->assertEquals(AdjustmentStatus::Voided, $adjustment->status);

        $balance->refresh();
        $this->assertEquals(0, (float) $balance->current_stock);
    }

    public function test_cannot_mutate_when_stock_is_frozen()
    {
        $this->outlet->update(['is_stock_frozen' => true]);

        $payload = [
            'outlet_id' => $this->outlet->id,
            'reason' => AdjustmentReason::Correction->value,
            'notes' => 'Uji freeze',
            'items' => [
                [
                    'inventory_item_id' => $this->inventoryItem->id,
                    'qty_change' => 5,
                    'description' => 'Uji freeze',
                ],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->post("http://{$this->appDomain}/inventories/adjustments", $payload);

        $response->assertSessionHas('failed');
    }
}
