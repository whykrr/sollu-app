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

    public function test_it_accurately_returns_multi_receipt_with_different_conversion_factors_without_negative_stock()
    {
        [$user, $business, $outlet, $item, $supplier, $uomPcs] = $this->setupBaseData();

        // Reset saldo ke 0 untuk pengujian isolasi penerimaan bertahap
        InventoryBalance::where('inventory_item_id', $item->id)->update(['current_stock' => 0]);

        $po = \App\Models\Inventory\PurchaseOrder::create([
            'business_id' => $business->id,
            'outlet_id' => $outlet->id,
            'supplier_id' => $supplier->id,
            'po_number' => 'PO-TEST-001',
            'status' => \App\Enums\PurchaseOrderStatus::Ordered,
            'order_date' => now()->format('Y-m-d'),
            'total_amount' => 100000,
            'created_by' => $user->id,
        ]);

        $poItem = $po->items()->create([
            'inventory_item_id' => $item->id,
            'uom_id' => $uomPcs->id,
            'qty_ordered' => 2,
            'qty_received' => 0,
            'purchase_price' => 50000,
            'conversion_factor' => 1,
            'converted_qty' => 0,
            'subtotal' => 100000,
        ]);

        // Penerimaan 1: 1 Dus @ konversi 11 Pcs = +11 Pcs
        $gr1 = \App\Models\Inventory\GoodsReceipt::create([
            'business_id' => $business->id,
            'outlet_id' => $outlet->id,
            'purchase_order_id' => $po->id,
            'supplier_id' => $supplier->id,
            'receipt_number' => 'GR-001',
            'delivery_order_number' => 'SJ-01',
            'received_at' => now()->format('Y-m-d'),
            'status' => 'completed',
            'received_by' => $user->id,
        ]);

        $grItem1 = $gr1->items()->create([
            'purchase_order_item_id' => $poItem->id,
            'inventory_item_id' => $item->id,
            'uom_id' => $uomPcs->id,
            'received_purchase_qty' => 1,
            'conversion_factor' => 11,
            'received_inventory_qty' => 11,
            'unit_cost' => 4545.45,
            'total_cost' => 50000,
        ]);

        $this->costingService->recordIncomingStock(
            business: $business,
            outlet: $outlet,
            item: $item,
            qty: 11,
            unitCost: 4545.45,
            movementType: InventoryMovementType::Purchase,
            reference: $gr1,
            description: 'Penerimaan #1',
            user: $user
        );

        // Penerimaan 2: 1 Dus @ konversi 24 Pcs = +24 Pcs
        $gr2 = \App\Models\Inventory\GoodsReceipt::create([
            'business_id' => $business->id,
            'outlet_id' => $outlet->id,
            'purchase_order_id' => $po->id,
            'supplier_id' => $supplier->id,
            'receipt_number' => 'GR-002',
            'delivery_order_number' => 'SJ-02',
            'received_at' => now()->format('Y-m-d'),
            'status' => 'completed',
            'received_by' => $user->id,
        ]);

        $grItem2 = $gr2->items()->create([
            'purchase_order_item_id' => $poItem->id,
            'inventory_item_id' => $item->id,
            'uom_id' => $uomPcs->id,
            'received_purchase_qty' => 1,
            'conversion_factor' => 24,
            'received_inventory_qty' => 24,
            'unit_cost' => 2083.33,
            'total_cost' => 50000,
        ]);

        $this->costingService->recordIncomingStock(
            business: $business,
            outlet: $outlet,
            item: $item,
            qty: 24,
            unitCost: 2083.33,
            movementType: InventoryMovementType::Purchase,
            reference: $gr2,
            description: 'Penerimaan #2',
            user: $user
        );

        // Total stok saat ini = 11 + 24 = 35 Pcs
        $balance = InventoryBalance::where('inventory_item_id', $item->id)->first();
        $this->assertEquals(35, $balance->current_stock);

        // Lakukan retur penuh untuk kedua surat jalan (1 Dus SJ-01 @ 11 pcs, 1 Dus SJ-02 @ 24 pcs)
        $returnData = [
            'purchase_order_id' => $po->id,
            'outlet_id' => $outlet->id,
            'supplier_id' => $supplier->id,
            'return_date' => now()->format('Y-m-d'),
            'reason' => 'Retur semua penerimaan karena rusak total',
            'items' => [
                [
                    'goods_receipt_item_id' => $grItem1->id,
                    'inventory_item_id' => $item->id,
                    'uom_id' => $uomPcs->id,
                    'return_purchase_qty' => 1,
                    'unit_cost' => 50000,
                ],
                [
                    'goods_receipt_item_id' => $grItem2->id,
                    'inventory_item_id' => $item->id,
                    'uom_id' => $uomPcs->id,
                    'return_purchase_qty' => 1,
                    'unit_cost' => 50000,
                ],
            ],
        ];

        $return = $this->service->createReturn($returnData, $user);

        $this->assertCount(2, $return->items);
        $this->assertEquals(11, $return->items[0]->return_inventory_qty);
        $this->assertEquals(24, $return->items[1]->return_inventory_qty);

        // Saldo akhir persediaan WAJIB tepat 0 (35 - 11 - 24 = 0), TIDAK BOLEH MINUS!
        $finalBalance = InventoryBalance::where('inventory_item_id', $item->id)->first();
        $this->assertEquals(0, $finalBalance->current_stock);
    }

    public function test_it_prevents_returning_more_than_remaining_returnable_qty_for_a_goods_receipt_item()
    {
        [$user, $business, $outlet, $item, $supplier, $uomPcs] = $this->setupBaseData();

        $gr = \App\Models\Inventory\GoodsReceipt::create([
            'business_id' => $business->id,
            'outlet_id' => $outlet->id,
            'supplier_id' => $supplier->id,
            'receipt_number' => 'GR-SINGLE-001',
            'received_at' => now()->format('Y-m-d'),
            'status' => 'completed',
            'received_by' => $user->id,
        ]);

        $grItem = $gr->items()->create([
            'inventory_item_id' => $item->id,
            'uom_id' => $uomPcs->id,
            'received_purchase_qty' => 2,
            'conversion_factor' => 10,
            'received_inventory_qty' => 20,
            'unit_cost' => 5000,
            'total_cost' => 100000,
        ]);

        // Coba retur 3 (melebihi received_purchase_qty = 2)
        $returnData = [
            'outlet_id' => $outlet->id,
            'supplier_id' => $supplier->id,
            'return_date' => now()->format('Y-m-d'),
            'reason' => 'Retur berlebih',
            'items' => [
                [
                    'goods_receipt_item_id' => $grItem->id,
                    'inventory_item_id' => $item->id,
                    'uom_id' => $uomPcs->id,
                    'return_purchase_qty' => 3,
                    'unit_cost' => 50000,
                ],
            ],
        ];

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('melebihi sisa penerimaan fisik surat jalan terkait');

        $this->service->createReturn($returnData, $user);
    }

    public function test_it_prevents_returning_items_from_voided_goods_receipt()
    {
        [$user, $business, $outlet, $item, $supplier, $uomPcs] = $this->setupBaseData();

        $gr = \App\Models\Inventory\GoodsReceipt::create([
            'business_id' => $business->id,
            'outlet_id' => $outlet->id,
            'supplier_id' => $supplier->id,
            'receipt_number' => 'GR-VOIDED-001',
            'received_at' => now()->format('Y-m-d'),
            'status' => \App\Enums\GoodsReceiptStatus::Voided,
            'received_by' => $user->id,
        ]);

        $grItem = $gr->items()->create([
            'inventory_item_id' => $item->id,
            'uom_id' => $uomPcs->id,
            'received_purchase_qty' => 5,
            'conversion_factor' => 1,
            'received_inventory_qty' => 5,
            'unit_cost' => 10000,
            'total_cost' => 50000,
        ]);

        $this->assertEquals(0, $grItem->remaining_returnable_qty);

        $returnData = [
            'outlet_id' => $outlet->id,
            'supplier_id' => $supplier->id,
            'return_date' => now()->format('Y-m-d'),
            'reason' => 'Retur dari SJ yang void',
            'items' => [
                [
                    'goods_receipt_item_id' => $grItem->id,
                    'inventory_item_id' => $item->id,
                    'uom_id' => $uomPcs->id,
                    'return_purchase_qty' => 1,
                    'unit_cost' => 10000,
                ],
            ],
        ];

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('sudah dibatalkan sehingga tidak dapat diretur');

        $this->service->createReturn($returnData, $user);
    }

    public function test_it_prevents_return_if_return_period_has_expired()
    {
        [$user, $business, $outlet, $item, $supplier, $uomPcs] = $this->setupBaseData();

        $supplier->update(['return_period_days' => 5]);

        $po = \App\Models\Inventory\PurchaseOrder::create([
            'business_id' => $business->id,
            'outlet_id' => $outlet->id,
            'supplier_id' => $supplier->id,
            'po_number' => 'PO-EXPIRED-01',
            'status' => \App\Enums\PurchaseOrderStatus::Ordered,
            'order_date' => now()->subDays(10)->format('Y-m-d'),
            'total_amount' => 50000,
            'created_by' => $user->id,
        ]);

        $poItem = $po->items()->create([
            'inventory_item_id' => $item->id,
            'uom_id' => $uomPcs->id,
            'qty_ordered' => 5,
            'qty_received' => 5,
            'purchase_price' => 10000,
            'subtotal' => 50000,
        ]);

        $gr = \App\Models\Inventory\GoodsReceipt::create([
            'business_id' => $business->id,
            'outlet_id' => $outlet->id,
            'purchase_order_id' => $po->id,
            'supplier_id' => $supplier->id,
            'receipt_number' => 'GR-EXPIRED-01',
            // Diterima 8 hari yang lalu, padahal batas retur supplier hanya 5 hari
            'received_at' => now()->subDays(8),
            'status' => 'completed',
            'received_by' => $user->id,
        ]);

        $grItem = $gr->items()->create([
            'purchase_order_item_id' => $poItem->id,
            'inventory_item_id' => $item->id,
            'uom_id' => $uomPcs->id,
            'received_purchase_qty' => 5,
            'conversion_factor' => 1,
            'received_inventory_qty' => 5,
            'unit_cost' => 10000,
            'total_cost' => 50000,
        ]);

        $returnData = [
            'purchase_order_id' => $po->id,
            'outlet_id' => $outlet->id,
            'supplier_id' => $supplier->id,
            'return_date' => now()->format('Y-m-d'),
            'reason' => 'Coba retur yang kedaluwarsa',
            'items' => [
                [
                    'goods_receipt_item_id' => $grItem->id,
                    'inventory_item_id' => $item->id,
                    'uom_id' => $uomPcs->id,
                    'return_purchase_qty' => 2,
                    'unit_cost' => 10000,
                ],
            ],
        ];

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('telah berakhir (maksimal 5 hari');

        $this->service->createReturn($returnData, $user);
    }

    public function test_it_allows_return_within_valid_return_period()
    {
        [$user, $business, $outlet, $item, $supplier, $uomPcs] = $this->setupBaseData();

        $supplier->update(['return_period_days' => 14]);

        $po = \App\Models\Inventory\PurchaseOrder::create([
            'business_id' => $business->id,
            'outlet_id' => $outlet->id,
            'supplier_id' => $supplier->id,
            'po_number' => 'PO-VALID-01',
            'status' => \App\Enums\PurchaseOrderStatus::Ordered,
            'order_date' => now()->subDays(3)->format('Y-m-d'),
            'total_amount' => 50000,
            'created_by' => $user->id,
        ]);

        $poItem = $po->items()->create([
            'inventory_item_id' => $item->id,
            'uom_id' => $uomPcs->id,
            'qty_ordered' => 5,
            'qty_received' => 5,
            'purchase_price' => 10000,
            'subtotal' => 50000,
        ]);

        $gr = \App\Models\Inventory\GoodsReceipt::create([
            'business_id' => $business->id,
            'outlet_id' => $outlet->id,
            'purchase_order_id' => $po->id,
            'supplier_id' => $supplier->id,
            'receipt_number' => 'GR-VALID-01',
            // Diterima 3 hari yang lalu (masih dalam batas 14 hari)
            'received_at' => now()->subDays(3),
            'status' => 'completed',
            'received_by' => $user->id,
        ]);

        $grItem = $gr->items()->create([
            'purchase_order_item_id' => $poItem->id,
            'inventory_item_id' => $item->id,
            'uom_id' => $uomPcs->id,
            'received_purchase_qty' => 5,
            'conversion_factor' => 1,
            'received_inventory_qty' => 5,
            'unit_cost' => 10000,
            'total_cost' => 50000,
        ]);

        $returnData = [
            'purchase_order_id' => $po->id,
            'outlet_id' => $outlet->id,
            'supplier_id' => $supplier->id,
            'return_date' => now()->format('Y-m-d'),
            'reason' => 'Retur masih dalam masa garansi supplier',
            'items' => [
                [
                    'goods_receipt_item_id' => $grItem->id,
                    'inventory_item_id' => $item->id,
                    'uom_id' => $uomPcs->id,
                    'return_purchase_qty' => 2,
                    'unit_cost' => 10000,
                ],
            ],
        ];

        $return = $this->service->createReturn($returnData, $user);

        $this->assertInstanceOf(PurchaseReturn::class, $return);
        $this->assertEquals(PurchaseReturnStatus::Completed, $return->status);
    }

    public function test_it_prevents_return_if_current_stock_is_insufficient()
    {
        [$user, $business, $outlet, $item, $supplier, $uomPcs] = $this->setupBaseData();

        // Stok saat ini dibuat 1 pcs
        InventoryBalance::where('inventory_item_id', $item->id)->update(['current_stock' => 1]);

        $returnData = [
            'outlet_id' => $outlet->id,
            'supplier_id' => $supplier->id,
            'return_date' => now()->format('Y-m-d'),
            'reason' => 'Retur melebihi sisa fisik toko',
            'items' => [
                [
                    'inventory_item_id' => $item->id,
                    'uom_id' => $uomPcs->id,
                    'return_purchase_qty' => 10,
                    'unit_cost' => 15000,
                ],
            ],
        ];

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('tidak mencukupi untuk diretur');

        $this->service->createReturn($returnData, $user);
    }
}
