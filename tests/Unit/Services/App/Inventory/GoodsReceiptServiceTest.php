<?php

namespace Tests\Unit\Services\App\Inventory;

use App\Enums\GoodsReceiptStatus;
use App\Enums\PurchaseOrderStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\GoodsReceipt;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\PurchaseOrder;
use App\Models\Inventory\Supplier;
use App\Models\Outlet;
use App\Models\Uom;
use App\Models\User;
use App\Services\App\Inventory\GoodsReceiptService;
use App\Services\App\Inventory\InventoryCostingService;
use App\Services\App\Master\ActivityLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class GoodsReceiptServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ActivityLogService $activityLogServiceMock;

    protected GoodsReceiptService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->activityLogServiceMock = Mockery::mock(ActivityLogService::class);
        $this->activityLogServiceMock->shouldReceive('log')->andReturnNull();

        $this->service = new GoodsReceiptService(
            $this->activityLogServiceMock,
            app(InventoryCostingService::class)
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
            'name' => 'Staff Receiver',
            'email' => 'receiver_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
        ]);

        $outlet = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Outlet Store',
            'is_active' => true,
        ]);

        $uomPcs = Uom::where('code', 'Pcs')->first() ?? Uom::first();
        $uomBox = Uom::where('code', 'Box')->first() ?? Uom::first();

        $inventoryItem = InventoryItem::firstOrCreate([
            'business_id' => $business->id,
        ], [
            'name' => 'Kopi Bubuk',
            'sku' => 'SKU-KOPI',
            'item_type' => 'raw_material',
            'uom_id' => $uomPcs->id,
        ]);

        $supplier = Supplier::create([
            'business_id' => $business->id,
            'name' => 'PT Supplier Kopi',
            'phone' => '0811111111',
            'status' => 'active',
        ]);

        return [$user, $business, $outlet, $inventoryItem, $supplier, $uomBox, $uomPcs];
    }

    public function test_it_creates_goods_receipt_for_full_delivery()
    {
        [$user, $business, $outlet, $item, $supplier] = $this->setupBaseData();

        $po = PurchaseOrder::create([
            'business_id' => $business->id,
            'outlet_id' => $outlet->id,
            'supplier_id' => $supplier->id,
            'po_number' => 'PO-202609-001',
            'order_date' => now()->format('Y-m-d'),
            'status' => PurchaseOrderStatus::Ordered,
            'total_amount' => 100000,
            'created_by' => $user->id,
        ]);

        $poItem = $po->items()->create([
            'inventory_item_id' => $item->id,
            'uom_id' => $item->uom_id,
            'qty_ordered' => 10,
            'purchase_price' => 10000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'subtotal' => 100000,
        ]);

        $receiptData = [
            'delivery_order_number' => 'DO-9988',
            'received_at' => now()->format('Y-m-d H:i:s'),
            'notes' => 'Penerimaan utuh 10 pcs',
            'items' => [
                [
                    'purchase_order_item_id' => $poItem->id,
                    'qty_received' => 10,
                    'conversion_factor' => 1.0,
                ],
            ],
        ];

        $receipt = $this->service->createReceipt($po, $receiptData, $user);

        $this->assertInstanceOf(GoodsReceipt::class, $receipt);
        $this->assertEquals(GoodsReceiptStatus::Completed, $receipt->status);
        $this->assertEquals('DO-9988', $receipt->delivery_order_number);
        $this->assertCount(1, $receipt->items);

        $receiptItem = $receipt->items->first();
        $this->assertEquals(10, $receiptItem->received_purchase_qty);
        $this->assertEquals(10, $receiptItem->received_inventory_qty);
        $this->assertEquals(10000, $receiptItem->unit_cost);
        $this->assertEquals(100000, $receiptItem->total_cost);

        // PO status should update to Received
        $po->refresh();
        $this->assertEquals(PurchaseOrderStatus::Received, $po->status);

        // Inventory balance checked
        $balance = InventoryBalance::where('inventory_item_id', $item->id)->first();
        $this->assertEquals(10, $balance->current_stock);
    }

    public function test_it_supports_partial_receiving_and_updates_po_status()
    {
        [$user, $business, $outlet, $item, $supplier] = $this->setupBaseData();

        $po = PurchaseOrder::create([
            'business_id' => $business->id,
            'outlet_id' => $outlet->id,
            'supplier_id' => $supplier->id,
            'po_number' => 'PO-202609-002',
            'order_date' => now()->format('Y-m-d'),
            'status' => PurchaseOrderStatus::Ordered,
            'total_amount' => 200000,
            'created_by' => $user->id,
        ]);

        $poItem = $po->items()->create([
            'inventory_item_id' => $item->id,
            'uom_id' => $item->uom_id,
            'qty_ordered' => 20,
            'purchase_price' => 10000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'subtotal' => 200000,
        ]);

        // First delivery: only 8 pcs
        $firstReceipt = $this->service->createReceipt($po, [
            'delivery_order_number' => 'DO-STAGE-1',
            'items' => [
                [
                    'purchase_order_item_id' => $poItem->id,
                    'qty_received' => 8,
                    'conversion_factor' => 1.0,
                ],
            ],
        ], $user);

        $po->refresh();
        $this->assertEquals(PurchaseOrderStatus::PartialReceived, $po->status);
        $this->assertEquals(8, $po->items->first()->qty_received);
        $this->assertEquals(12, $po->items->first()->outstanding_qty);

        // Second delivery: remaining 12 pcs
        $secondReceipt = $this->service->createReceipt($po, [
            'delivery_order_number' => 'DO-STAGE-2',
            'items' => [
                [
                    'purchase_order_item_id' => $poItem->id,
                    'qty_received' => 12,
                    'conversion_factor' => 1.0,
                ],
            ],
        ], $user);

        $po->refresh();
        $this->assertEquals(PurchaseOrderStatus::Received, $po->status);
        $this->assertEquals(20, $po->items->first()->qty_received);
        $this->assertEquals(0, $po->items->first()->outstanding_qty);

        $balance = InventoryBalance::where('inventory_item_id', $item->id)->first();
        $this->assertEquals(20, $balance->current_stock);
    }

    public function test_it_calculates_dynamic_uom_conversion_and_unit_cost()
    {
        [$user, $business, $outlet, $item, $supplier, $uomBox] = $this->setupBaseData();

        // Order 2 Boxes at 50,000 / Box with 5,000 discount. Each Box contains 10 Pcs (stock unit).
        $po = PurchaseOrder::create([
            'business_id' => $business->id,
            'outlet_id' => $outlet->id,
            'supplier_id' => $supplier->id,
            'po_number' => 'PO-202609-003',
            'order_date' => now()->format('Y-m-d'),
            'status' => PurchaseOrderStatus::Ordered,
            'total_amount' => 95000,
            'created_by' => $user->id,
        ]);

        $poItem = $po->items()->create([
            'inventory_item_id' => $item->id,
            'uom_id' => $uomBox->id,
            'qty_ordered' => 2,
            'purchase_price' => 50000,
            'discount_amount' => 5000,
            'tax_amount' => 0,
            'subtotal' => 95000,
        ]);

        $receipt = $this->service->createReceipt($po, [
            'delivery_order_number' => 'DO-BOX-01',
            'items' => [
                [
                    'purchase_order_item_id' => $poItem->id,
                    'qty_received' => 2,
                    'conversion_factor' => 10.0, // 1 Box = 10 Pcs
                ],
            ],
        ], $user);

        $receiptItem = $receipt->items->first();
        $this->assertEquals(2, $receiptItem->received_purchase_qty);
        $this->assertEquals(10.0, $receiptItem->conversion_factor);
        $this->assertEquals(20, $receiptItem->received_inventory_qty); // 2 * 10 = 20 Pcs
        $this->assertEquals(95000, $receiptItem->total_cost);
        $this->assertEquals(4750, $receiptItem->unit_cost); // 95000 / 20 = 4750 / Pcs

        $balance = InventoryBalance::where('inventory_item_id', $item->id)->first();
        $this->assertEquals(20, $balance->current_stock);
    }

    public function test_it_voids_goods_receipt_and_reverses_stock()
    {
        [$user, $business, $outlet, $item, $supplier] = $this->setupBaseData();

        $po = PurchaseOrder::create([
            'business_id' => $business->id,
            'outlet_id' => $outlet->id,
            'supplier_id' => $supplier->id,
            'po_number' => 'PO-202609-004',
            'order_date' => now()->format('Y-m-d'),
            'status' => PurchaseOrderStatus::Ordered,
            'total_amount' => 50000,
            'created_by' => $user->id,
        ]);

        $poItem = $po->items()->create([
            'inventory_item_id' => $item->id,
            'uom_id' => $item->uom_id,
            'qty_ordered' => 5,
            'purchase_price' => 10000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'subtotal' => 50000,
        ]);

        $receipt = $this->service->createReceipt($po, [
            'delivery_order_number' => 'DO-VOID-01',
            'items' => [
                [
                    'purchase_order_item_id' => $poItem->id,
                    'qty_received' => 5,
                    'conversion_factor' => 1.0,
                ],
            ],
        ], $user);

        $this->assertEquals(PurchaseOrderStatus::Received, $po->refresh()->status);

        // Void the receipt
        $voidedReceipt = $this->service->voidReceipt($receipt, $user, 'Salah input barang');

        $this->assertEquals(GoodsReceiptStatus::Voided, $voidedReceipt->status);
        $this->assertStringContainsString('Salah input barang', $voidedReceipt->notes);

        // PO status should revert to Ordered
        $po->refresh();
        $this->assertEquals(PurchaseOrderStatus::Ordered, $po->status);
        $this->assertEquals(0, $po->items->first()->qty_received);

        // Inventory stock should be back to 0
        $balance = InventoryBalance::where('inventory_item_id', $item->id)->first();
        $this->assertEquals(0, $balance->current_stock);

        // FIFO layer should be removed
        $this->assertDatabaseMissing('inventory_cost_layers', [
            'reference_id' => $receipt->id,
        ]);
    }

    public function test_it_cannot_receive_draft_or_cancelled_po()
    {
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        [$user, $business, $outlet, $item, $supplier] = $this->setupBaseData();

        $po = PurchaseOrder::create([
            'business_id' => $business->id,
            'outlet_id' => $outlet->id,
            'po_number' => 'PO-DRAFT-01',
            'order_date' => now()->format('Y-m-d'),
            'status' => PurchaseOrderStatus::Draft,
            'total_amount' => 10000,
            'created_by' => $user->id,
        ]);

        $this->service->createReceipt($po, ['items' => []], $user);
    }

    public function test_it_prevents_voiding_goods_receipt_if_stock_is_insufficient()
    {
        [$user, $business, $outlet, $item, $supplier] = $this->setupBaseData();

        $po = PurchaseOrder::create([
            'business_id' => $business->id,
            'outlet_id' => $outlet->id,
            'supplier_id' => $supplier->id,
            'po_number' => 'PO-VOID-GUARD-01',
            'order_date' => now()->format('Y-m-d'),
            'status' => PurchaseOrderStatus::Ordered,
            'total_amount' => 100000,
            'created_by' => $user->id,
        ]);

        $poItem = $po->items()->create([
            'inventory_item_id' => $item->id,
            'uom_id' => $item->uom_id,
            'qty_ordered' => 10,
            'purchase_price' => 10000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'subtotal' => 100000,
        ]);

        $receipt = $this->service->createReceipt($po, [
            'delivery_order_number' => 'DO-GUARD-01',
            'items' => [
                [
                    'purchase_order_item_id' => $poItem->id,
                    'qty_received' => 10,
                    'conversion_factor' => 1.0,
                ],
            ],
        ], $user);

        // Simulasi barang terjual di POS sehingga stok berkurang menjadi 3
        InventoryBalance::where('inventory_item_id', $item->id)->update(['current_stock' => 3]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('kurang dari jumlah penerimaan');

        $this->service->voidReceipt($receipt, $user, 'Salah catat');
    }

    public function test_it_prevents_voiding_goods_receipt_if_active_return_exists()
    {
        [$user, $business, $outlet, $item, $supplier] = $this->setupBaseData();

        $po = PurchaseOrder::create([
            'business_id' => $business->id,
            'outlet_id' => $outlet->id,
            'supplier_id' => $supplier->id,
            'po_number' => 'PO-VOID-GUARD-02',
            'order_date' => now()->format('Y-m-d'),
            'status' => PurchaseOrderStatus::Ordered,
            'total_amount' => 100000,
            'created_by' => $user->id,
        ]);

        $poItem = $po->items()->create([
            'inventory_item_id' => $item->id,
            'uom_id' => $item->uom_id,
            'qty_ordered' => 10,
            'purchase_price' => 10000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'subtotal' => 100000,
        ]);

        $receipt = $this->service->createReceipt($po, [
            'delivery_order_number' => 'DO-GUARD-02',
            'items' => [
                [
                    'purchase_order_item_id' => $poItem->id,
                    'qty_received' => 10,
                    'conversion_factor' => 1.0,
                ],
            ],
        ], $user);

        $grItem = $receipt->items->first();

        // Buat dokumen retur aktif
        $pr = \App\Models\Inventory\PurchaseReturn::create([
            'business_id' => $business->id,
            'outlet_id' => $outlet->id,
            'supplier_id' => $supplier->id,
            'return_number' => 'PR-202609-001',
            'return_date' => now()->format('Y-m-d'),
            'status' => \App\Enums\PurchaseReturnStatus::Completed,
            'created_by' => $user->id,
        ]);

        $pr->items()->create([
            'inventory_item_id' => $item->id,
            'goods_receipt_item_id' => $grItem->id,
            'return_purchase_qty' => 2,
            'conversion_factor' => 1,
            'return_inventory_qty' => 2,
            'unit_cost' => 10000,
            'subtotal' => 20000,
        ]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('memiliki riwayat retur aktif');

        $this->service->voidReceipt($receipt, $user, 'Coba void yang sudah diretur');
    }
}
