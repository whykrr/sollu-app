<?php

namespace Tests\Feature\Inventory;

use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Enums\PlanEnum;
use App\Enums\StockTransferMode;
use App\Enums\StockTransferStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\StockTransfer;
use App\Models\Outlet;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StockTransferFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;

    protected User $supervisor;

    protected Business $business;

    protected Outlet $outletA;

    protected Outlet $outletB;

    protected InventoryItem $inventoryItem;

    protected function setUp(): void
    {
        parent::setUp();

        config(['inertia.testing.page_paths' => [
            resource_path('js/Pages'),
            resource_path('js/Pages/App'),
        ]]);

        $this->seed(DatabaseSeeder::class);

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            [
                'name' => 'Retail',
                'sort_order' => 1,
                'is_visible' => true,
                'features' => [
                    FeatureEnum::INVENTORY_MANAGEMENT->value,
                    FeatureEnum::STOCK_TRANSFERS->value,
                    FeatureEnum::DOUBLE_AUTHORIZATION->value,
                ],
            ]
        );

        $this->business = Business::create([
            'name' => 'Transfer Test Business',
            'owner_name' => 'Owner Test',
            'email' => 'owner_tf_'.uniqid().'@test.com',
            'phone' => '08123456789',
            'status' => 'active',
            'trial_end_at' => now()->addDays(30),
            'business_type_id' => $type->id,
            'settings' => [
                'active_features' => [
                    FeatureEnum::INVENTORY_MANAGEMENT->value,
                    FeatureEnum::STOCK_TRANSFERS->value,
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

        $this->outletA = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Asal (Cabang A)',
            'is_active' => true,
            'is_stock_frozen' => false,
        ]);

        $this->outletB = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Tujuan (Cabang B)',
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

        $this->owner->outlets()->attach([$this->outletA->id, $this->outletB->id]);
        $this->supervisor->outlets()->attach([$this->outletA->id, $this->outletB->id]);

        setPermissionsTeamId($this->business->id);
        $permissions = [
            PermissionEnum::INVENTORY_TRANSFER_VIEW->value,
            PermissionEnum::INVENTORY_TRANSFER_CREATE->value,
            PermissionEnum::INVENTORY_TRANSFER_UPDATE->value,
            PermissionEnum::INVENTORY_TRANSFER_VALIDATE->value,
            PermissionEnum::INVENTORY_TRANSFER_DELETE->value,
            PermissionEnum::INVENTORY_TRANSFER_RECEIVE->value,
        ];

        foreach ($permissions as $p) {
            Permission::findOrCreate($p, 'web');
        }

        $this->owner->givePermissionTo($permissions);
        $this->supervisor->givePermissionTo([
            PermissionEnum::INVENTORY_TRANSFER_VIEW->value,
            PermissionEnum::INVENTORY_TRANSFER_CREATE->value,
            PermissionEnum::INVENTORY_TRANSFER_UPDATE->value,
            PermissionEnum::INVENTORY_TRANSFER_VALIDATE->value,
            PermissionEnum::INVENTORY_TRANSFER_RECEIVE->value,
        ]);

        $this->inventoryItem = InventoryItem::create([
            'business_id' => $this->business->id,
            'name' => 'Biji Kopi Arabika',
            'sku' => 'KOP-001',
            'item_type' => 'raw_material',
            'cost_price' => 15000,
        ]);

        InventoryBalance::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outletA->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'current_stock' => 50,
        ]);
    }

    public function test_user_can_view_transfers_index_with_filters(): void
    {
        StockTransfer::create([
            'business_id' => $this->business->id,
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'transfer_number' => 'TF-202609-001',
            'transfer_date' => now()->format('Y-m-d'),
            'status' => StockTransferStatus::Pending,
            'requested_by' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('inventory.transfers.index', ['status' => 'pending']));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Inventory/Transfer/Index')
            ->has('transfers.data', 1)
            ->where('transfers.data.0.transfer_number', 'TF-202609-001')
        );
    }

    public function test_user_can_create_direct_stock_transfer(): void
    {
        $payload = [
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'transfer_mode' => StockTransferMode::DIRECT->value,
            'transfer_date' => now()->format('Y-m-d'),
            'notes' => 'Kirim stok kopi ke Cabang B langsung',
            'items' => [
                [
                    'inventory_item_id' => $this->inventoryItem->id,
                    'qty' => 10,
                ],
            ],
        ];

        $response = $this->actingAs($this->supervisor)
            ->post(route('inventory.transfers.store'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('stock_transfers', [
            'business_id' => $this->business->id,
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'status' => StockTransferStatus::Completed->value,
            'transfer_mode' => StockTransferMode::DIRECT->value,
            'notes' => 'Kirim stok kopi ke Cabang B langsung',
            'requested_by' => $this->supervisor->id,
        ]);

        $this->assertDatabaseHas('stock_transfer_items', [
            'inventory_item_id' => $this->inventoryItem->id,
            'qty' => 10,
            'qty_received' => 10,
        ]);

        $this->assertEquals(40, InventoryBalance::where('outlet_id', $this->outletA->id)->where('inventory_item_id', $this->inventoryItem->id)->value('current_stock'));
        $this->assertEquals(10, InventoryBalance::where('outlet_id', $this->outletB->id)->where('inventory_item_id', $this->inventoryItem->id)->value('current_stock'));
    }

    public function test_user_can_create_two_step_stock_transfer_in_transit(): void
    {
        $payload = [
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'transfer_mode' => StockTransferMode::TWO_STEP->value,
            'transfer_date' => now()->format('Y-m-d'),
            'notes' => 'Kirim stok kopi ke Cabang B 2-langkah',
            'items' => [
                [
                    'inventory_item_id' => $this->inventoryItem->id,
                    'qty' => 15,
                ],
            ],
        ];

        $response = $this->actingAs($this->supervisor)
            ->post(route('inventory.transfers.store'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('stock_transfers', [
            'business_id' => $this->business->id,
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'status' => StockTransferStatus::InTransit->value,
            'transfer_mode' => StockTransferMode::TWO_STEP->value,
            'notes' => 'Kirim stok kopi ke Cabang B 2-langkah',
            'requested_by' => $this->supervisor->id,
        ]);

        // Origin balance immediately deducted: 50 - 15 = 35
        $this->assertEquals(35, InventoryBalance::where('outlet_id', $this->outletA->id)->where('inventory_item_id', $this->inventoryItem->id)->value('current_stock'));
        // Destination balance still 0
        $this->assertEquals(0, (float) (InventoryBalance::where('outlet_id', $this->outletB->id)->where('inventory_item_id', $this->inventoryItem->id)->value('current_stock') ?? 0));
    }

    public function test_user_can_create_stock_transfer_as_pending_when_supervision_is_enabled(): void
    {
        $this->business->settings = [
            'active_features' => [
                FeatureEnum::INVENTORY_MANAGEMENT->value,
                FeatureEnum::STOCK_TRANSFERS->value,
                FeatureEnum::DOUBLE_AUTHORIZATION->value,
            ],
            'inventory_sod' => [
                'enabled' => true,
                'allow_owner_bypass' => false,
                'rules' => ['stock_transfer_approval' => true],
            ],
        ];
        $this->business->save();
        $this->business->clearMemoizedFeatures();

        $payload = [
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'transfer_date' => now()->format('Y-m-d'),
            'notes' => 'Kirim stok kopi ke Cabang B butuh approval',
            'items' => [
                [
                    'inventory_item_id' => $this->inventoryItem->id,
                    'qty' => 10,
                ],
            ],
        ];

        $response = $this->actingAs($this->supervisor)
            ->post(route('inventory.transfers.store'), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('stock_transfers', [
            'business_id' => $this->business->id,
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'status' => StockTransferStatus::Pending->value,
            'notes' => 'Kirim stok kopi ke Cabang B butuh approval',
            'requested_by' => $this->supervisor->id,
        ]);
    }

    public function test_user_can_update_pending_stock_transfer(): void
    {
        $transfer = StockTransfer::create([
            'business_id' => $this->business->id,
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'transfer_number' => 'TF-202609-002',
            'transfer_date' => now()->format('Y-m-d'),
            'status' => StockTransferStatus::Pending,
            'requested_by' => $this->owner->id,
            'notes' => 'Initial notes',
        ]);

        $payload = [
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'transfer_date' => now()->format('Y-m-d'),
            'notes' => 'Updated transfer notes',
            'items' => [
                [
                    'inventory_item_id' => $this->inventoryItem->id,
                    'qty' => 15,
                ],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->put(route('inventory.transfers.update', $transfer->id), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('stock_transfers', [
            'id' => $transfer->id,
            'notes' => 'Updated transfer notes',
        ]);
        $this->assertDatabaseHas('stock_transfer_items', [
            'stock_transfer_id' => $transfer->id,
            'qty' => 15,
        ]);
    }

    public function test_cannot_update_non_pending_stock_transfer(): void
    {
        $transfer = StockTransfer::create([
            'business_id' => $this->business->id,
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'transfer_number' => 'TF-202609-003',
            'transfer_date' => now()->format('Y-m-d'),
            'status' => StockTransferStatus::Approved,
            'requested_by' => $this->owner->id,
        ]);

        $payload = [
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'transfer_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'inventory_item_id' => $this->inventoryItem->id,
                    'qty' => 20,
                ],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->put(route('inventory.transfers.update', $transfer->id), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('failed');
    }

    public function test_user_can_approve_stock_transfer(): void
    {
        $transfer = StockTransfer::create([
            'business_id' => $this->business->id,
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'transfer_number' => 'TF-202609-004',
            'transfer_mode' => StockTransferMode::TWO_STEP->value,
            'transfer_date' => now()->format('Y-m-d'),
            'status' => StockTransferStatus::Pending,
            'requested_by' => $this->supervisor->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->post(route('inventory.transfers.approve', $transfer->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('stock_transfers', [
            'id' => $transfer->id,
            'status' => StockTransferStatus::Approved->value,
            'approved_by' => $this->owner->id,
        ]);
    }

    public function test_sod_blocks_creator_from_approving_own_transfer(): void
    {
        $this->business->settings = [
            'inventory_sod' => [
                'enabled' => true,
                'allow_owner_bypass' => false,
                'rules' => ['stock_transfer_approval' => true],
            ],
        ];
        $this->business->save();

        $transfer = StockTransfer::create([
            'business_id' => $this->business->id,
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'transfer_number' => 'TF-202609-005',
            'transfer_date' => now()->format('Y-m-d'),
            'status' => StockTransferStatus::Pending,
            'requested_by' => $this->supervisor->id,
        ]);

        $response = $this->actingAs($this->supervisor)
            ->post(route('inventory.transfers.approve', $transfer->id));

        $response->assertRedirect();
        $response->assertSessionHas('failed');

        $this->assertEquals(StockTransferStatus::Pending, $transfer->fresh()->status);
    }

    public function test_user_can_reject_stock_transfer(): void
    {
        $transfer = StockTransfer::create([
            'business_id' => $this->business->id,
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'transfer_number' => 'TF-202609-006',
            'transfer_date' => now()->format('Y-m-d'),
            'status' => StockTransferStatus::Pending,
            'requested_by' => $this->supervisor->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->post(route('inventory.transfers.reject', $transfer->id), [
                'notes' => 'Stok sedang tidak mencukupi untuk dikirim',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('stock_transfers', [
            'id' => $transfer->id,
            'status' => StockTransferStatus::Rejected->value,
            'notes' => 'Stok sedang tidak mencukupi untuk dikirim',
        ]);
    }

    public function test_user_can_ship_approved_stock_transfer(): void
    {
        $transfer = StockTransfer::create([
            'business_id' => $this->business->id,
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'transfer_number' => 'TF-202609-007',
            'transfer_mode' => StockTransferMode::TWO_STEP->value,
            'transfer_date' => now()->format('Y-m-d'),
            'status' => StockTransferStatus::Approved,
            'requested_by' => $this->supervisor->id,
            'approved_by' => $this->owner->id,
            'approved_at' => now(),
        ]);

        $transfer->items()->create([
            'inventory_item_id' => $this->inventoryItem->id,
            'qty' => 10,
        ]);

        $response = $this->actingAs($this->owner)
            ->post(route('inventory.transfers.ship', $transfer->id));

        $response->assertRedirect();
        $this->assertDatabaseHas('stock_transfers', [
            'id' => $transfer->id,
            'status' => StockTransferStatus::InTransit->value,
        ]);

        // Origin stock is deducted upon shipping
        $sourceBalance = InventoryBalance::where('outlet_id', $this->outletA->id)
            ->where('inventory_item_id', $this->inventoryItem->id)
            ->first();
        $this->assertEquals(40, $sourceBalance->current_stock);
    }

    public function test_user_can_receive_in_transit_stock_transfer_and_update_balances(): void
    {
        $transfer = StockTransfer::create([
            'business_id' => $this->business->id,
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'transfer_number' => 'TF-202609-008',
            'transfer_mode' => StockTransferMode::TWO_STEP->value,
            'transfer_date' => now()->format('Y-m-d'),
            'status' => StockTransferStatus::Approved,
            'requested_by' => $this->supervisor->id,
            'approved_by' => $this->owner->id,
            'approved_at' => now(),
        ]);

        $item = $transfer->items()->create([
            'inventory_item_id' => $this->inventoryItem->id,
            'qty' => 10,
        ]);

        // Ship the transfer (cuts origin stock: 50 -> 40)
        $this->actingAs($this->owner)->post(route('inventory.transfers.ship', $transfer->id));

        $payload = [
            'items' => [
                [
                    'id' => $item->id,
                    'qty_received' => 10,
                ],
            ],
        ];

        $response = $this->actingAs($this->supervisor)
            ->post(route('inventory.transfers.receive', $transfer->id), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('stock_transfers', [
            'id' => $transfer->id,
            'status' => StockTransferStatus::Completed->value,
            'received_by' => $this->supervisor->id,
        ]);

        // Source balance: 50 - 10 = 40
        $sourceBalance = InventoryBalance::where('outlet_id', $this->outletA->id)
            ->where('inventory_item_id', $this->inventoryItem->id)
            ->first();
        $this->assertEquals(40, $sourceBalance->current_stock);

        // Destination balance: 0 + 10 = 10
        $destBalance = InventoryBalance::where('outlet_id', $this->outletB->id)
            ->where('inventory_item_id', $this->inventoryItem->id)
            ->first();
        $this->assertEquals(10, $destBalance->current_stock);
    }

    public function test_user_can_receive_partial_stock_with_discrepancy(): void
    {
        $transfer = StockTransfer::create([
            'business_id' => $this->business->id,
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'transfer_number' => 'TF-202609-009',
            'transfer_mode' => StockTransferMode::TWO_STEP->value,
            'transfer_date' => now()->format('Y-m-d'),
            'status' => StockTransferStatus::Approved,
            'requested_by' => $this->supervisor->id,
            'approved_by' => $this->owner->id,
            'approved_at' => now(),
        ]);

        $item = $transfer->items()->create([
            'inventory_item_id' => $this->inventoryItem->id,
            'qty' => 10,
        ]);

        // Ship the transfer (cuts origin stock: 50 -> 40)
        $this->actingAs($this->owner)->post(route('inventory.transfers.ship', $transfer->id));

        $payload = [
            'items' => [
                [
                    'id' => $item->id,
                    'qty_received' => 8, // 2 items lost/damaged
                    'difference_reason' => 'Pecah di jalan',
                ],
            ],
        ];

        $response = $this->actingAs($this->supervisor)
            ->post(route('inventory.transfers.receive', $transfer->id), $payload);

        $response->assertRedirect();
        $this->assertDatabaseHas('stock_transfers', [
            'id' => $transfer->id,
            'status' => StockTransferStatus::Completed->value,
        ]);

        $this->assertDatabaseHas('stock_transfer_items', [
            'id' => $item->id,
            'qty' => 10,
            'qty_received' => 8,
            'qty_difference' => 2,
            'difference_reason' => 'Pecah di jalan',
        ]);

        // Source outlet was cut 10 upon ship = 40
        $sourceBalance = InventoryBalance::where('outlet_id', $this->outletA->id)
            ->where('inventory_item_id', $this->inventoryItem->id)
            ->first();
        $this->assertEquals(40, $sourceBalance->current_stock);

        // Destination outlet receives 8
        $destBalance = InventoryBalance::where('outlet_id', $this->outletB->id)
            ->where('inventory_item_id', $this->inventoryItem->id)
            ->first();
        $this->assertEquals(8, $destBalance->current_stock);
    }

    public function test_user_can_void_in_transit_transfer(): void
    {
        $transfer = StockTransfer::create([
            'business_id' => $this->business->id,
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'transfer_number' => 'TF-202609-VOID1',
            'transfer_mode' => StockTransferMode::TWO_STEP->value,
            'transfer_date' => now()->format('Y-m-d'),
            'status' => StockTransferStatus::Approved,
            'requested_by' => $this->supervisor->id,
            'approved_by' => $this->owner->id,
            'approved_at' => now(),
        ]);

        $transfer->items()->create([
            'inventory_item_id' => $this->inventoryItem->id,
            'qty' => 10,
        ]);

        // Ship
        $this->actingAs($this->owner)->post(route('inventory.transfers.ship', $transfer->id));
        $this->assertEquals(40, InventoryBalance::where('outlet_id', $this->outletA->id)->where('inventory_item_id', $this->inventoryItem->id)->value('current_stock'));

        // Void in-transit
        $response = $this->actingAs($this->owner)
            ->post(route('inventory.transfers.void', $transfer->id), [
                'void_reason' => 'Kendaraan rusak di jalan, transfer dibatalkan',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('stock_transfers', [
            'id' => $transfer->id,
            'status' => StockTransferStatus::Void->value,
            'void_reason' => 'Kendaraan rusak di jalan, transfer dibatalkan',
        ]);

        // Origin stock refunded back to 50
        $this->assertEquals(50, InventoryBalance::where('outlet_id', $this->outletA->id)->where('inventory_item_id', $this->inventoryItem->id)->value('current_stock'));
    }

    public function test_user_can_void_completed_transfer(): void
    {
        // Start with direct transfer: origin 50 -> 40, dest 0 -> 10
        $payload = [
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'transfer_mode' => StockTransferMode::DIRECT->value,
            'items' => [
                ['inventory_item_id' => $this->inventoryItem->id, 'qty' => 10],
            ],
        ];
        $this->actingAs($this->supervisor)->post(route('inventory.transfers.store'), $payload);
        $transfer = StockTransfer::where('business_id', $this->business->id)->latest('created_at')->first();

        $this->assertEquals(40, InventoryBalance::where('outlet_id', $this->outletA->id)->where('inventory_item_id', $this->inventoryItem->id)->value('current_stock'));
        $this->assertEquals(10, InventoryBalance::where('outlet_id', $this->outletB->id)->where('inventory_item_id', $this->inventoryItem->id)->value('current_stock'));

        // Void completed
        $response = $this->actingAs($this->owner)
            ->post(route('inventory.transfers.void', $transfer->id), [
                'void_reason' => 'Salah tujuan transfer barang',
            ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('stock_transfers', [
            'id' => $transfer->id,
            'status' => StockTransferStatus::Void->value,
            'void_reason' => 'Salah tujuan transfer barang',
        ]);

        // Symmetric reversal: dest deducted back to 0, origin refunded to 50
        $this->assertEquals(50, InventoryBalance::where('outlet_id', $this->outletA->id)->where('inventory_item_id', $this->inventoryItem->id)->value('current_stock'));
        $this->assertEquals(0, InventoryBalance::where('outlet_id', $this->outletB->id)->where('inventory_item_id', $this->inventoryItem->id)->value('current_stock'));
    }

    public function test_transfer_creation_blocked_when_outlet_stock_is_frozen(): void
    {
        $this->outletA->update(['is_stock_frozen' => true]);

        $payload = [
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'transfer_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'inventory_item_id' => $this->inventoryItem->id,
                    'qty' => 5,
                ],
            ],
        ];

        $response = $this->actingAs($this->owner)
            ->post(route('inventory.transfers.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHas('failed');
    }

    public function test_user_can_view_transfer_detail_via_show_endpoint(): void
    {
        $transfer = StockTransfer::create([
            'business_id' => $this->business->id,
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'transfer_number' => 'TF-202609-010',
            'transfer_date' => now()->format('Y-m-d'),
            'status' => StockTransferStatus::Approved,
            'requested_by' => $this->supervisor->id,
        ]);

        $transfer->items()->create([
            'inventory_item_id' => $this->inventoryItem->id,
            'qty' => 5,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('inventory.transfers.show', $transfer->id));

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'id',
                'transfer_number',
                'status',
                'from_outlet',
                'to_outlet',
                'items',
            ],
        ]);
    }

    public function test_user_can_export_transfer_pdf(): void
    {
        $transfer = StockTransfer::create([
            'business_id' => $this->business->id,
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'transfer_number' => 'TF-202609-011',
            'transfer_date' => now()->format('Y-m-d'),
            'status' => StockTransferStatus::Approved,
            'requested_by' => $this->supervisor->id,
        ]);

        $transfer->items()->create([
            'inventory_item_id' => $this->inventoryItem->id,
            'qty' => 5,
        ]);

        $response = $this->actingAs($this->owner)
            ->get(route('inventory.transfers.export.pdf', $transfer->id));

        $response->assertOk();
        $this->assertEquals('application/pdf', $response->headers->get('content-type'));
    }
}
