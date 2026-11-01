<?php

namespace Tests\Feature\Inventory;

use App\Enums\AdjustmentReason;
use App\Enums\AdjustmentStatus;
use App\Enums\FeatureEnum;
use App\Enums\GoodsReceiptStatus;
use App\Enums\PlanEnum;
use App\Enums\PurchaseOrderStatus;
use App\Enums\StockTransferMode;
use App\Enums\StockTransferStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\Supplier;
use App\Models\Outlet;
use App\Models\OutletSetting;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Uom;
use App\Models\User;
use App\Services\App\Inventory\GoodsReceiptService;
use App\Services\App\Inventory\PurchaseOrderService;
use App\Services\App\Inventory\PurchaseReturnService;
use App\Services\App\Inventory\StockAdjustmentService;
use App\Services\App\Inventory\StockTransferService;
use Database\Seeders\DatabaseSeeder;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UniversalInventoryVoidTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

    protected Outlet $outletA;

    protected Outlet $outletB;

    protected InventoryItem $item;

    protected Supplier $supplier;

    protected Uom $uom;

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
                    FeatureEnum::PURCHASE_ORDER->value,
                    FeatureEnum::STOCK_ADJUSTMENTS->value,
                    FeatureEnum::STOCK_TRANSFERS->value,
                ],
            ]
        );

        $this->business = Business::create([
            'name' => 'Universal Void Business',
            'owner_name' => 'Owner',
            'email' => 'void_'.uniqid().'@test.com',
            'phone' => '08123456789',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
            'settings' => [
                'active_features' => [
                    FeatureEnum::INVENTORY_MANAGEMENT->value,
                    FeatureEnum::PURCHASE_ORDER->value,
                    FeatureEnum::STOCK_ADJUSTMENTS->value,
                    FeatureEnum::STOCK_TRANSFERS->value,
                ],
            ],
        ]);

        $proPlan = SubscriptionPlan::where('code', PlanEnum::PRO->value)->first();
        Subscription::create([
            'business_id' => $this->business->id,
            'plan_id' => $proPlan->id,
            'status' => SubscriptionStatus::Active,
            'billing_cycle' => 'monthly',
            'started_at' => now()->subDay(),
            'expired_at' => now()->addMonth(),
        ]);
        $this->business->clearMemoizedFeatures();

        $this->outletA = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet A (Normal)',
            'is_active' => true,
            'is_stock_frozen' => false,
        ]);

        $this->outletB = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet B (Toleran Minus)',
            'is_active' => true,
            'is_stock_frozen' => false,
        ]);

        OutletSetting::create([
            'outlet_id' => $this->outletB->id,
            'category' => 'sales',
            'key' => 'allow_negative_stock',
            'value' => [true],
        ]);

        $this->uom = Uom::where('code', 'pcs')->first() ?? Uom::first();

        $this->item = InventoryItem::create([
            'business_id' => $this->business->id,
            'name' => 'Bahan Uji Void',
            'uom_id' => $this->uom->id,
            'is_active' => true,
        ]);

        $this->supplier = Supplier::create([
            'business_id' => $this->business->id,
            'name' => 'Supplier Void Test',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
            'is_root_user' => false,
        ]);

        setPermissionsTeamId($this->business->id);
        $allPerms = Permission::all();
        $this->user->givePermissionTo($allPerms);
    }

    public function test_goods_receipt_void_fails_when_stock_insufficient_and_negative_stock_disallowed(): void
    {
        $poService = app(PurchaseOrderService::class);
        $grService = app(GoodsReceiptService::class);

        // 1. Create PO and receive 10 units at Outlet A (disallows negative stock)
        $po = $poService->createPO([
            'supplier_id' => $this->supplier->id,
            'outlet_id' => $this->outletA->id,
            'order_date' => now()->format('Y-m-d'),
            'status' => PurchaseOrderStatus::Ordered,
            'items' => [
                [
                    'inventory_item_id' => $this->item->id,
                    'qty_ordered' => 10,
                    'purchase_price' => 1000,
                    'uom_id' => $this->uom->id,
                    'conversion_factor' => 1,
                ],
            ],
        ], $this->user);

        $receipt = $grService->createReceipt($po, [
            'delivery_order_number' => 'DO-001',
            'received_at' => now()->format('Y-m-d'),
            'items' => [
                [
                    'purchase_order_item_id' => $po->items->first()->id,
                    'qty_received' => 10,
                    'received_purchase_qty' => 10,
                    'uom_id' => $this->uom->id,
                    'conversion_factor' => 1,
                ],
            ],
        ], $this->user);

        // Current balance in outlet A is 10. Simulate reduction to 2.
        InventoryBalance::where('inventory_item_id', $this->item->id)
            ->where('outlet_id', $this->outletA->id)
            ->update(['current_stock' => 2]);

        // Attempting to void the receipt should throw DomainException
        $this->expectException(DomainException::class);
        $this->expectExceptionMessage('kurang dari kuantitas pembatalan');

        $grService->voidReceipt($receipt, $this->user, 'Salah catat');
    }

    public function test_goods_receipt_void_succeeds_when_negative_stock_is_allowed(): void
    {
        $poService = app(PurchaseOrderService::class);
        $grService = app(GoodsReceiptService::class);

        // Receive 10 units at Outlet B (allows negative stock)
        $po = $poService->createPO([
            'supplier_id' => $this->supplier->id,
            'outlet_id' => $this->outletB->id,
            'order_date' => now()->format('Y-m-d'),
            'status' => PurchaseOrderStatus::Ordered,
            'items' => [
                [
                    'inventory_item_id' => $this->item->id,
                    'qty_ordered' => 10,
                    'purchase_price' => 1000,
                    'uom_id' => $this->uom->id,
                    'conversion_factor' => 1,
                ],
            ],
        ], $this->user);

        $receipt = $grService->createReceipt($po, [
            'delivery_order_number' => 'DO-002',
            'received_at' => now()->format('Y-m-d'),
            'items' => [
                [
                    'purchase_order_item_id' => $po->items->first()->id,
                    'qty_received' => 10,
                    'received_purchase_qty' => 10,
                    'uom_id' => $this->uom->id,
                    'conversion_factor' => 1,
                ],
            ],
        ], $this->user);

        // Simulate reduction to 2 (voiding 10 will leave -8)
        InventoryBalance::where('inventory_item_id', $this->item->id)
            ->where('outlet_id', $this->outletB->id)
            ->update(['current_stock' => 2]);

        $grService->voidReceipt($receipt, $this->user, 'Alasan void uji');

        $receipt->refresh();
        $this->assertEquals(GoodsReceiptStatus::Voided, $receipt->status);
        $this->assertNotNull($receipt->voided_at);
        $this->assertEquals($this->user->id, $receipt->voided_by);
        $this->assertEquals('Alasan void uji', $receipt->void_reason);

        // Check negative balance allowed
        $balance = InventoryBalance::where('inventory_item_id', $this->item->id)
            ->where('outlet_id', $this->outletB->id)
            ->first();
        $this->assertEquals(-8, (float) $balance->current_stock);
    }

    public function test_stock_adjustment_void_evaluates_negative_stock_and_stores_audit(): void
    {
        $adjService = app(StockAdjustmentService::class);

        // 1. Positive adjustment of +10 at Outlet A
        $adj = $adjService->create([
            'outlet_id' => $this->outletA->id,
            'reason' => AdjustmentReason::Correction->value,
            'notes' => 'Adjustment Uji',
            'is_draft' => false,
            'items' => [
                [
                    'inventory_item_id' => $this->item->id,
                    'qty_change' => 10,
                    'unit_cost' => 500,
                    'description' => 'Tambah 10',
                ],
            ],
        ], $this->user);

        // Simulate reduction to 2
        InventoryBalance::where('inventory_item_id', $this->item->id)
            ->where('outlet_id', $this->outletA->id)
            ->update(['current_stock' => 2]);

        // Outlet A has negative stock disallowed -> must throw DomainException
        try {
            $adjService->void($adj, $this->user, 'Batal adjustment');
            $this->fail('Harusnya gagal karena saldo tidak mencukupi');
        } catch (DomainException $e) {
            $this->assertStringContainsString('kurang dari kuantitas pembatalan', $e->getMessage());
        }

        // Allow negative stock on Outlet A
        OutletSetting::updateOrCreate(
            ['outlet_id' => $this->outletA->id, 'key' => 'allow_negative_stock'],
            ['category' => 'sales', 'value' => [true]]
        );
        $this->outletA->unsetRelation('settings');

        $adjService->void($adj, $this->user, 'Batal adjustment sukses');
        $adj->refresh();

        $this->assertEquals(AdjustmentStatus::Voided, $adj->status);
        $this->assertNotNull($adj->voided_at);
        $this->assertEquals($this->user->id, $adj->voided_by);
        $this->assertEquals('Batal adjustment sukses', $adj->void_reason);

        $balance = InventoryBalance::where('inventory_item_id', $this->item->id)
            ->where('outlet_id', $this->outletA->id)
            ->first();
        $this->assertEquals(-8, (float) $balance->current_stock);
    }

    public function test_stock_transfer_void_evaluates_destination_negative_stock_and_stores_audit(): void
    {
        $tfService = app(StockTransferService::class);

        // Initial balance: Outlet A = 20, Outlet B = 0
        InventoryBalance::updateOrCreate(
            ['business_id' => $this->business->id, 'inventory_item_id' => $this->item->id, 'outlet_id' => $this->outletA->id],
            ['current_stock' => 20]
        );

        // Direct transfer of 10 from A to A's sibling Outlet B (Outlet B allows negative stock)
        $transfer = $tfService->createTransfer([
            'from_outlet_id' => $this->outletA->id,
            'to_outlet_id' => $this->outletB->id,
            'transfer_mode' => StockTransferMode::DIRECT->value,
            'notes' => 'Transfer 10 unit',
            'items' => [
                [
                    'inventory_item_id' => $this->item->id,
                    'qty' => 10,
                ],
            ],
        ], $this->user);

        // Now Outlet A has 10, Outlet B has 10.
        // Simulate Outlet B consumes 8 (balance = 2).
        InventoryBalance::where('inventory_item_id', $this->item->id)
            ->where('outlet_id', $this->outletB->id)
            ->update(['current_stock' => 2]);

        // Outlet B disallows negative stock temporarily
        OutletSetting::updateOrCreate(
            ['outlet_id' => $this->outletB->id, 'key' => 'allow_negative_stock'],
            ['category' => 'sales', 'value' => [false]]
        );
        $this->outletB->unsetRelation('settings');

        try {
            $tfService->voidTransfer($transfer, ['void_reason' => 'Tolak void transfer'], $this->user);
            $this->fail('Harusnya gagal karena stok di Outlet B tidak cukup');
        } catch (DomainException $e) {
            $this->assertStringContainsString('kurang dari kuantitas pembatalan', $e->getMessage());
        }

        // Re-enable negative stock on Outlet B
        OutletSetting::updateOrCreate(
            ['outlet_id' => $this->outletB->id, 'key' => 'allow_negative_stock'],
            ['category' => 'sales', 'value' => [true]]
        );
        $this->outletB->unsetRelation('settings');

        $tfService->voidTransfer($transfer, ['void_reason' => 'Void transfer berhasil'], $this->user);
        $transfer->refresh();

        $this->assertEquals(StockTransferStatus::Void, $transfer->status);
        $this->assertNotNull($transfer->voided_at);
        $this->assertEquals($this->user->id, $transfer->voided_by);
        $this->assertEquals('Void transfer berhasil', $transfer->void_reason);

        // Destination balance is now -8
        $balanceB = InventoryBalance::where('inventory_item_id', $this->item->id)
            ->where('outlet_id', $this->outletB->id)
            ->first();
        $this->assertEquals(-8, (float) $balanceB->current_stock);

        // Source balance is restored back to 20
        $balanceA = InventoryBalance::where('inventory_item_id', $this->item->id)
            ->where('outlet_id', $this->outletA->id)
            ->first();
        $this->assertEquals(20, (float) $balanceA->current_stock);
    }

    public function test_purchase_order_void_records_audit_columns(): void
    {
        $poService = app(PurchaseOrderService::class);

        $po = $poService->directPurchase([
            'supplier_id' => $this->supplier->id,
            'outlet_id' => $this->outletB->id,
            'delivery_order_number' => 'DO-PO-VOID',
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'inventory_item_id' => $this->item->id,
                    'qty_ordered' => 5,
                    'purchase_price' => 1000,
                    'uom_id' => $this->uom->id,
                    'conversion_factor' => 1,
                ],
            ],
        ], $this->user);

        $poService->void($po, $this->user, 'Salah pesan ke vendor');
        $po->refresh();

        $this->assertEquals(PurchaseOrderStatus::Cancelled, $po->status);
        $this->assertNotNull($po->voided_at);
        $this->assertEquals($this->user->id, $po->voided_by);
        $this->assertEquals('Salah pesan ke vendor', $po->void_reason);
    }

    public function test_purchase_return_void_records_audit_columns(): void
    {
        $poService = app(PurchaseOrderService::class);
        $returnService = app(PurchaseReturnService::class);

        // 1. Create direct purchase (receipt created automatically)
        $po = $poService->directPurchase([
            'supplier_id' => $this->supplier->id,
            'outlet_id' => $this->outletB->id,
            'delivery_order_number' => 'DO-DIR-01',
            'order_date' => now()->format('Y-m-d'),
            'items' => [
                [
                    'inventory_item_id' => $this->item->id,
                    'qty_ordered' => 10,
                    'purchase_price' => 1000,
                    'uom_id' => $this->uom->id,
                    'conversion_factor' => 1,
                ],
            ],
        ], $this->user);

        $grItem = $po->goodsReceipts->first()->items->first();

        // 2. Return 4 items
        $return = $returnService->createReturn([
            'purchase_order_id' => $po->id,
            'outlet_id' => $this->outletB->id,
            'supplier_id' => $this->supplier->id,
            'return_date' => now()->format('Y-m-d'),
            'reason' => 'Barang cacat kemasan',
            'items' => [
                [
                    'goods_receipt_item_id' => $grItem->id,
                    'return_purchase_qty' => 4,
                    'unit_cost' => 1000,
                ],
            ],
        ], $this->user);

        // 3. Void the return
        $returnService->voidReturn($return, $this->user, 'Batal retur karena vendor memberi diskon');
        $return->refresh();

        $this->assertNotNull($return->voided_at);
        $this->assertEquals($this->user->id, $return->voided_by);
        $this->assertEquals('Batal retur karena vendor memberi diskon', $return->void_reason);
    }

    public function test_void_audit_columns_migration_rollback_and_migrate(): void
    {
        $migration = require database_path('migrations/2026_10_31_230000_add_void_audit_columns_to_inventory_tables.php');

        // Test down
        $migration->down();
        $this->assertFalse(Schema::hasColumn('stock_adjustments', 'voided_at'));
        $this->assertFalse(Schema::hasColumn('goods_receipts', 'voided_at'));
        $this->assertFalse(Schema::hasColumn('purchase_orders', 'voided_at'));
        $this->assertFalse(Schema::hasColumn('purchase_returns', 'voided_at'));

        // Test up
        $migration->up();
        $this->assertTrue(Schema::hasColumn('stock_adjustments', 'voided_at'));
        $this->assertTrue(Schema::hasColumn('goods_receipts', 'voided_at'));
        $this->assertTrue(Schema::hasColumn('purchase_orders', 'voided_at'));
        $this->assertTrue(Schema::hasColumn('purchase_returns', 'voided_at'));
    }
}
