<?php

namespace Tests\Unit\Services\App\Inventory;

use App\Enums\StockOpnameStatus;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\StockOpname;
use App\Models\User;
use App\Services\App\Inventory\StockOpnameService;
use App\Services\App\Master\ActivityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class StockOpnameServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ActivityLogService $activityLogServiceMock;

    protected StockOpnameService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activityLogServiceMock = Mockery::mock(ActivityLogService::class);
        $this->activityLogServiceMock->shouldReceive('log')->andReturnNull();

        $this->service = new StockOpnameService(
            $this->activityLogServiceMock,
            app(\App\Services\App\Inventory\InventoryCostingService::class),
            app(\App\Services\App\Inventory\InventorySodService::class)
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function setupBaseData()
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $type = \App\Models\BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = \App\Models\Business::create([
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

        $outlet = \App\Models\Outlet::create([
            'business_id' => $business->id,
            'name' => 'Main Outlet',
            'is_active' => true,
        ]);

        $inventoryItem = \App\Models\Inventory\InventoryItem::firstOrCreate([
            'business_id' => $business->id,
        ], [
            'name' => 'Flour',
            'sku' => 'FL-001',
            'item_type' => 'raw_material',
        ]);

        return [$user, $business, $outlet, $inventoryItem];
    }

    public function test_it_creates_opname()
    {
        [$user, $business, $outlet, $inventoryItem] = $this->setupBaseData();

        $data = [
            'outlet_id' => $outlet->id,
            'opname_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'inventory_item_id' => $inventoryItem->id,
                    'system_qty' => 10,
                    'actual_qty' => 8,
                ],
            ],
        ];

        $opname = $this->service->createOpname($data, $user);

        $this->assertInstanceOf(StockOpname::class, $opname);
        $this->assertEquals(StockOpnameStatus::InProgress, $opname->status);
        $this->assertCount(1, $opname->items);
        $this->assertEquals(8, $opname->items[0]->actual_qty);
        $this->assertEquals(-2, $opname->items[0]->difference_qty);
    }

    public function test_it_updates_opname_and_changes_status_to_pending()
    {
        [$user, $business, $outlet, $inventoryItem] = $this->setupBaseData();

        $opname = $this->service->createOpname([
            'outlet_id' => $outlet->id,
            'opname_date' => now()->format('Y-m-d'),
            'items' => [['inventory_item_id' => $inventoryItem->id, 'system_qty' => 10, 'actual_qty' => null]],
        ], $user);

        $updateData = [
            'notes' => 'Updated notes',
            'items' => [
                ['inventory_item_id' => $inventoryItem->id, 'system_qty' => 10, 'actual_qty' => 12],
            ],
        ];

        $updatedOpname = $this->service->updateOpname($opname, $updateData, $user);

        $this->assertEquals(StockOpnameStatus::PendingApproval, $updatedOpname->status);
        $this->assertEquals('Updated notes', $updatedOpname->notes);
        $this->assertEquals(12, $updatedOpname->items()->first()->actual_qty);
        $this->assertEquals(2, $updatedOpname->items()->first()->difference_qty);
    }

    public function test_it_fails_to_update_non_in_progress_opname()
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Hanya opname berstatus In Progress yang dapat diubah.');

        [$user, $business, $outlet, $inventoryItem] = $this->setupBaseData();

        $opname = $this->service->createOpname([
            'outlet_id' => $outlet->id,
            'opname_date' => now()->format('Y-m-d'),
            'items' => [],
        ], $user);

        $opname->status = StockOpnameStatus::PendingApproval;
        $opname->save();

        $this->service->updateOpname($opname, [], $user);
    }

    public function test_it_completes_opname_and_adjusts_inventory()
    {
        [$user, $business, $outlet, $inventoryItem] = $this->setupBaseData();

        $opname = $this->service->createOpname([
            'outlet_id' => $outlet->id,
            'opname_date' => now()->format('Y-m-d'),
            'items' => [],
        ], $user);

        $opname->status = StockOpnameStatus::PendingApproval;
        $opname->save();

        InventoryBalance::create([
            'business_id' => $business->id,
            'outlet_id' => $outlet->id,
            'inventory_item_id' => $inventoryItem->id,
            'current_stock' => 10,
            'average_cost' => 5000,
        ]);

        $completeData = [
            'items' => [
                ['inventory_item_id' => $inventoryItem->id, 'system_qty' => 10, 'actual_qty' => 8], // -2 difference
            ],
        ];

        $completedOpname = $this->service->completeOpname($opname, $completeData, $user);

        $this->assertEquals(StockOpnameStatus::Approved, $completedOpname->status);
        $this->assertEquals($user->id, $completedOpname->approved_by);

        $balance = InventoryBalance::where('inventory_item_id', $inventoryItem->id)->first();
        $this->assertEquals(8, (float) $balance->current_stock);
    }

    public function test_it_completes_opname_with_surplus()
    {
        [$user, $business, $outlet, $inventoryItem] = $this->setupBaseData();

        $opname = $this->service->createOpname([
            'outlet_id' => $outlet->id,
            'opname_date' => now()->format('Y-m-d'),
            'items' => [],
        ], $user);

        $opname->status = StockOpnameStatus::PendingApproval;
        $opname->save();

        InventoryBalance::create([
            'business_id' => $business->id,
            'outlet_id' => $outlet->id,
            'inventory_item_id' => $inventoryItem->id,
            'current_stock' => 10,
            'average_cost' => 5000,
        ]);

        $completeData = [
            'items' => [
                ['inventory_item_id' => $inventoryItem->id, 'system_qty' => 10, 'actual_qty' => 15], // +5 surplus
            ],
        ];

        $completedOpname = $this->service->completeOpname($opname, $completeData, $user);

        $this->assertEquals(StockOpnameStatus::Approved, $completedOpname->status);

        $balance = InventoryBalance::where('inventory_item_id', $inventoryItem->id)->first();
        $this->assertEquals(15, (float) $balance->current_stock);
    }

    public function test_it_rejects_opname()
    {
        [$user, $business, $outlet, $inventoryItem] = $this->setupBaseData();

        $opname = $this->service->createOpname([
            'outlet_id' => $outlet->id,
            'opname_date' => now()->format('Y-m-d'),
            'items' => [],
        ], $user);

        $opname->status = StockOpnameStatus::PendingApproval;
        $opname->save();

        $rejectedOpname = $this->service->rejectOpname($opname, ['notes' => 'Invalid count'], $user);

        $this->assertEquals(StockOpnameStatus::Rejected, $rejectedOpname->status);
        $this->assertEquals('Invalid count', $rejectedOpname->notes);
        $this->assertEquals($user->id, $rejectedOpname->approved_by);
    }

    public function test_it_fails_to_reject_non_pending_opname()
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Opname harus dalam status Menunggu Persetujuan untuk ditolak.');

        [$user, $business, $outlet, $inventoryItem] = $this->setupBaseData();

        $opname = $this->service->createOpname([
            'outlet_id' => $outlet->id,
            'opname_date' => now()->format('Y-m-d'),
            'items' => [],
        ], $user);

        // Status is InProgress
        $this->service->rejectOpname($opname, ['notes' => 'Invalid'], $user);
    }

    public function test_it_prevents_self_approval_when_sod_enabled()
    {
        [$user, $business, $outlet, $inventoryItem] = $this->setupBaseData();

        $business->update([
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

        $opname = $this->service->createOpname([
            'outlet_id' => $outlet->id,
            'opname_date' => now()->format('Y-m-d'),
            'items' => [],
        ], $user);

        $opname->status = StockOpnameStatus::PendingApproval;
        $opname->save();

        InventoryBalance::create([
            'business_id' => $business->id,
            'outlet_id' => $outlet->id,
            'inventory_item_id' => $inventoryItem->id,
            'current_stock' => 10,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Pemisahan tugas aktif');

        $this->service->completeOpname($opname, [
            'items' => [
                ['inventory_item_id' => $inventoryItem->id, 'system_qty' => 10, 'actual_qty' => 8],
            ],
        ], $user);
    }

    public function test_it_allows_self_approval_when_sod_disabled()
    {
        [$user, $business, $outlet, $inventoryItem] = $this->setupBaseData();

        $business->update([
            'settings' => [
                'inventory_sod' => [
                    'enabled' => false,
                ],
            ],
        ]);

        $opname = $this->service->createOpname([
            'outlet_id' => $outlet->id,
            'opname_date' => now()->format('Y-m-d'),
            'items' => [],
        ], $user);

        $opname->status = StockOpnameStatus::PendingApproval;
        $opname->save();

        InventoryBalance::create([
            'business_id' => $business->id,
            'outlet_id' => $outlet->id,
            'inventory_item_id' => $inventoryItem->id,
            'current_stock' => 10,
            'average_cost' => 5000,
        ]);

        $completedOpname = $this->service->completeOpname($opname, [
            'items' => [
                ['inventory_item_id' => $inventoryItem->id, 'system_qty' => 10, 'actual_qty' => 8],
            ],
        ], $user);

        $this->assertEquals(StockOpnameStatus::Approved, $completedOpname->status);
    }
}
