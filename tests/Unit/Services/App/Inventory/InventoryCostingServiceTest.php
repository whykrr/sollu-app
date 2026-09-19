<?php

namespace Tests\Unit\Services\App\Inventory;

use App\Enums\InventoryCostingMethod;
use App\Enums\InventoryMovementType;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryCostLayer;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryMovement;
use App\Models\Outlet;
use App\Models\User;
use App\Services\App\Inventory\InventoryCostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryCostingServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InventoryCostingService $service;

    protected Business $business;

    protected User $user;

    protected Outlet $outlet;

    protected InventoryItem $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(InventoryCostingService::class);

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $this->business = Business::create([
            'name' => 'Test Business',
            'owner_name' => 'Owner',
            'email' => 'owner_'.uniqid().'@test.com',
            'phone' => '08123456789',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Test User',
            'email' => 'user_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Main Outlet',
            'is_active' => true,
        ]);

        $this->item = InventoryItem::firstOrCreate([
            'business_id' => $this->business->id,
        ], [
            'name' => 'Biji Kopi Arabika',
            'sku' => 'KOPI-001',
            'item_type' => 'raw_material',
        ]);
    }

    public function test_it_records_incoming_stock_and_creates_fifo_layer()
    {
        $this->service->recordIncomingStock(
            $this->business,
            $this->outlet,
            $this->item,
            10.0,
            10000.0,
            InventoryMovementType::Purchase,
            null,
            'PO-001'
        );

        $balance = InventoryBalance::where('business_id', $this->business->id)
            ->where('outlet_id', $this->outlet->id)
            ->where('inventory_item_id', $this->item->id)
            ->first();

        $this->assertNotNull($balance);
        $this->assertEquals(10.0, $balance->current_stock);
        $this->assertEquals(10000.0, $balance->average_cost);
        $this->assertEquals(100000.0, $balance->total_value);
        $this->assertEquals(10000.0, $balance->last_cost);

        $layers = InventoryCostLayer::where('inventory_item_id', $this->item->id)->get();
        $this->assertCount(1, $layers);
        $this->assertEquals(10.0, $layers->first()->qty_remaining);
        $this->assertEquals(10000.0, $layers->first()->purchase_price);

        $movement = InventoryMovement::where('inventory_item_id', $this->item->id)->first();
        $this->assertNotNull($movement);
        $this->assertEquals(10.0, $movement->qty_change);
        $this->assertEquals(10000.0, $movement->unit_cost);
        $this->assertEquals(100000.0, $movement->total_cost);
    }

    public function test_it_calculates_weighted_moving_average_on_consecutive_purchases()
    {
        // Batch 1: 10 units @ Rp 10.000
        $this->service->recordIncomingStock(
            $this->business,
            $this->outlet,
            $this->item,
            10.0,
            10000.0,
            InventoryMovementType::Purchase,
            null,
            'PO-001'
        );

        // Batch 2: 10 units @ Rp 20.000
        $this->service->recordIncomingStock(
            $this->business,
            $this->outlet,
            $this->item,
            10.0,
            20000.0,
            InventoryMovementType::Purchase,
            null,
            'PO-002'
        );

        $balance = InventoryBalance::where('inventory_item_id', $this->item->id)->first();
        $this->assertEquals(20.0, $balance->current_stock);
        $this->assertEquals(15000.0, $balance->average_cost); // (100k + 200k) / 20 = 15k
        $this->assertEquals(300000.0, $balance->total_value);
        $this->assertEquals(20000.0, $balance->last_cost);

        $layers = InventoryCostLayer::where('inventory_item_id', $this->item->id)->get();
        $this->assertCount(2, $layers);
    }

    public function test_it_consumes_stock_under_fifo_costing_method()
    {
        $this->business->settings = array_merge($this->business->settings ?? [], [
            'inventory_costing_method' => InventoryCostingMethod::FIFO->value,
        ]);
        $this->business->save();

        // Batch 1: 10 units @ Rp 10.000
        $this->service->recordIncomingStock(
            $this->business,
            $this->outlet,
            $this->item,
            10.0,
            10000.0,
            InventoryMovementType::Purchase,
            null,
            'PO-001'
        );

        // Batch 2: 10 units @ Rp 20.000
        $this->service->recordIncomingStock(
            $this->business,
            $this->outlet,
            $this->item,
            10.0,
            20000.0,
            InventoryMovementType::Purchase,
            null,
            'PO-002'
        );

        // Consume 15 units (10 from Batch 1 @ 10k + 5 from Batch 2 @ 20k)
        // Total expected cost = 100.000 + 100.000 = 200.000
        $costResult = $this->service->recordOutgoingStock(
            $this->business,
            $this->outlet,
            $this->item,
            15.0,
            InventoryMovementType::Sale,
            null,
            'INV-001'
        );

        $this->assertEquals(200000.0, $costResult['total_cogs']);
        $this->assertEquals(13333.33, round($costResult['unit_cogs'], 2));

        $balance = InventoryBalance::where('inventory_item_id', $this->item->id)->first();
        $this->assertEquals(5.0, $balance->current_stock);
        $this->assertEquals(100000.0, $balance->total_value); // 5 units remaining @ 20k
        $this->assertEquals(20000.0, $balance->average_cost);

        $activeLayers = InventoryCostLayer::where('inventory_item_id', $this->item->id)
            ->where('qty_remaining', '>', 0)
            ->get();
        $this->assertCount(1, $activeLayers);
        $this->assertEquals(5.0, $activeLayers->first()->qty_remaining);
        $this->assertEquals(20000.0, $activeLayers->first()->purchase_price);
    }

    public function test_it_consumes_stock_under_average_costing_method()
    {
        $this->business->settings = array_merge($this->business->settings ?? [], [
            'inventory_costing_method' => InventoryCostingMethod::AVERAGE->value,
        ]);
        $this->business->save();

        // Batch 1: 10 units @ Rp 10.000
        $this->service->recordIncomingStock(
            $this->business,
            $this->outlet,
            $this->item,
            10.0,
            10000.0,
            InventoryMovementType::Purchase,
            null,
            'PO-001'
        );

        // Batch 2: 10 units @ Rp 20.000 (Avg: 15.000)
        $this->service->recordIncomingStock(
            $this->business,
            $this->outlet,
            $this->item,
            10.0,
            20000.0,
            InventoryMovementType::Purchase,
            null,
            'PO-002'
        );

        // Consume 10 units @ Average 15.000 = 150.000
        $costResult = $this->service->recordOutgoingStock(
            $this->business,
            $this->outlet,
            $this->item,
            10.0,
            InventoryMovementType::Sale,
            null,
            'INV-001'
        );

        $this->assertEquals(150000.0, $costResult['total_cogs']);
        $this->assertEquals(15000.0, $costResult['unit_cogs']);

        $balance = InventoryBalance::where('inventory_item_id', $this->item->id)->first();
        $this->assertEquals(10.0, $balance->current_stock);
        $this->assertEquals(150000.0, $balance->total_value);
        $this->assertEquals(15000.0, $balance->average_cost);
    }

    public function test_it_switches_costing_method_seamlessly()
    {
        // 1. Start with FIFO
        $this->business->settings = ['inventory_costing_method' => InventoryCostingMethod::FIFO->value];
        $this->business->save();

        $this->service->recordIncomingStock(
            $this->business,
            $this->outlet,
            $this->item,
            10.0,
            10000.0,
            InventoryMovementType::Purchase,
            null,
            'PO-001'
        );

        $this->service->recordIncomingStock(
            $this->business,
            $this->outlet,
            $this->item,
            10.0,
            20000.0,
            InventoryMovementType::Purchase,
            null,
            'PO-002'
        );

        // 2. Switch to AVERAGE
        $this->service->switchCostingMethod($this->business, InventoryCostingMethod::AVERAGE);

        $this->business->refresh();
        $this->assertEquals(InventoryCostingMethod::AVERAGE, $this->business->getCostingMethod());

        // 3. Switch back to FIFO
        $this->service->switchCostingMethod($this->business, InventoryCostingMethod::FIFO);

        $this->business->refresh();
        $this->assertEquals(InventoryCostingMethod::FIFO, $this->business->getCostingMethod());
    }
}
