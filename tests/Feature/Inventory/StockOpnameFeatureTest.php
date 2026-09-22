<?php

namespace Tests\Feature\Inventory;

use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Enums\PlanEnum;
use App\Enums\StockOpnameStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\StockOpname;
use App\Models\Outlet;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StockOpnameFeatureTest extends TestCase
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
                    FeatureEnum::STOCK_OPNAME->value,
                ],
            ]
        );

        $this->business = Business::create([
            'name' => 'Opname Test Business',
            'owner_name' => 'Owner Test',
            'email' => 'owner_opname_'.uniqid().'@test.com',
            'phone' => '08123456789',
            'status' => 'active',
            'trial_end_at' => now()->addDays(30),
            'business_type_id' => $type->id,
            'settings' => [
                'active_features' => [
                    FeatureEnum::INVENTORY_MANAGEMENT->value,
                    FeatureEnum::STOCK_OPNAME->value,
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
            'name' => 'Main Outlet',
            'is_active' => true,
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
            'name' => 'Coffee Beans',
            'sku' => 'CB-001',
            'item_type' => 'raw_material',
            'track_inventory' => true,
            'is_active' => true,
        ]);

        setPermissionsTeamId($this->business->id);

        foreach ([
            PermissionEnum::INVENTORY_OPNAME_READ->value,
            PermissionEnum::INVENTORY_OPNAME_CREATE->value,
            PermissionEnum::INVENTORY_OPNAME_UPDATE->value,
            PermissionEnum::INVENTORY_OPNAME_APPROVE->value,
            PermissionEnum::INVENTORY_OPNAME_DELETE->value,
            PermissionEnum::INVENTORY_OPNAME_EXPORT->value,
        ] as $p) {
            Permission::findOrCreate($p, 'web');
        }

        $this->owner->givePermissionTo([
            'business.*',
            PermissionEnum::INVENTORY_OPNAME_READ->value,
            PermissionEnum::INVENTORY_OPNAME_CREATE->value,
            PermissionEnum::INVENTORY_OPNAME_UPDATE->value,
            PermissionEnum::INVENTORY_OPNAME_APPROVE->value,
            PermissionEnum::INVENTORY_OPNAME_DELETE->value,
            PermissionEnum::INVENTORY_OPNAME_EXPORT->value,
        ]);

        $this->supervisor->givePermissionTo([
            PermissionEnum::INVENTORY_OPNAME_READ->value,
            PermissionEnum::INVENTORY_OPNAME_CREATE->value,
            PermissionEnum::INVENTORY_OPNAME_UPDATE->value,
            PermissionEnum::INVENTORY_OPNAME_APPROVE->value,
            PermissionEnum::INVENTORY_OPNAME_DELETE->value,
        ]);
    }

    public function test_user_can_view_stock_opnames_list()
    {
        $response = $this->actingAs($this->owner)
            ->get("http://{$this->appDomain}/inventories/stock-opnames");

        $response->assertOk();
    }

    public function test_user_can_view_single_stock_opname_detail_via_json()
    {
        $opname = StockOpname::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'opname_number' => 'OP-202609-001',
            'opname_date' => now()->format('Y-m-d'),
            'status' => StockOpnameStatus::InProgress,
            'created_by' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson("http://{$this->appDomain}/inventories/stock-opnames/{$opname->id}");

        $response->assertOk();
        $response->assertJsonPath('opname_number', 'OP-202609-001');
    }

    public function test_user_can_create_in_progress_stock_opname()
    {
        $payload = [
            'outlet_id' => $this->outlet->id,
            'opname_date' => now()->format('Y-m-d'),
            'notes' => 'Stock opname awal bulan',
            'items' => [
                [
                    'inventory_item_id' => $this->inventoryItem->id,
                    'system_qty' => 50,
                    'actual_qty' => 48,
                ],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->post("http://{$this->appDomain}/inventories/stock-opnames", $payload);

        $response->assertSessionHasNoErrors();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('stock_opnames', [
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'status' => StockOpnameStatus::InProgress->value,
            'created_by' => $this->owner->id,
        ]);

        $this->assertDatabaseHas('stock_opname_items', [
            'inventory_item_id' => $this->inventoryItem->id,
            'system_qty' => 50,
            'actual_qty' => 48,
            'difference_qty' => -2,
        ]);
    }

    public function test_user_can_update_opname_and_submit_for_approval()
    {
        $opname = StockOpname::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'opname_number' => 'OP-202609-002',
            'opname_date' => now()->format('Y-m-d'),
            'status' => StockOpnameStatus::InProgress,
            'created_by' => $this->supervisor->id,
        ]);

        $payload = [
            'notes' => 'Penghitungan fisik selesai dilakukan',
            'items' => [
                [
                    'inventory_item_id' => $this->inventoryItem->id,
                    'system_qty' => 30,
                    'actual_qty' => 35,
                ],
            ],
        ];

        $response = $this->actingAs($this->supervisor)
            ->put("http://{$this->appDomain}/inventories/stock-opnames/{$opname->id}", $payload);

        $response->assertSessionHas('success');

        $opname->refresh();
        $this->assertEquals(StockOpnameStatus::PendingApproval, $opname->status);
        $this->assertEquals('Penghitungan fisik selesai dilakukan', $opname->notes);

        $this->assertDatabaseHas('stock_opname_items', [
            'stock_opname_id' => $opname->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'system_qty' => 30,
            'actual_qty' => 35,
            'difference_qty' => 5,
        ]);
    }

    public function test_owner_can_approve_stock_opname_and_adjust_inventory_balance()
    {
        InventoryBalance::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'current_stock' => 50,
            'average_cost' => 10000,
        ]);

        $opname = StockOpname::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'opname_number' => 'OP-202609-003',
            'opname_date' => now()->format('Y-m-d'),
            'status' => StockOpnameStatus::PendingApproval,
            'created_by' => $this->supervisor->id,
        ]);

        $opname->items()->create([
            'inventory_item_id' => $this->inventoryItem->id,
            'system_qty' => 50,
            'actual_qty' => 45, // -5 deficit
            'difference_qty' => -5,
        ]);

        $response = $this->actingAs($this->owner)
            ->post("http://{$this->appDomain}/inventories/stock-opnames/{$opname->id}/approve", [
                'items' => [
                    [
                        'inventory_item_id' => $this->inventoryItem->id,
                        'system_qty' => 50,
                        'actual_qty' => 45,
                    ],
                ],
            ]);

        $response->assertSessionHas('success');

        $opname->refresh();
        $this->assertEquals(StockOpnameStatus::Approved, $opname->status);
        $this->assertEquals($this->owner->id, $opname->approved_by);

        $balance = InventoryBalance::where('inventory_item_id', $this->inventoryItem->id)
            ->where('outlet_id', $this->outlet->id)
            ->first();

        $this->assertEquals(45, (float) $balance->current_stock);
    }

    public function test_supervisor_cannot_approve_their_own_opname_when_sod_is_enabled()
    {
        $this->business->update([
            'settings' => [
                'inventory_sod' => [
                    'enabled' => true,
                    'allow_owner_bypass' => false,
                    'rules' => [
                        'stock_opname' => true,
                    ],
                ],
            ],
        ]);

        $opname = StockOpname::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'opname_number' => 'OP-202609-004',
            'opname_date' => now()->format('Y-m-d'),
            'status' => StockOpnameStatus::PendingApproval,
            'created_by' => $this->supervisor->id,
        ]);

        $opname->items()->create([
            'inventory_item_id' => $this->inventoryItem->id,
            'system_qty' => 20,
            'actual_qty' => 22,
            'difference_qty' => 2,
        ]);

        $response = $this->actingAs($this->supervisor)
            ->post("http://{$this->appDomain}/inventories/stock-opnames/{$opname->id}/approve", [
                'items' => [
                    [
                        'inventory_item_id' => $this->inventoryItem->id,
                        'system_qty' => 20,
                        'actual_qty' => 22,
                    ],
                ],
            ]);

        $response->assertSessionHas('failed');

        $opname->refresh();
        $this->assertEquals(StockOpnameStatus::PendingApproval, $opname->status);
    }

    public function test_owner_can_reject_stock_opname_with_notes()
    {
        $opname = StockOpname::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'opname_number' => 'OP-202609-005',
            'opname_date' => now()->format('Y-m-d'),
            'status' => StockOpnameStatus::PendingApproval,
            'created_by' => $this->supervisor->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->post("http://{$this->appDomain}/inventories/stock-opnames/{$opname->id}/reject", [
                'notes' => 'Jumlah fisik tidak sesuai standar toleransi.',
            ]);

        $response->assertSessionHas('success');

        $opname->refresh();
        $this->assertEquals(StockOpnameStatus::Rejected, $opname->status);
        $this->assertEquals('Jumlah fisik tidak sesuai standar toleransi.', $opname->notes);
        $this->assertEquals($this->owner->id, $opname->approved_by);
    }

    public function test_user_can_delete_in_progress_opname()
    {
        $opname = StockOpname::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'opname_number' => 'OP-202609-006',
            'opname_date' => now()->format('Y-m-d'),
            'status' => StockOpnameStatus::InProgress,
            'created_by' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->delete("http://{$this->appDomain}/inventories/stock-opnames/{$opname->id}");

        $response->assertSessionHas('success');
        $this->assertDatabaseMissing('stock_opnames', ['id' => $opname->id]);
    }

    public function test_user_cannot_delete_approved_opname()
    {
        $opname = StockOpname::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'opname_number' => 'OP-202609-007',
            'opname_date' => now()->format('Y-m-d'),
            'status' => StockOpnameStatus::Approved,
            'created_by' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->delete("http://{$this->appDomain}/inventories/stock-opnames/{$opname->id}");

        $response->assertSessionHas('failed');
        $this->assertDatabaseHas('stock_opnames', ['id' => $opname->id]);
    }

    public function test_user_can_export_stock_opname_pdf()
    {
        $opname = StockOpname::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'opname_number' => 'OP-202609-008',
            'opname_date' => now()->format('Y-m-d'),
            'status' => StockOpnameStatus::Approved,
            'created_by' => $this->owner->id,
        ]);

        $opname->items()->create([
            'inventory_item_id' => $this->inventoryItem->id,
            'system_qty' => 10,
            'actual_qty' => 10,
            'difference_qty' => 0,
        ]);

        $response = $this->actingAs($this->owner)
            ->get("http://{$this->appDomain}/inventories/stock-opnames/{$opname->id}/pdf");

        $response->assertOk();
        $this->assertStringContainsString('application/pdf', $response->headers->get('content-type'));
    }

    public function test_user_without_permission_cannot_access_opnames()
    {
        $staff = User::create([
            'business_id' => $this->business->id,
            'name' => 'Restricted Staff',
            'email' => 'restricted_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
        ]);
        $staff->outlets()->attach($this->outlet->id);

        $response = $this->actingAs($staff)
            ->from("http://{$this->appDomain}/login")
            ->get("http://{$this->appDomain}/inventories/stock-opnames");

        $response->assertRedirect("http://{$this->appDomain}/login");
        $response->assertSessionHas(\App\Constants\FlashDataVariable::FAILED->value);
    }
}
