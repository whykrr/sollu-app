<?php

namespace Tests\Integration\Services\App\Inventory;

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
use App\Services\App\Inventory\InventoryCostingService;
use App\Services\App\Inventory\InventorySodService;
use App\Services\App\Inventory\StockFreezeService;
use App\Services\App\Inventory\StockTransferService;
use App\Services\App\Master\ActivityLogService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StockTransferServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ActivityLogService $activityLogServiceMock;

    protected StockFreezeService $stockFreezeServiceMock;

    protected StockTransferService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activityLogServiceMock = Mockery::mock(ActivityLogService::class);
        $this->activityLogServiceMock->shouldReceive('log')->andReturnNull();

        $this->stockFreezeServiceMock = Mockery::mock(StockFreezeService::class);
        $this->stockFreezeServiceMock->shouldReceive('assertNotFrozen')->andReturnNull();

        $this->service = new StockTransferService(
            $this->activityLogServiceMock,
            $this->stockFreezeServiceMock,
            app(InventoryCostingService::class),
            app(InventorySodService::class)
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function setupBaseData(bool $enableSupervision = false)
    {
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

        $business = Business::create([
            'name' => 'Test Business',
            'owner_name' => 'Owner',
            'email' => 'owner_'.uniqid().'@test.com',
            'phone' => '08123456789',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
            'settings' => [
                'active_features' => [
                    FeatureEnum::INVENTORY_MANAGEMENT->value,
                    FeatureEnum::STOCK_TRANSFERS->value,
                    FeatureEnum::DOUBLE_AUTHORIZATION->value,
                ],
                'inventory_sod' => [
                    'enabled' => $enableSupervision,
                    'allow_owner_bypass' => false,
                    'rules' => ['stock_transfer_approval' => true],
                ],
            ],
        ]);

        $proPlan = SubscriptionPlan::where('code', PlanEnum::PRO->value)->first();
        if ($proPlan) {
            Subscription::create([
                'business_id' => $business->id,
                'plan_id' => $proPlan->id,
                'status' => SubscriptionStatus::Active,
                'billing_cycle' => 'monthly',
                'started_at' => now()->subDay(),
                'expired_at' => now()->addMonth(),
            ]);
            $business->clearMemoizedFeatures();
        }

        $user = User::create([
            'business_id' => $business->id,
            'name' => 'Test User',
            'email' => 'user_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
        ]);

        setPermissionsTeamId($business->id);
        $user->update(['is_root_user' => true]);
        foreach ([
            PermissionEnum::INVENTORY_TRANSFER_VIEW->value,
            PermissionEnum::INVENTORY_TRANSFER_CREATE->value,
            PermissionEnum::INVENTORY_TRANSFER_UPDATE->value,
            PermissionEnum::INVENTORY_TRANSFER_VALIDATE->value,
            PermissionEnum::INVENTORY_TRANSFER_RECEIVE->value,
            PermissionEnum::INVENTORY_TRANSFER_DELETE->value,
        ] as $p) {
            Permission::findOrCreate($p, 'business');
            Permission::findOrCreate($p, 'web');
            $user->givePermissionTo($p);
        }

        $outlet1 = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Outlet 1',
            'is_active' => true,
        ]);

        $outlet2 = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Outlet 2',
            'is_active' => true,
        ]);

        $inventoryItem = InventoryItem::firstOrCreate([
            'business_id' => $business->id,
        ], [
            'name' => 'Flour',
            'sku' => 'FL-001',
            'item_type' => 'raw_material',
        ]);

        return [$user, $business, $outlet1, $outlet2, $inventoryItem];
    }

    public function test_it_creates_transfer_in_pending_when_supervision_is_enabled()
    {
        [$user, $business, $outlet1, $outlet2, $inventoryItem] = $this->setupBaseData(enableSupervision: true);

        $data = [
            'from_outlet_id' => $outlet1->id,
            'to_outlet_id' => $outlet2->id,
            'transfer_mode' => StockTransferMode::DIRECT->value,
            'transfer_date' => now()->format('Y-m-d'),
            'notes' => 'Test Transfer',
            'items' => [
                [
                    'inventory_item_id' => $inventoryItem->id,
                    'qty' => 10,
                ],
            ],
        ];

        $transfer = $this->service->createTransfer($data, $user);

        $this->assertInstanceOf(StockTransfer::class, $transfer);
        $this->assertEquals(StockTransferStatus::Pending, $transfer->status);
        $this->assertCount(1, $transfer->items);
        $this->assertStringStartsWith('TF-', $transfer->transfer_number);
    }

    public function test_it_creates_direct_transfer_and_completes_instantly_when_supervision_disabled()
    {
        [$user, $business, $outlet1, $outlet2, $inventoryItem] = $this->setupBaseData(enableSupervision: false);

        InventoryBalance::create([
            'business_id' => $business->id,
            'outlet_id' => $outlet1->id,
            'inventory_item_id' => $inventoryItem->id,
            'current_stock' => 50,
            'average_cost' => 1000,
        ]);

        $data = [
            'from_outlet_id' => $outlet1->id,
            'to_outlet_id' => $outlet2->id,
            'transfer_mode' => StockTransferMode::DIRECT->value,
            'transfer_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'inventory_item_id' => $inventoryItem->id,
                    'qty' => 10,
                ],
            ],
        ];

        $transfer = $this->service->createTransfer($data, $user);

        $this->assertEquals(StockTransferStatus::Completed, $transfer->status);
        $sourceBalance = InventoryBalance::where('outlet_id', $outlet1->id)->where('inventory_item_id', $inventoryItem->id)->first();
        $this->assertEquals(40, $sourceBalance->current_stock);

        $destBalance = InventoryBalance::where('outlet_id', $outlet2->id)->where('inventory_item_id', $inventoryItem->id)->first();
        $this->assertEquals(10, $destBalance->current_stock);
    }

    public function test_it_updates_pending_transfer()
    {
        [$user, $business, $outlet1, $outlet2, $inventoryItem] = $this->setupBaseData(enableSupervision: true);

        $transfer = $this->service->createTransfer([
            'from_outlet_id' => $outlet1->id,
            'to_outlet_id' => $outlet2->id,
            'transfer_date' => now()->format('Y-m-d'),
            'items' => [['inventory_item_id' => $inventoryItem->id, 'qty' => 10]],
        ], $user);

        $updatedTransfer = $this->service->updateTransfer($transfer, [
            'notes' => 'Updated notes',
            'items' => [
                ['inventory_item_id' => $inventoryItem->id, 'qty' => 20],
            ],
        ]);

        $this->assertEquals('Updated notes', $updatedTransfer->notes);
        $this->assertEquals(20, $updatedTransfer->items()->first()->qty);
    }

    public function test_it_fails_to_approve_own_transfer_when_sod_is_enabled()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Pemisahan tugas aktif');

        [$user, $business, $outlet1, $outlet2, $inventoryItem] = $this->setupBaseData(enableSupervision: true);

        $requester = User::factory()->create(['business_id' => $business->id]);
        $requester->givePermissionTo(PermissionEnum::INVENTORY_TRANSFER_VALIDATE->value);

        $transfer = $this->service->createTransfer([
            'from_outlet_id' => $outlet1->id,
            'to_outlet_id' => $outlet2->id,
            'transfer_mode' => StockTransferMode::TWO_STEP->value,
            'transfer_date' => now()->format('Y-m-d'),
            'items' => [],
        ], $requester);

        $this->service->approveTransfer($transfer, $requester);
    }

    public function test_it_allows_self_approval_when_sod_is_disabled()
    {
        [$user, $business, $outlet1, $outlet2, $inventoryItem] = $this->setupBaseData(enableSupervision: false);

        $requester = User::factory()->create(['business_id' => $business->id]);

        // Manually create pending transfer to test approval when SoD is disabled
        $transfer = StockTransfer::create([
            'business_id' => $business->id,
            'from_outlet_id' => $outlet1->id,
            'to_outlet_id' => $outlet2->id,
            'transfer_number' => 'TF-202610-TEST',
            'status' => StockTransferStatus::Pending,
            'transfer_mode' => StockTransferMode::TWO_STEP,
            'requested_by' => $requester->id,
        ]);

        $approved = $this->service->approveTransfer($transfer, $requester);
        $this->assertEquals(StockTransferStatus::Approved, $approved->status);
    }

    public function test_it_approves_transfer()
    {
        [$user, $business, $outlet1, $outlet2, $inventoryItem] = $this->setupBaseData(enableSupervision: true);

        $requester = User::factory()->create(['business_id' => $business->id]);

        $transfer = $this->service->createTransfer([
            'from_outlet_id' => $outlet1->id,
            'to_outlet_id' => $outlet2->id,
            'transfer_mode' => StockTransferMode::TWO_STEP->value,
            'transfer_date' => now()->format('Y-m-d'),
            'items' => [],
        ], $requester);

        $approvedTransfer = $this->service->approveTransfer($transfer, $user); // $user is admin

        $this->assertEquals(StockTransferStatus::Approved, $approvedTransfer->status);
        $this->assertEquals($user->id, $approvedTransfer->approved_by);
    }

    public function test_it_rejects_transfer()
    {
        [$user, $business, $outlet1, $outlet2, $inventoryItem] = $this->setupBaseData(enableSupervision: true);

        $transfer = $this->service->createTransfer([
            'from_outlet_id' => $outlet1->id,
            'to_outlet_id' => $outlet2->id,
            'transfer_date' => now()->format('Y-m-d'),
            'items' => [],
        ], $user);

        $rejectedTransfer = $this->service->rejectTransfer($transfer, ['notes' => 'Rejected'], $user);

        $this->assertEquals(StockTransferStatus::Rejected, $rejectedTransfer->status);
        $this->assertEquals('Rejected', $rejectedTransfer->notes);
    }

    public function test_it_ships_transfer_and_deducts_origin_stock()
    {
        [$user, $business, $outlet1, $outlet2, $inventoryItem] = $this->setupBaseData(enableSupervision: true);

        InventoryBalance::create([
            'business_id' => $business->id,
            'outlet_id' => $outlet1->id,
            'inventory_item_id' => $inventoryItem->id,
            'current_stock' => 50,
            'average_cost' => 1200,
        ]);

        $requester = User::factory()->create(['business_id' => $business->id]);
        $transfer = $this->service->createTransfer([
            'from_outlet_id' => $outlet1->id,
            'to_outlet_id' => $outlet2->id,
            'transfer_mode' => StockTransferMode::TWO_STEP->value,
            'transfer_date' => now()->format('Y-m-d'),
            'items' => [['inventory_item_id' => $inventoryItem->id, 'qty' => 10]],
        ], $requester);

        $this->service->approveTransfer($transfer, $user);

        $shippedTransfer = $this->service->shipTransfer($transfer, $user);

        $this->assertEquals(StockTransferStatus::InTransit, $shippedTransfer->status);
        $this->assertNotNull($shippedTransfer->shipped_at);

        // Origin stock deducted upon shipping
        $sourceBalance = InventoryBalance::where('outlet_id', $outlet1->id)->where('inventory_item_id', $inventoryItem->id)->first();
        $this->assertEquals(40, $sourceBalance->current_stock);
    }

    public function test_it_completes_transfer_and_updates_balances()
    {
        [$user, $business, $outlet1, $outlet2, $inventoryItem] = $this->setupBaseData(enableSupervision: true);

        InventoryBalance::create([
            'business_id' => $business->id,
            'outlet_id' => $outlet1->id,
            'inventory_item_id' => $inventoryItem->id,
            'current_stock' => 15,
            'average_cost' => 1000,
        ]);

        $requester = User::factory()->create(['business_id' => $business->id]);
        $transfer = $this->service->createTransfer([
            'from_outlet_id' => $outlet1->id,
            'to_outlet_id' => $outlet2->id,
            'transfer_mode' => StockTransferMode::TWO_STEP->value,
            'transfer_date' => now()->format('Y-m-d'),
            'items' => [['inventory_item_id' => $inventoryItem->id, 'qty' => 10]],
        ], $requester);

        $this->service->approveTransfer($transfer, $user);
        $this->service->shipTransfer($transfer, $user);

        $transferItem = $transfer->items()->first();

        $receivedData = [
            'items' => [
                [
                    'id' => $transferItem->id,
                    'qty_received' => 10,
                ],
            ],
        ];

        $completedTransfer = $this->service->completeTransfer($transfer, $receivedData, $user);

        $this->assertEquals(StockTransferStatus::Completed, $completedTransfer->status);

        $sourceBalance = InventoryBalance::where('outlet_id', $outlet1->id)->where('inventory_item_id', $inventoryItem->id)->first();
        $this->assertEquals(5, $sourceBalance->current_stock); // 15 - 10

        $destBalance = InventoryBalance::where('outlet_id', $outlet2->id)->where('inventory_item_id', $inventoryItem->id)->first();
        $this->assertEquals(10, $destBalance->current_stock); // 0 + 10
    }

    public function test_it_voids_in_transit_transfer_and_restores_origin_stock()
    {
        [$user, $business, $outlet1, $outlet2, $inventoryItem] = $this->setupBaseData(enableSupervision: true);

        InventoryBalance::create([
            'business_id' => $business->id,
            'outlet_id' => $outlet1->id,
            'inventory_item_id' => $inventoryItem->id,
            'current_stock' => 20,
            'average_cost' => 1000,
        ]);

        $requester = User::factory()->create(['business_id' => $business->id]);
        $transfer = $this->service->createTransfer([
            'from_outlet_id' => $outlet1->id,
            'to_outlet_id' => $outlet2->id,
            'transfer_mode' => StockTransferMode::TWO_STEP->value,
            'items' => [['inventory_item_id' => $inventoryItem->id, 'qty' => 10]],
        ], $requester);

        $this->service->approveTransfer($transfer, $user);
        $this->service->shipTransfer($transfer, $user);

        $sourceBalance = InventoryBalance::where('outlet_id', $outlet1->id)->where('inventory_item_id', $inventoryItem->id)->first();
        $this->assertEquals(10, $sourceBalance->current_stock); // Deducted 10

        // Void the in-transit transfer
        $voidedTransfer = $this->service->voidTransfer($transfer, ['void_reason' => 'Truck breakdown'], $user);

        $this->assertEquals(StockTransferStatus::Void, $voidedTransfer->status);
        $this->assertEquals('Truck breakdown', $voidedTransfer->void_reason);

        // Origin stock returned back to 20
        $sourceBalance->refresh();
        $this->assertEquals(20, $sourceBalance->current_stock);
    }

    public function test_it_voids_completed_transfer_with_symmetric_reversal()
    {
        [$user, $business, $outlet1, $outlet2, $inventoryItem] = $this->setupBaseData(enableSupervision: true);

        InventoryBalance::create([
            'business_id' => $business->id,
            'outlet_id' => $outlet1->id,
            'inventory_item_id' => $inventoryItem->id,
            'current_stock' => 30,
            'average_cost' => 1500,
        ]);

        $requester = User::factory()->create(['business_id' => $business->id]);
        $transfer = $this->service->createTransfer([
            'from_outlet_id' => $outlet1->id,
            'to_outlet_id' => $outlet2->id,
            'transfer_mode' => StockTransferMode::TWO_STEP->value,
            'items' => [['inventory_item_id' => $inventoryItem->id, 'qty' => 10]],
        ], $requester);

        $this->service->approveTransfer($transfer, $user);
        $this->service->shipTransfer($transfer, $user);

        $transferItem = $transfer->items()->first();
        $this->service->completeTransfer($transfer, [
            'items' => [['id' => $transferItem->id, 'qty_received' => 10]],
        ], $user);

        // Origin is 20, destination is 10
        $this->assertEquals(20, InventoryBalance::where('outlet_id', $outlet1->id)->where('inventory_item_id', $inventoryItem->id)->first()->current_stock);
        $this->assertEquals(10, InventoryBalance::where('outlet_id', $outlet2->id)->where('inventory_item_id', $inventoryItem->id)->first()->current_stock);

        // Void completed transfer
        $voided = $this->service->voidTransfer($transfer, ['void_reason' => 'Wrong branch requested'], $user);
        $this->assertEquals(StockTransferStatus::Void, $voided->status);

        // Destination deducted back to 0, Origin restored to 30
        $this->assertEquals(30, InventoryBalance::where('outlet_id', $outlet1->id)->where('inventory_item_id', $inventoryItem->id)->first()->current_stock);
        $this->assertEquals(0, InventoryBalance::where('outlet_id', $outlet2->id)->where('inventory_item_id', $inventoryItem->id)->first()->current_stock);
    }
}
