<?php

namespace Tests\Integration\Services\App\Inventory;

use App\Enums\PermissionEnum;
use App\Enums\StockTransferStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\StockTransfer;
use App\Models\Outlet;
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

    private function setupBaseData()
    {
        $this->seed(DatabaseSeeder::class);

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = Business::create([
            'name' => 'Test Business',
            'owner_name' => 'Owner',
            'email' => 'owner_'.uniqid().'@test.com',
            'phone' => '08123456789',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

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

    public function test_it_creates_transfer()
    {
        [$user, $business, $outlet1, $outlet2, $inventoryItem] = $this->setupBaseData();

        $data = [
            'from_outlet_id' => $outlet1->id,
            'to_outlet_id' => $outlet2->id,
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

    public function test_it_updates_transfer()
    {
        [$user, $business, $outlet1, $outlet2, $inventoryItem] = $this->setupBaseData();

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

        [$user, $business, $outlet1, $outlet2, $inventoryItem] = $this->setupBaseData();

        $business->settings = [
            'inventory_sod' => [
                'enabled' => true,
                'allow_owner_bypass' => false,
                'rules' => ['stock_transfer_approval' => true],
            ],
        ];
        $business->save();

        $requester = User::factory()->create(['business_id' => $business->id]);

        $transfer = $this->service->createTransfer([
            'from_outlet_id' => $outlet1->id,
            'to_outlet_id' => $outlet2->id,
            'transfer_date' => now()->format('Y-m-d'),
            'items' => [],
        ], $requester);

        $this->service->approveTransfer($transfer, $requester);
    }

    public function test_it_allows_self_approval_when_sod_is_disabled()
    {
        [$user, $business, $outlet1, $outlet2, $inventoryItem] = $this->setupBaseData();

        $business->settings = [
            'inventory_sod' => [
                'enabled' => false,
            ],
        ];
        $business->save();

        $requester = User::factory()->create(['business_id' => $business->id]);

        $transfer = $this->service->createTransfer([
            'from_outlet_id' => $outlet1->id,
            'to_outlet_id' => $outlet2->id,
            'transfer_date' => now()->format('Y-m-d'),
            'items' => [],
        ], $requester);

        $approved = $this->service->approveTransfer($transfer, $requester);
        $this->assertEquals(StockTransferStatus::Approved, $approved->status);
    }

    public function test_it_approves_transfer()
    {
        [$user, $business, $outlet1, $outlet2, $inventoryItem] = $this->setupBaseData();

        $requester = User::factory()->create(['business_id' => $business->id]);

        $transfer = $this->service->createTransfer([
            'from_outlet_id' => $outlet1->id,
            'to_outlet_id' => $outlet2->id,
            'transfer_date' => now()->format('Y-m-d'),
            'items' => [],
        ], $requester);

        $approvedTransfer = $this->service->approveTransfer($transfer, $user); // $user is admin

        $this->assertEquals(StockTransferStatus::Approved, $approvedTransfer->status);
        $this->assertEquals($user->id, $approvedTransfer->approved_by);
    }

    public function test_it_rejects_transfer()
    {
        [$user, $business, $outlet1, $outlet2, $inventoryItem] = $this->setupBaseData();

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

    public function test_it_ships_transfer()
    {
        [$user, $business, $outlet1, $outlet2, $inventoryItem] = $this->setupBaseData();

        $requester = User::factory()->create(['business_id' => $business->id]);
        $transfer = $this->service->createTransfer([
            'from_outlet_id' => $outlet1->id,
            'to_outlet_id' => $outlet2->id,
            'transfer_date' => now()->format('Y-m-d'),
            'items' => [],
        ], $requester);

        $this->service->approveTransfer($transfer, $user);

        $shippedTransfer = $this->service->shipTransfer($transfer, $user);

        $this->assertEquals(StockTransferStatus::InTransit, $shippedTransfer->status);
    }

    public function test_it_completes_transfer_and_updates_balances()
    {
        [$user, $business, $outlet1, $outlet2, $inventoryItem] = $this->setupBaseData();

        InventoryBalance::create([
            'business_id' => $business->id,
            'outlet_id' => $outlet1->id,
            'inventory_item_id' => $inventoryItem->id,
            'current_stock' => 15,
        ]);

        $requester = User::factory()->create(['business_id' => $business->id]);
        $transfer = $this->service->createTransfer([
            'from_outlet_id' => $outlet1->id,
            'to_outlet_id' => $outlet2->id,
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
}
