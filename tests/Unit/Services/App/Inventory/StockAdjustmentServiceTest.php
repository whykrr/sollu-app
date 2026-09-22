<?php

namespace Tests\Unit\Services\App\Inventory;

use App\Enums\AdjustmentReason;
use App\Enums\AdjustmentStatus;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\StockAdjustment;
use App\Models\User;
use App\Services\App\Inventory\StockAdjustmentService;
use App\Services\App\Master\ActivityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class StockAdjustmentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ActivityLogService $activityLogServiceMock;

    protected StockAdjustmentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activityLogServiceMock = Mockery::mock(ActivityLogService::class);
        $this->activityLogServiceMock->shouldReceive('log')->andReturnNull();

        $this->service = new StockAdjustmentService(
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

        setPermissionsTeamId($business->id);
        \Spatie\Permission\Models\Permission::findOrCreate('business.*', 'web');
        $user->givePermissionTo('business.*');

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

    public function test_it_creates_stock_adjustment()
    {
        [$user, $business, $outlet, $inventoryItem] = $this->setupBaseData();

        $data = [
            'outlet_id' => $outlet->id,
            'reason' => AdjustmentReason::Correction->value,
            'notes' => 'Test notes',
            'items' => [
                [
                    'inventory_item_id' => $inventoryItem->id,
                    'qty_change' => 10,
                    'unit_cost' => 1000,
                    'description' => 'Add 10 items',
                ],
            ],
        ];

        $adj = $this->service->create($data, $user);

        $this->assertInstanceOf(StockAdjustment::class, $adj);
        $this->assertEquals(AdjustmentStatus::Draft, $adj->status);
        $this->assertEquals($data['reason'], $adj->reason->value);
        $this->assertCount(1, $adj->items);
        $this->assertStringStartsWith('ADJ-', $adj->adjustment_number);
    }

    public function test_it_approves_stock_adjustment()
    {
        [$user, $business, $outlet, $inventoryItem] = $this->setupBaseData();

        $data = [
            'outlet_id' => $outlet->id,
            'reason' => AdjustmentReason::Correction->value,
            'notes' => 'Test',
            'items' => [
                [
                    'inventory_item_id' => $inventoryItem->id,
                    'qty_change' => 5,
                    'unit_cost' => 100,
                    'description' => 'Add 5',
                ],
            ],
        ];

        $adj = $this->service->create($data, $user);
        $user->givePermissionTo('business.*');
        $approvedAdj = $this->service->approve($adj, $user);

        $this->assertEquals(AdjustmentStatus::Approved, $approvedAdj->status);

        $balance = InventoryBalance::where('inventory_item_id', $inventoryItem->id)->first();
        $this->assertEquals(5, $balance->current_stock);

        $movement = $approvedAdj->inventoryMovements()->first();
        $this->assertNotNull($movement);
        $this->assertEquals(5, $movement->qty_change);
    }

    public function test_it_fails_to_approve_if_sod_is_enabled_and_user_created_adjustment()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Pemisahan tugas aktif');

        [$user, $business, $outlet, $inventoryItem] = $this->setupBaseData();

        $business->settings = [
            'inventory_sod' => [
                'enabled' => true,
                'allow_owner_bypass' => true,
                'rules' => ['stock_adjustment' => true],
            ],
        ];
        $business->save();

        $data = [
            'outlet_id' => $outlet->id,
            'reason' => AdjustmentReason::Correction->value,
            'notes' => 'Self created',
            'items' => [
                [
                    'inventory_item_id' => $inventoryItem->id,
                    'qty_change' => 5,
                    'unit_cost' => 100,
                    'description' => 'Add 5',
                ],
            ],
        ];
        $adj = $this->service->create($data, $user);

        $user->revokePermissionTo('business.*');
        $this->service->approve($adj, $user);
    }

    public function test_it_allows_self_approval_when_sod_is_disabled()
    {
        [$user, $business, $outlet, $inventoryItem] = $this->setupBaseData();

        $business->settings = [
            'inventory_sod' => [
                'enabled' => false,
            ],
        ];
        $business->save();

        $user->revokePermissionTo('business.*');

        $data = [
            'outlet_id' => $outlet->id,
            'reason' => AdjustmentReason::Correction->value,
            'notes' => 'Self created with SoD disabled',
            'items' => [
                [
                    'inventory_item_id' => $inventoryItem->id,
                    'qty_change' => 5,
                    'unit_cost' => 100,
                    'description' => 'Add 5',
                ],
            ],
        ];
        $adj = $this->service->create($data, $user);
        $approvedAdj = $this->service->approve($adj, $user);

        $this->assertEquals(AdjustmentStatus::Approved, $approvedAdj->status);
    }

    public function test_it_fails_to_approve_if_stock_becomes_negative()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Stok tidak mencukupi');

        [$user, $business, $outlet, $inventoryItem] = $this->setupBaseData();

        $data = [
            'outlet_id' => $outlet->id,
            'reason' => AdjustmentReason::Waste->value,
            'notes' => 'Test',
            'items' => [
                [
                    'inventory_item_id' => $inventoryItem->id,
                    'qty_change' => -5,
                    'unit_cost' => null,
                    'description' => 'Remove 5',
                ],
            ],
        ];
        $adj = $this->service->create($data, $user);

        $this->service->approve($adj, $user);
    }

    public function test_it_rejects_stock_adjustment()
    {
        [$user, $business, $outlet, $inventoryItem] = $this->setupBaseData();

        $data = [
            'outlet_id' => $outlet->id,
            'reason' => AdjustmentReason::Correction->value,
            'notes' => 'Test',
            'items' => [
                [
                    'inventory_item_id' => $inventoryItem->id,
                    'qty_change' => 5,
                    'unit_cost' => 100,
                    'description' => 'Add 5',
                ],
            ],
        ];
        $adj = $this->service->create($data, $user);

        $rejectedAdj = $this->service->reject($adj, 'Wrong items', $user);

        $this->assertEquals(AdjustmentStatus::Rejected, $rejectedAdj->status);
        $this->assertEquals('Wrong items', $rejectedAdj->notes);
    }

    public function test_it_voids_approved_stock_adjustment()
    {
        [$user, $business, $outlet, $inventoryItem] = $this->setupBaseData();

        $data = [
            'outlet_id' => $outlet->id,
            'reason' => AdjustmentReason::Correction->value,
            'notes' => 'Test',
            'items' => [
                [
                    'inventory_item_id' => $inventoryItem->id,
                    'qty_change' => 10,
                    'unit_cost' => 100,
                    'description' => 'Add 10',
                ],
            ],
        ];
        $adj = $this->service->create($data, $user);
        $this->service->approve($adj, $user);

        $balanceBefore = InventoryBalance::where('inventory_item_id', $inventoryItem->id)->first()->current_stock;
        $this->assertEquals(10, $balanceBefore);

        $voidedAdj = $this->service->void($adj, $user);

        $this->assertEquals(AdjustmentStatus::Voided, $voidedAdj->status);

        $balanceAfter = InventoryBalance::where('inventory_item_id', $inventoryItem->id)->first()->current_stock;
        $this->assertEquals(0, $balanceAfter);
    }
}
