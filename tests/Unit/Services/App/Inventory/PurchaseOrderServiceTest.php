<?php

namespace Tests\Unit\Services\App\Inventory;

use App\Enums\PurchaseOrderStatus;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryCostLayer;
use App\Models\Inventory\InventoryMovement;
use App\Models\Inventory\PurchaseOrder;
use App\Models\User;
use App\Services\App\Inventory\PurchaseOrderService;
use App\Services\App\Master\ActivityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PurchaseOrderServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ActivityLogService $activityLogServiceMock;

    protected PurchaseOrderService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activityLogServiceMock = Mockery::mock(ActivityLogService::class);
        $this->activityLogServiceMock->shouldReceive('log')->andReturnNull();

        $this->service = new PurchaseOrderService(
            $this->activityLogServiceMock,
            app(\App\Services\App\Inventory\InventoryCostingService::class)
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
            'name' => 'Item 1',
            'sku' => 'SKU-1',
            'item_type' => 'raw_material',
        ]);

        return [$user, $business, $outlet, $inventoryItem];
    }

    public function test_it_creates_purchase_order_successfully()
    {
        [$user, $business, $outlet, $inventoryItem] = $this->setupBaseData();

        $data = [
            'outlet_id' => $outlet->id,
            'supplier_id' => null,
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'inventory_item_id' => $inventoryItem->id,
                    'qty_ordered' => 5,
                    'purchase_price' => 1000,
                ],
            ],
        ];

        $po = $this->service->createPO($data, $user);

        $this->assertInstanceOf(PurchaseOrder::class, $po);
        $this->assertEquals(PurchaseOrderStatus::Draft, $po->status);
        $this->assertEquals(5000, $po->total_amount);
        $this->assertCount(1, $po->items);
        $this->assertEquals($inventoryItem->id, $po->items[0]->inventory_item_id);
    }

    public function test_it_updates_draft_purchase_order()
    {
        [$user, $business, $outlet, $inventoryItem] = $this->setupBaseData();

        $po = $this->service->createPO([
            'outlet_id' => $outlet->id,
            'order_date' => now()->format('Y-m-d'),
            'items' => [['inventory_item_id' => $inventoryItem->id, 'qty_ordered' => 5, 'purchase_price' => 1000]],
        ], $user);

        $updateData = [
            'items' => [
                ['inventory_item_id' => $inventoryItem->id, 'qty_ordered' => 10, 'purchase_price' => 1000],
            ],
        ];

        $poUpdated = $this->service->updatePO($po, $updateData, $user);

        $this->assertEquals(10000, $poUpdated->total_amount);
        $this->assertEquals(10, $poUpdated->items()->first()->qty_ordered);
    }

    public function test_it_cannot_update_non_draft_po()
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Hanya PO berstatus Draft yang dapat diubah.');

        [$user, $business, $outlet, $inventoryItem] = $this->setupBaseData();
        $po = $this->service->createPO(['outlet_id' => $outlet->id, 'order_date' => now()->format('Y-m-d'), 'items' => []], $user);

        $po->status = PurchaseOrderStatus::Ordered;
        $po->save();

        $this->service->updatePO($po, [], $user);
    }

    public function test_it_marks_as_ordered()
    {
        [$user, $business, $outlet, $inventoryItem] = $this->setupBaseData();
        $po = $this->service->createPO(['outlet_id' => $outlet->id, 'order_date' => now()->format('Y-m-d'), 'items' => []], $user);

        $poOrdered = $this->service->markAsOrdered($po, $user);

        $this->assertEquals(PurchaseOrderStatus::Ordered, $poOrdered->status);
    }

    public function test_it_cancels_ordered_po()
    {
        [$user, $business, $outlet, $inventoryItem] = $this->setupBaseData();
        $po = $this->service->createPO(['outlet_id' => $outlet->id, 'order_date' => now()->format('Y-m-d'), 'items' => []], $user);
        $po->status = PurchaseOrderStatus::Ordered;
        $po->save();

        $poCancelled = $this->service->cancel($po, $user);

        $this->assertEquals(PurchaseOrderStatus::Cancelled, $poCancelled->status);
    }

    public function test_it_receives_po_and_updates_inventory()
    {
        [$user, $business, $outlet, $inventoryItem] = $this->setupBaseData();
        $po = $this->service->createPO([
            'outlet_id' => $outlet->id,
            'order_date' => now()->format('Y-m-d'),
            'items' => [['inventory_item_id' => $inventoryItem->id, 'qty_ordered' => 5, 'purchase_price' => 1000]],
        ], $user);

        $po->status = PurchaseOrderStatus::Ordered;
        $po->save();
        $poItem = $po->items()->first();

        $receivedData = [
            'items' => [
                [
                    'id' => $poItem->id,
                    'qty_received' => 5,
                    'conversion_factor' => 1.0,
                ],
            ],
        ];

        $poReceived = $this->service->receivePO($po, $receivedData, $user);

        $this->assertEquals(PurchaseOrderStatus::Received, $poReceived->status);

        $balance = InventoryBalance::where('inventory_item_id', $inventoryItem->id)->first();
        $this->assertNotNull($balance);
        $this->assertEquals(5, $balance->current_stock);

        $movement = InventoryMovement::where('inventory_item_id', $inventoryItem->id)->first();
        $this->assertNotNull($movement);
        $this->assertEquals(5, $movement->qty_change);

        $layer = InventoryCostLayer::where('inventory_item_id', $inventoryItem->id)->first();
        $this->assertNotNull($layer);
        $this->assertEquals(5, $layer->qty_purchased);
    }

    public function test_it_voids_received_po()
    {
        [$user, $business, $outlet, $inventoryItem] = $this->setupBaseData();
        $po = $this->service->createPO([
            'outlet_id' => $outlet->id,
            'order_date' => now()->format('Y-m-d'),
            'items' => [['inventory_item_id' => $inventoryItem->id, 'qty_ordered' => 5, 'purchase_price' => 1000]],
        ], $user);
        $po->status = PurchaseOrderStatus::Ordered;
        $po->save();

        $poItem = $po->items()->first();
        $this->service->receivePO($po, [
            'items' => [
                ['id' => $poItem->id, 'qty_received' => 5, 'conversion_factor' => 1.0],
            ],
        ], $user);

        $poVoided = $this->service->void($po, $user);

        $this->assertEquals(PurchaseOrderStatus::Cancelled, $poVoided->status);

        $balance = InventoryBalance::where('inventory_item_id', $inventoryItem->id)->first();
        $this->assertEquals(0, $balance->current_stock);

        $this->assertDatabaseMissing('inventory_cost_layers', [
            'reference_id' => $po->id,
        ]);
    }
}
