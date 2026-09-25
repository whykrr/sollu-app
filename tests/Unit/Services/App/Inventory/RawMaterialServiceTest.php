<?php

namespace Tests\Unit\Services\App\Inventory;

use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryItem;
use App\Models\Outlet;
use App\Models\User;
use App\Services\App\Inventory\RawMaterialService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RawMaterialServiceTest extends TestCase
{
    use RefreshDatabase;

    protected RawMaterialService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new RawMaterialService;
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

        $outlet = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Outlet Test',
            'is_active' => true,
        ]);

        // Inactive outlet
        Outlet::create(['business_id' => $business->id, 'name' => 'Inactive Outlet', 'is_active' => false]);

        return [$user, $business, $outlet];
    }

    public function test_it_creates_raw_material_and_initializes_balances()
    {
        // Arrange
        [$user, $business, $outlet] = $this->setupBaseData();

        $data = [
            'name' => 'Flour',
            'sku' => 'FL-001',
            'uom_id' => null,
            'is_track_stock' => true,
        ];

        // Act
        $item = $this->service->createRawMaterial($data, $business);

        // Assert
        $this->assertInstanceOf(InventoryItem::class, $item);
        $this->assertEquals('Flour', $item->name);
        $this->assertEquals('raw_material', $item->item_type);

        $this->assertDatabaseHas('product_items', [
            'id' => $item->product_item_id,
            'name' => 'Flour',
            'item_type' => 'raw_material',
        ]);

        $this->assertDatabaseHas('inventory_items', [
            'id' => $item->id,
            'product_item_id' => $item->product_item_id,
        ]);

        // Check balances initialized for active outlets only
        $activeOutletsCount = $business->outlets()->active()->count();
        $balancesCount = InventoryBalance::where('inventory_item_id', $item->id)->count();

        $this->assertEquals($activeOutletsCount, $balancesCount);
        $this->assertGreaterThan(0, $activeOutletsCount);

        $this->assertDatabaseHas('inventory_balances', [
            'inventory_item_id' => $item->id,
            'outlet_id' => $outlet->id,
            'current_stock' => 0,
        ]);
    }

    public function test_it_updates_raw_material()
    {
        // Arrange
        [$user, $business, $outlet] = $this->setupBaseData();
        $item = $this->service->createRawMaterial([
            'name' => 'Sugar',
            'sku' => 'SG-001',
        ], $business);

        // Act
        $updatedItem = $this->service->updateRawMaterial($item, [
            'name' => 'Brown Sugar',
            'sku' => 'BSG-001',
        ]);

        // Assert
        $this->assertEquals('Brown Sugar', $updatedItem->name);
        $this->assertEquals('BSG-001', $updatedItem->sku);
        $this->assertDatabaseHas('product_items', [
            'id' => $item->product_item_id,
            'name' => 'Brown Sugar',
            'sku' => 'BSG-001',
        ]);
    }
}
