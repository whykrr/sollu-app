<?php

namespace Tests\Unit\Services\App\Master;

use App\Models\Business;
use App\Models\Master\Product;
use App\Models\Outlet;
use App\Services\App\Master\InventoryService;
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
        $type = \App\Models\BusinessType::firstOrCreate(
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
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
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

        $variantGroup = \App\Models\Master\VariantGroup::create([
            'product_id' => $product->id,
            'name' => 'Size',
        ]);

        $option = \App\Models\Master\VariantGroupOption::create([
            'variant_group_id' => $variantGroup->id,
            'name' => 'Large',
        ]);

        $data = [
            'business_id' => $business->id,
            'name' => 'Product Large',
            'product_id' => $product->id,
            'sku' => 'PRD-L',
            'barcode' => '123456',
            'track_inventory' => true,
            'min_stock' => 5,
            'options' => [$option->id],
        ];

        $item = $this->service->createVariantInventory($data);

        $this->assertInstanceOf(\App\Models\Master\InventoryItem::class, $item);
        $this->assertEquals('Product Large', $item->name);
        $this->assertEquals('variant_sku', $item->item_type);
        $this->assertTrue($item->track_inventory);
        $this->assertEquals(5, $item->min_stock);

        $this->assertDatabaseHas('inventory_items', [
            'id' => $item->id,
            'sku' => 'PRD-L',
        ]);

        $this->assertTrue($item->variantGroupOptions->contains($option->id));

        $this->assertDatabaseHas('inventory_balances', [
            'inventory_item_id' => $item->id,
            'outlet_id' => $outlet->id,
            'current_stock' => 0,
        ]);
    }

    public function test_it_creates_variant_inventory_and_syncs_balances_for_specific_active_outlets()
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
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

        $data = [
            'business_id' => $business->id,
            'name' => 'Product Scoped Item',
            'product_id' => $product->id,
            'sku' => 'PRD-SCOPED',
            'track_inventory' => true,
            'min_stock' => 5,
        ];

        // Only Outlet A is active for this item
        $item = $this->service->createVariantInventory($data, [$outletA->id]);

        $this->assertDatabaseHas('inventory_balances', [
            'inventory_item_id' => $item->id,
            'outlet_id' => $outletA->id,
            'current_stock' => 0,
        ]);

        $this->assertDatabaseMissing('inventory_balances', [
            'inventory_item_id' => $item->id,
            'outlet_id' => $outletB->id,
        ]);

        // When Outlet B is activated later, sync balances creates balance for Outlet B without deleting Outlet A
        $this->service->syncInventoryBalances($item, [$outletA->id, $outletB->id]);

        $this->assertDatabaseHas('inventory_balances', [
            'inventory_item_id' => $item->id,
            'outlet_id' => $outletA->id,
        ]);

        $this->assertDatabaseHas('inventory_balances', [
            'inventory_item_id' => $item->id,
            'outlet_id' => $outletB->id,
            'current_stock' => 0,
        ]);

        // When Outlet A is disabled (target is only Outlet B), Outlet A's balance is PRESERVED (not deleted)
        $this->service->syncInventoryBalances($item, [$outletB->id]);

        $this->assertDatabaseHas('inventory_balances', [
            'inventory_item_id' => $item->id,
            'outlet_id' => $outletA->id,
        ]);
    }

    public function test_it_syncs_balances_only_if_tracking_inventory()
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
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

        $data = [
            'business_id' => $business->id,
            'name' => 'Product Untracked',
            'product_id' => $product->id,
            'track_inventory' => false,
        ];

        $item = $this->service->createVariantInventory($data);

        $this->assertDatabaseMissing('inventory_balances', [
            'inventory_item_id' => $item->id,
        ]);
    }
}
