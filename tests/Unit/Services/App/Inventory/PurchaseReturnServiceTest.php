<?php

namespace Tests\Unit\Services\App\Inventory;

use App\Enums\InventoryMovementType;
use App\Enums\PurchaseReturnStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryMovement;
use App\Models\Inventory\PurchaseReturn;
use App\Models\Inventory\Supplier;
use App\Models\Outlet;
use App\Models\Uom;
use App\Models\User;
use App\Services\App\Inventory\InventoryCostingService;
use App\Services\App\Inventory\PurchaseReturnService;
use App\Services\App\Master\ActivityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PurchaseReturnServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ActivityLogService $activityLogServiceMock;

    protected PurchaseReturnService $service;

    protected InventoryCostingService $costingService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activityLogServiceMock = Mockery::mock(ActivityLogService::class);
        $this->activityLogServiceMock->shouldReceive('log')->andReturnNull();

        $this->costingService = app(InventoryCostingService::class);

        $this->service = new PurchaseReturnService(
            $this->activityLogServiceMock,
            $this->costingService
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function setupBaseData(): array
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);

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
            'name' => 'Staff Warehouse',
            'email' => 'warehouse_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
        ]);

        $outlet = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Outlet Store',
            'is_active' => true,
        ]);

        $uomPcs = Uom::where('code', 'Pcs')->first() ?? Uom::first();

        $inventoryItem = InventoryItem::firstOrCreate([
            'business_id' => $business->id,
        ], [
            'name' => 'Beras Pandan Wangi',
            'sku' => 'SKU-BERAS',
            'item_type' => 'raw_material',
            'uom_id' => $uomPcs->id,
        ]);

        $supplier = Supplier::create([
            'business_id' => $business->id,
            'name' => 'Distributor Beras',
            'phone' => '0822222222',
            'status' => 'active',
        ]);

        // Berikan saldo awal stok 50 pcs @ 15,000
        $this->costingService->recordIncomingStock(
            business: $business,
            outlet: $outlet,
            item: $inventoryItem,
            qty: 50,
            unitCost: 15000,
            movementType: InventoryMovementType::InitialStock,
            reference: $supplier,
            description: 'Stok awal beras',
            user: $user
        );

        return [$user, $business, $outlet, $inventoryItem, $supplier, $uomPcs];
    }

    public function test_it_creates_purchase_return_and_deducts_inventory()
    {
        [$user, $business, $outlet, $item, $supplier, $uomPcs] = $this->setupBaseData();

        $returnData = [
            'outlet_id' => $outlet->id,
            'supplier_id' => $supplier->id,
            'return_date' => now()->format('Y-m-d'),
            'reason' => 'Beras bau apek dan kemasan sobek',
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'uom_id' => $uomPcs->id,
                    'return_purchase_qty' => 10,
                    'conversion_factor' => 1.0,
                    'unit_cost' => 15000,
                ],
            ],
        ];

        $return = $this->service->createReturn($returnData, $user);

        $this->assertInstanceOf(PurchaseReturn::class, $return);
        $this->assertEquals(PurchaseReturnStatus::Completed, $return->status);
        $this->assertEquals(150000, $return->total_return_amount);
        $this->assertCount(1, $return->items);

        $returnItem = $return->items->first();
        $this->assertEquals(10, $returnItem->return_purchase_qty);
        $this->assertEquals(10, $returnItem->return_inventory_qty);
        $this->assertEquals(150000, $returnItem->subtotal);

        // Stok persediaan berkurang dari 50 menjadi 40
        $balance = InventoryBalance::where('inventory_item_id', $item->id)->first();
        $this->assertEquals(40, $balance->current_stock);

        // Movement tercatat sebagai PurchaseReturn
        $movement = InventoryMovement::where('reference_type', PurchaseReturn::class)
            ->where('reference_id', $return->id)
            ->first();
        $this->assertNotNull($movement);
        $this->assertEquals(InventoryMovementType::PurchaseReturn, $movement->movement_type);
        $this->assertEquals(-10, $movement->qty_change);
    }

    public function test_it_voids_purchase_return_and_restores_inventory()
    {
        [$user, $business, $outlet, $item, $supplier, $uomPcs] = $this->setupBaseData();

        $returnData = [
            'outlet_id' => $outlet->id,
            'supplier_id' => $supplier->id,
            'return_date' => now()->format('Y-m-d'),
            'reason' => 'Barang cacat fisik',
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'uom_id' => $uomPcs->id,
                    'return_purchase_qty' => 10,
                    'conversion_factor' => 1.0,
                    'unit_cost' => 15000,
                ],
            ],
        ];

        $return = $this->service->createReturn($returnData, $user);

        $balanceAfterReturn = InventoryBalance::where('inventory_item_id', $item->id)->first();
        $this->assertEquals(40, $balanceAfterReturn->current_stock);

        // Void the return
        $voidedReturn = $this->service->voidReturn($return, $user, 'Salah retur, barang diganti langsung');

        $this->assertEquals(PurchaseReturnStatus::Voided, $voidedReturn->status);
        $this->assertStringContainsString('Salah retur', $voidedReturn->reason);

        // Stok persediaan kembali menjadi 50
        $balanceAfterVoid = InventoryBalance::where('inventory_item_id', $item->id)->first();
        $this->assertEquals(50, $balanceAfterVoid->current_stock);
    }
}
