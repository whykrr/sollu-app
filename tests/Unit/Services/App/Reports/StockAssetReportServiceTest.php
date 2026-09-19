<?php

namespace Tests\Unit\Services\App\Reports;

use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryMovement;
use App\Models\Outlet;
use App\Models\User;
use App\Services\App\Reports\StockAssetReportService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StockAssetReportServiceTest extends TestCase
{
    use RefreshDatabase;

    protected StockAssetReportService $service;

    protected Business $business;

    protected User $user;

    protected Outlet $outlet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new StockAssetReportService;

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
            'name' => 'Outlet Report',
            'is_active' => true,
        ]);
    }

    public function test_it_gets_report()
    {
        $invItem = InventoryItem::firstOrCreate([
            'business_id' => $this->business->id,
        ], [
            'name' => 'Beras Organik',
            'sku' => 'BERAS-001',
            'item_type' => 'raw_material',
            'minimum_stock' => 10,
        ]);

        $now = Carbon::now();

        DB::table('inventory_balances')->insert([
            'id' => \Illuminate\Support\Str::uuid()->toString(),
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $invItem->id,
            'current_stock' => 15,
            'average_cost' => 12000,
            'total_value' => 180000,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        // Stock in
        InventoryMovement::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $invItem->id,
            'movement_type' => 'purchase',
            'qty_change' => 20,
            'stock_before' => 0,
            'stock_after' => 20,
            'unit_cost' => 12000,
            'total_cost' => 240000,
            'created_at' => $now,
        ]);

        // Stock out
        InventoryMovement::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $invItem->id,
            'movement_type' => 'sale',
            'qty_change' => -5,
            'stock_before' => 20,
            'stock_after' => 15,
            'unit_cost' => 12000,
            'total_cost' => 60000,
            'created_at' => $now->copy()->addMinutes(5),
        ]);

        $startDate = $now->copy()->startOfDay();
        $endDate = $now->copy()->endOfDay();

        $result = $this->service->getReport($this->outlet->id, $startDate, $endDate);

        $this->assertNotEmpty($result->items());
        $firstItem = $result->items()[0];

        $this->assertEquals('Beras Organik', $firstItem->item_name);
        $this->assertEquals(0, $firstItem->starting_stock);
        $this->assertEquals(20, $firstItem->stock_in);
        $this->assertEquals(5, $firstItem->stock_out);
        $this->assertEquals(15, $firstItem->closing_stock);
        $this->assertEquals(12000, $firstItem->unit_cost);
        $this->assertEquals(180000, $firstItem->closing_asset_value);
    }
}
