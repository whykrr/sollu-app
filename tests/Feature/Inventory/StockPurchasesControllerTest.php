<?php

namespace Tests\Feature\Inventory;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\FeatureEnum;
use App\Enums\GoodsReceiptStatus;
use App\Enums\PermissionEnum;
use App\Enums\PurchaseOrderStatus;
use App\Enums\PurchaseReturnStatus;
use App\Jobs\Inventory\ExportPurchaseOrderJob;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\GoodsReceipt;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\PurchaseOrder;
use App\Models\Inventory\PurchaseReturn;
use App\Models\Inventory\Supplier;
use App\Models\Outlet;
use App\Models\Uom;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class StockPurchasesControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

    protected Outlet $outlet;

    protected InventoryItem $item;

    protected Supplier $supplier;

    protected Uom $uomPcs;

    protected Uom $uomBox;

    protected string $appDomain;

    protected function setUp(): void
    {
        parent::setUp();

        config(['inertia.testing.page_paths' => [
            resource_path('js/Pages'),
            resource_path('js/Pages/App'),
        ]]);

        $this->seed(DatabaseSeeder::class);
        $this->appDomain = config('domain.app', 'app.sollu.test');

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            [
                'name' => 'Retail',
                'sort_order' => 1,
                'is_visible' => true,
                'features' => [
                    FeatureEnum::INVENTORY_MANAGEMENT->value,
                    FeatureEnum::PURCHASE_ORDERS->value,
                ],
            ]
        );

        $this->business = Business::create([
            'name' => 'Purchasing Test Business',
            'owner_name' => 'Owner',
            'email' => 'purchasing_'.uniqid().'@test.com',
            'phone' => '08123456789',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
            'settings' => [
                'active_features' => [
                    FeatureEnum::INVENTORY_MANAGEMENT->value,
                    FeatureEnum::PURCHASE_ORDERS->value,
                ],
            ],
        ]);

        $proPlan = \App\Models\SubscriptionPlan::where('code', \App\Enums\PlanEnum::PRO->value)->first();
        \App\Models\Subscription::create([
            'business_id' => $this->business->id,
            'plan_id' => $proPlan->id,
            'status' => \App\Enums\SubscriptionStatus::Active,
            'billing_cycle' => 'monthly',
            'started_at' => now()->subDay(),
            'expired_at' => now()->addMonth(),
        ]);
        $this->business->clearMemoizedFeatures();

        $this->outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Store',
            'is_active' => true,
        ]);

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Purchasing Manager',
            'email' => 'purchasing_mgr_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->user->outlets()->attach($this->outlet->id);

        setPermissionsTeamId($this->business->id);

        $permissions = [
            PermissionEnum::PURCHASE_ORDER_VIEW->value,
            PermissionEnum::PURCHASE_ORDER_CREATE->value,
            PermissionEnum::PURCHASE_ORDER_UPDATE->value,
            PermissionEnum::PURCHASE_ORDER_CANCEL->value,
            PermissionEnum::PURCHASE_ORDER_RECEIVE->value,
            PermissionEnum::PURCHASE_ORDER_VOID->value,
            PermissionEnum::PURCHASE_ORDER_RETURN->value,
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'business']);
            $this->user->givePermissionTo($perm);
        }

        $this->uomPcs = Uom::where('code', 'Pcs')->first() ?? Uom::first();
        $this->uomBox = Uom::where('code', 'Box')->first() ?? Uom::first();

        $this->item = InventoryItem::firstOrCreate([
            'business_id' => $this->business->id,
        ], [
            'name' => 'Biji Kopi Arabika',
            'sku' => 'SKU-KOPI-01',
            'item_type' => 'raw_material',
            'uom_id' => $this->uomPcs->id,
            'track_inventory' => true,
            'is_active' => true,
        ]);

        $this->supplier = Supplier::create([
            'business_id' => $this->business->id,
            'name' => 'Pemasok Kopi Nusantara',
            'phone' => '081233334444',
            'status' => 'active',
        ]);
    }

    public function test_user_can_view_purchases_index_with_date_preset(): void
    {
        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/inventories/purchases?preset=this_month");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Inventory/Purchase/Index')
            ->has('purchases.data')
            ->has('suppliers')
            ->has('outlets')
            ->has('params')
        );
    }

    public function test_user_can_create_draft_purchase_order_with_line_discounts(): void
    {
        $payload = [
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
            'reference_number' => 'INV-SUPP-999',
            'order_date' => now()->format('Y-m-d'),
            'notes' => 'Harap dikirim pagi hari',
            'items' => [
                [
                    'inventory_item_id' => $this->item->id,
                    'uom_id' => $this->uomBox->id,
                    'qty_ordered' => 5,
                    'purchase_price' => 50000,
                    'discount_amount' => 10000,
                    'tax_amount' => 5000,
                    'conversion_factor' => 10.0,
                ],
            ],
        ];

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/inventories/purchases", $payload);

        $response->assertRedirect();
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::CREATE_SUCCESS);

        $this->assertDatabaseHas('purchase_orders', [
            'business_id' => $this->business->id,
            'reference_number' => 'INV-SUPP-999',
            'status' => PurchaseOrderStatus::Draft->value,
            'total_amount' => 245000, // (5 * 50,000) - 10,000 + 5,000
        ]);
    }

    public function test_user_can_create_direct_purchase_with_instant_goods_receipt(): void
    {
        $payload = [
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
            'reference_number' => 'DIRECT-REF-01',
            'delivery_order_number' => 'SJ-LANGSUNG-01',
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'inventory_item_id' => $this->item->id,
                    'uom_id' => $this->uomPcs->id,
                    'qty_ordered' => 10,
                    'purchase_price' => 15000,
                    'discount_amount' => 5000,
                    'tax_amount' => 0,
                    'conversion_factor' => 1.0,
                ],
            ],
        ];

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/inventories/purchases/direct", $payload);

        $response->assertRedirect();
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value);

        $po = PurchaseOrder::where('business_id', $this->business->id)
            ->where('reference_number', 'DIRECT-REF-01')
            ->first();

        $this->assertNotNull($po);
        $this->assertEquals(PurchaseOrderStatus::Received, $po->status);
        $this->assertCount(1, $po->goodsReceipts);

        $balance = InventoryBalance::where('inventory_item_id', $this->item->id)->first();
        $this->assertEquals(10, $balance->current_stock);
    }

    public function test_user_can_receive_items_via_goods_receipt(): void
    {
        $po = PurchaseOrder::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
            'po_number' => 'PO-RECEIVE-01',
            'order_date' => now()->format('Y-m-d'),
            'status' => PurchaseOrderStatus::Ordered,
            'total_amount' => 100000,
            'created_by' => $this->user->id,
        ]);

        $poItem = $po->items()->create([
            'inventory_item_id' => $this->item->id,
            'uom_id' => $this->uomBox->id,
            'qty_ordered' => 2,
            'purchase_price' => 50000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'subtotal' => 100000,
        ]);

        $receivePayload = [
            'delivery_order_number' => 'DO-RECEIVE-99',
            'items' => [
                [
                    'purchase_order_item_id' => $poItem->id,
                    'qty_received' => 2,
                    'conversion_factor' => 12.0, // 1 Box = 12 Pcs
                ],
            ],
        ];

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/inventories/purchases/{$po->id}/receive", $receivePayload);

        $response->assertRedirect();
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value);

        $po->refresh();
        $this->assertEquals(PurchaseOrderStatus::Received, $po->status);

        $receipt = GoodsReceipt::where('purchase_order_id', $po->id)->first();
        $this->assertNotNull($receipt);
        $this->assertEquals('DO-RECEIVE-99', $receipt->delivery_order_number);
        $this->assertEquals(24, $receipt->items->first()->received_inventory_qty);

        // Check stock
        $balance = InventoryBalance::where('inventory_item_id', $this->item->id)->first();
        $this->assertEquals(24, $balance->current_stock);
    }

    public function test_user_can_void_goods_receipt(): void
    {
        $po = PurchaseOrder::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
            'po_number' => 'PO-VOID-01',
            'order_date' => now()->format('Y-m-d'),
            'status' => PurchaseOrderStatus::Ordered,
            'total_amount' => 50000,
            'created_by' => $this->user->id,
        ]);

        $poItem = $po->items()->create([
            'inventory_item_id' => $this->item->id,
            'uom_id' => $this->uomPcs->id,
            'qty_ordered' => 5,
            'purchase_price' => 10000,
            'subtotal' => 50000,
        ]);

        // Receive
        app(\App\Services\App\Inventory\GoodsReceiptService::class)->createReceipt($po, [
            'delivery_order_number' => 'DO-TO-VOID',
            'items' => [
                ['purchase_order_item_id' => $poItem->id, 'qty_received' => 5, 'conversion_factor' => 1.0],
            ],
        ], $this->user);

        $receipt = GoodsReceipt::where('purchase_order_id', $po->id)->firstOrFail();
        $this->assertEquals(PurchaseOrderStatus::Received, $po->refresh()->status);

        // Void receipt
        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/inventories/purchases/receipts/{$receipt->id}/void", [
                'reason' => 'Salah terima barang',
            ]);

        $response->assertRedirect();
        $this->assertEquals(GoodsReceiptStatus::Voided, $receipt->refresh()->status);
        $this->assertEquals(PurchaseOrderStatus::Ordered, $po->refresh()->status);

        $balance = InventoryBalance::where('inventory_item_id', $this->item->id)->first();
        $this->assertEquals(0, $balance->current_stock);
    }

    public function test_user_can_create_and_void_purchase_return(): void
    {
        // Berikan saldo awal 20 pcs
        app(\App\Services\App\Inventory\InventoryCostingService::class)->recordIncomingStock(
            business: $this->business,
            outlet: $this->outlet,
            item: $this->item,
            qty: 20,
            unitCost: 10000,
            movementType: \App\Enums\InventoryMovementType::InitialStock,
            reference: $this->supplier,
            description: 'Stok awal',
            user: $this->user
        );

        $payload = [
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
            'return_date' => now()->format('Y-m-d'),
            'reason' => 'Barang rusak saat transit',
            'items' => [
                [
                    'inventory_item_id' => $this->item->id,
                    'uom_id' => $this->uomPcs->id,
                    'return_purchase_qty' => 5,
                    'conversion_factor' => 1.0,
                    'unit_cost' => 10000,
                ],
            ],
        ];

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/inventories/purchases/returns", $payload);

        $response->assertRedirect();

        $return = PurchaseReturn::where('business_id', $this->business->id)->first();
        $this->assertNotNull($return);
        $this->assertEquals(PurchaseReturnStatus::Completed, $return->status);
        $this->assertEquals(50000, $return->total_return_amount);

        // Stok sisa 15
        $balance = InventoryBalance::where('inventory_item_id', $this->item->id)->first();
        $this->assertEquals(15, $balance->current_stock);

        // Void return
        $voidResponse = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/inventories/purchases/returns/{$return->id}/void", [
                'reason' => 'Batal retur',
            ]);

        $voidResponse->assertRedirect();
        $this->assertEquals(PurchaseReturnStatus::Voided, $return->refresh()->status);

        // Stok kembali 20
        $balance->refresh();
        $this->assertEquals(20, $balance->current_stock);
    }

    public function test_user_can_fetch_on_demand_po_detail_json(): void
    {
        $po = PurchaseOrder::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
            'po_number' => 'PO-DETAIL-01',
            'order_date' => now()->format('Y-m-d'),
            'status' => PurchaseOrderStatus::Draft,
            'total_amount' => 10000,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->getJson("http://{$this->appDomain}/inventories/purchases/{$po->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('id', $po->id);
        $response->assertJsonPath('po_number', 'PO-DETAIL-01');
    }

    public function test_user_can_download_purchase_order_pdf(): void
    {
        $po = PurchaseOrder::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
            'po_number' => 'PO-PDF-01',
            'order_date' => now()->format('Y-m-d'),
            'status' => PurchaseOrderStatus::Draft,
            'total_amount' => 50000,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/inventories/purchases/{$po->id}/pdf");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_user_can_download_goods_receipt_pdf(): void
    {
        $po = PurchaseOrder::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
            'po_number' => 'PO-GR-PDF-01',
            'order_date' => now()->format('Y-m-d'),
            'status' => PurchaseOrderStatus::Ordered,
            'total_amount' => 50000,
            'created_by' => $this->user->id,
        ]);

        $poItem = $po->items()->create([
            'inventory_item_id' => $this->item->id,
            'uom_id' => $this->uomPcs->id,
            'qty_ordered' => 5,
            'purchase_price' => 10000,
            'subtotal' => 50000,
        ]);

        $receipt = app(\App\Services\App\Inventory\GoodsReceiptService::class)->createReceipt($po, [
            'delivery_order_number' => 'SJ-PDF-01',
            'items' => [
                ['purchase_order_item_id' => $poItem->id, 'qty_received' => 5, 'conversion_factor' => 1.0],
            ],
        ], $this->user);

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/inventories/purchases/receipts/{$receipt->id}/pdf");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_user_can_download_purchase_return_pdf(): void
    {
        $return = PurchaseReturn::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'supplier_id' => $this->supplier->id,
            'return_number' => 'PR-PDF-01',
            'return_date' => now()->format('Y-m-d'),
            'reason' => 'Barang rusak',
            'status' => PurchaseReturnStatus::Completed,
            'total_return_amount' => 50000,
            'created_by' => $this->user->id,
        ]);

        $return->items()->create([
            'inventory_item_id' => $this->item->id,
            'uom_id' => $this->uomPcs->id,
            'return_purchase_qty' => 5,
            'conversion_factor' => 1.0,
            'return_inventory_qty' => 5,
            'unit_cost' => 10000,
            'subtotal' => 50000,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/inventories/purchases/returns/{$return->id}/pdf");

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_user_can_trigger_async_csv_export(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/inventories/purchases/export-csv?preset=this_month");

        $response->assertRedirect();
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value);

        Queue::assertPushed(ExportPurchaseOrderJob::class);
    }
}
