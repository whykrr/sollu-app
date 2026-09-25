<?php

namespace Tests\Unit\Services\App\Master;

use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryItem;
use App\Models\Master\Product;
use App\Models\Master\ProductItem;
use App\Models\Outlet;
use App\Models\Uom;
use App\Services\App\Master\InventoryService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InventoryService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InventoryService;
    }

    protected function createTenant(): Business
    {
        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        return Business::create([
            'name' => 'Test Business',
            'owner_name' => 'Owner',
            'email' => 'owner_'.uniqid().'@test.com',
            'phone' => '08123456789',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);
    }

    public function test_it_creates_variant_inventory_and_syncs_balances()
    {
        $this->seed(DatabaseSeeder::class);
        $business = $this->createTenant();

        $outlet = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Outlet 1',
            'is_active' => true,
        ]);

        $product = Product::create([
            'business_id' => $business->id,
            'name' => 'Product',
            'product_type' => 'basic',
        ]);
        $product->outlets()->attach($outlet->id, ['is_enabled' => true, 'is_available' => true]);

        $productItem = ProductItem::create([
            'business_id' => $business->id,
            'product_id' => $product->id,
            'name' => 'Product Large',
            'sku' => 'PRD-L',
            'barcode' => '123456',
            'track_inventory' => true,
            'item_type' => 'variant_sku',
        ]);

        $data = [
            'min_stock' => 5,
        ];

        $invItem = $this->service->linkInventoryItem($productItem, $data);

        $this->assertInstanceOf(InventoryItem::class, $invItem);
        $this->assertEquals('Product Large', $invItem->name);
        $this->assertEquals('variant_sku', $invItem->item_type);
        $this->assertTrue($invItem->track_inventory);
        $this->assertEquals(5, $invItem->minimum_stock);

        $this->assertDatabaseHas('inventory_items', [
            'id' => $invItem->id,
            'product_item_id' => $productItem->id,
        ]);

        $this->assertDatabaseHas('inventory_balances', [
            'inventory_item_id' => $invItem->id,
            'outlet_id' => $outlet->id,
            'current_stock' => 0,
        ]);
    }

    public function test_it_creates_variant_inventory_and_syncs_balances_for_specific_active_outlets()
    {
        $this->seed(DatabaseSeeder::class);
        $business = $this->createTenant();

        $outletA = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Outlet A',
            'is_active' => true,
        ]);

        $outletB = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Outlet B',
            'is_active' => true,
        ]);

        $product = Product::create([
            'business_id' => $business->id,
            'name' => 'Product Scoped',
            'product_type' => 'basic',
        ]);

        $productItem = ProductItem::create([
            'business_id' => $business->id,
            'product_id' => $product->id,
            'name' => 'Product Scoped Item',
            'sku' => 'PRD-SCOPED',
            'track_inventory' => true,
            'item_type' => 'variant_sku',
        ]);

        $data = [
            'min_stock' => 5,
        ];

        // Only Outlet A is active for this item
        $invItem = $this->service->linkInventoryItem($productItem, $data, [$outletA->id]);

        $this->assertDatabaseHas('inventory_balances', [
            'inventory_item_id' => $invItem->id,
            'outlet_id' => $outletA->id,
            'current_stock' => 0,
        ]);

        $this->assertDatabaseMissing('inventory_balances', [
            'inventory_item_id' => $invItem->id,
            'outlet_id' => $outletB->id,
        ]);

        // When Outlet B is activated later, sync balances creates balance for Outlet B without deleting Outlet A
        $this->service->syncInventoryBalances($invItem, [$outletA->id, $outletB->id]);

        $this->assertDatabaseHas('inventory_balances', [
            'inventory_item_id' => $invItem->id,
            'outlet_id' => $outletA->id,
        ]);

        $this->assertDatabaseHas('inventory_balances', [
            'inventory_item_id' => $invItem->id,
            'outlet_id' => $outletB->id,
            'current_stock' => 0,
        ]);

        // When Outlet A is disabled (target is only Outlet B), Outlet A's balance is PRESERVED (not deleted)
        $this->service->syncInventoryBalances($invItem, [$outletB->id]);

        $this->assertDatabaseHas('inventory_balances', [
            'inventory_item_id' => $invItem->id,
            'outlet_id' => $outletA->id,
        ]);
    }

    public function test_it_syncs_balances_only_if_tracking_inventory()
    {
        $this->seed(DatabaseSeeder::class);
        $business = $this->createTenant();

        $outlet = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Outlet 1',
            'is_active' => true,
        ]);

        $product = Product::create([
            'business_id' => $business->id,
            'name' => 'Product',
            'product_type' => 'basic',
        ]);

        $productItem = ProductItem::create([
            'business_id' => $business->id,
            'product_id' => $product->id,
            'name' => 'Product Untracked',
            'track_inventory' => false,
            'item_type' => 'variant_sku',
        ]);

        $invItem = $this->service->linkInventoryItem($productItem, []);

        $this->assertDatabaseMissing('inventory_balances', [
            'inventory_item_id' => $invItem->id,
        ]);
    }

    public function test_it_stores_and_updates_snapshot_name_and_uom_on_inventory_item()
    {
        $this->seed(DatabaseSeeder::class);
        $business = $this->createTenant();

        $uom1 = Uom::first();
        $uom2 = Uom::skip(1)->first();

        $product = Product::create([
            'business_id' => $business->id,
            'name' => 'Product Snapshot Test',
            'product_type' => 'basic',
        ]);

        $productItem = ProductItem::create([
            'business_id' => $business->id,
            'product_id' => $product->id,
            'uom_id' => $uom1->id,
            'name' => 'Product Snapshot Initial Name',
            'track_inventory' => true,
            'item_type' => 'variant_sku',
        ]);

        // Link initially
        $invItem = $this->service->linkInventoryItem($productItem, [
            'min_stock' => 10,
        ]);

        $this->assertEquals('Product Snapshot Initial Name', $invItem->name);
        $this->assertEquals($uom1->id, $invItem->uom_id);
        $this->assertEquals(10, $invItem->minimum_stock);

        // Update ProductItem and re-link
        $productItem->update([
            'name' => 'Product Snapshot Updated Name',
            'uom_id' => $uom2->id,
        ]);

        $updatedInvItem = $this->service->linkInventoryItem($productItem, [
            'min_stock' => 20,
        ]);

        $this->assertEquals($invItem->id, $updatedInvItem->id);
        $this->assertEquals('Product Snapshot Updated Name', $updatedInvItem->name);
        $this->assertEquals($uom2->id, $updatedInvItem->uom_id);
        $this->assertEquals(20, $updatedInvItem->minimum_stock);

        $this->assertDatabaseHas('inventory_items', [
            'id' => $invItem->id,
            'name' => 'Product Snapshot Updated Name',
            'uom_id' => $uom2->id,
            'minimum_stock' => 20,
        ]);
    }

    public function test_it_preserves_existing_minimum_stock_when_omitted_by_product_update()
    {
        $this->seed(DatabaseSeeder::class);
        $business = $this->createTenant();

        $product = Product::create([
            'business_id' => $business->id,
            'name' => 'Product Minimum Stock Preservation Test',
            'product_type' => 'basic',
        ]);

        $productItem = ProductItem::create([
            'business_id' => $business->id,
            'product_id' => $product->id,
            'name' => 'Product Item',
            'track_inventory' => true,
            'item_type' => 'variant_sku',
        ]);

        // Existing inventory item created/configured with minimum_stock = 15 by inventory team
        $invItem = InventoryItem::create([
            'business_id' => $business->id,
            'product_item_id' => $productItem->id,
            'name' => 'Product Item',
            'minimum_stock' => 15,
            'is_active' => true,
        ]);

        // Product module updates name/uom without passing min_stock
        $productItem->update(['name' => 'Product Item Renamed']);
        $result = $this->service->linkInventoryItem($productItem, []);

        $this->assertEquals(15, $result->minimum_stock);
        $this->assertEquals('Product Item Renamed', $result->name);
        $this->assertDatabaseHas('inventory_items', [
            'id' => $invItem->id,
            'name' => 'Product Item Renamed',
            'minimum_stock' => 15,
        ]);
    }
}
