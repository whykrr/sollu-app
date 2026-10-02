<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Transaction;

use App\DTOs\Transaction\CreateB2bTransactionDTO;
use App\DTOs\Transaction\CreateTransactionItemDTO;
use App\Enums\InventoryMovementType;
use App\Enums\PaymentTermEnum;
use App\Enums\ProductTypeEnum;
use App\Enums\SalesChannelEnum;
use App\Enums\TransactionPaymentStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionTypeEnum;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryMovement;
use App\Models\Master\Customer;
use App\Models\Master\PaymentMethod;
use App\Models\Master\Product;
use App\Models\Master\ProductItem;
use App\Models\Outlet;
use App\Models\OutletSetting;
use App\Models\Promotion\Promotion;
use App\Models\Sales\Transaction;
use App\Models\User;
use App\Services\App\Inventory\InventoryCostingService;
use App\Services\App\Transaction\B2bTransactionService;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class B2bTransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;

    protected Outlet $outlet;

    protected User $user;

    protected Customer $customer;

    protected Product $product;

    protected ProductItem $productItem;

    protected InventoryItem $inventoryItem;

    protected PaymentMethod $paymentMethod;

    protected B2bTransactionService $b2bService;

    protected InventoryCostingService $costingService;

    protected function setUp(): void
    {
        parent::setUp();

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $this->business = Business::create([
            'name' => 'B2B Merchant',
            'owner_name' => 'Owner',
            'email' => 'b2b_'.uniqid().'@test.com',
            'phone' => '081200001',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $this->outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Main B2B Outlet',
        ]);

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Sales Officer',
            'email' => 'sales_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->customer = Customer::create([
            'business_id' => $this->business->id,
            'name' => 'PT Mitra Sejahtera',
            'phone' => '0812345678',
            'email' => 'mitra@sejahtera.com',
        ]);

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Kopi Arabika Premium 1kg',
            'product_type' => ProductTypeEnum::BASIC,
            'category_id' => null,
            'is_active' => true,
        ]);

        $this->productItem = ProductItem::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'name' => 'Kopi Arabika Premium 1kg',
            'sku' => 'KAP-1000',
            'item_type' => 'variant_sku',
            'track_inventory' => true,
            'is_active' => true,
        ]);

        $this->inventoryItem = InventoryItem::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'product_item_id' => $this->productItem->id,
            'is_active' => true,
        ]);

        $this->paymentMethod = PaymentMethod::create([
            'business_id' => $this->business->id,
            'name' => 'Bank Transfer BCA',
            'code' => 'bca_transfer',
            'type' => 'bank_transfer',
            'is_active' => true,
        ]);

        $this->costingService = app(InventoryCostingService::class);
        $this->b2bService = app(B2bTransactionService::class);
    }

    public function test_it_creates_draft_b2b_wholesale_transaction_with_customer_and_items(): void
    {
        $itemDto = new CreateTransactionItemDTO(
            productId: $this->product->id,
            qty: 10.0,
            price: 100000.0,
            productItemId: $this->productItem->id,
            inventoryItemId: $this->inventoryItem->id,
            discountAmount: 50000.0,
            notes: 'Diskon Grosir Baris'
        );

        $dto = new CreateB2bTransactionDTO(
            outletId: $this->outlet->id,
            channel: SalesChannelEnum::Wholesale,
            transactionDate: new DateTimeImmutable('2026-09-30 09:00:00'),
            paymentTerm: PaymentTermEnum::Credit,
            items: [$itemDto],
            customerId: $this->customer->id,
            dueDate: new DateTimeImmutable('2026-10-30 09:00:00'),
            notes: 'Faktur Pengadaan Grosir Batch 1',
            discountType: 'fixed',
            discountValue: 25000.0,
            shippingFee: 20000.0,
            serviceChargeAmount: 5000.0,
            paymentTermCode: 'net_30'
        );

        $transaction = $this->b2bService->createTransaction($dto, $this->user);

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals(TransactionTypeEnum::Invoice, $transaction->type);
        $this->assertEquals(SalesChannelEnum::Wholesale, $transaction->channel);
        $this->assertEquals(TransactionStatus::Draft, $transaction->status);
        $this->assertEquals(TransactionPaymentStatus::Draft, $transaction->payment_status);
        $this->assertEquals($this->customer->id, $transaction->customer_id);

        // Subtotal = (10 * 100.000) - 50.000 = 950.000
        $this->assertEquals(950000.0, (float) $transaction->subtotal);
        // Total = 950.000 - 25.000 + 20.000 + 5.000 = 950.000
        $this->assertEquals(950000.0, (float) $transaction->total);
        $this->assertEquals(950000.0, (float) $transaction->balance_due);

        // Verify Invoice Extension
        $this->assertNotNull($transaction->invoice);
        $this->assertStringStartsWith('INV/MAIN-B2B-OUTLET/202609/', $transaction->invoice->invoice_number);
        $this->assertEquals(PaymentTermEnum::Credit, $transaction->invoice->payment_term);
        $this->assertEquals('2026-10-30', $transaction->invoice->due_date->format('Y-m-d'));
        $this->assertEquals(TransactionStatus::Draft, $transaction->invoice->status);

        // Verify Items
        $this->assertCount(1, $transaction->items);
        $item = $transaction->items->first();
        $this->assertEquals($this->product->id, $item->product_id);
        $this->assertEquals($this->inventoryItem->id, $item->inventory_item_id);
        $this->assertEquals(10.0, (float) $item->qty);
        $this->assertEquals(100000.0, (float) $item->price);
        $this->assertEquals(50000.0, (float) $item->discount_amount);
        $this->assertEquals(950000.0, (float) $item->subtotal);
    }

    public function test_it_creates_draft_b2b_direct_transaction_without_customer(): void
    {
        $itemDto = new CreateTransactionItemDTO(
            productId: $this->product->id,
            qty: 2.0,
            price: 120000.0,
        );

        $dto = new CreateB2bTransactionDTO(
            outletId: $this->outlet->id,
            channel: SalesChannelEnum::Direct,
            transactionDate: new DateTimeImmutable('2026-09-30 11:00:00'),
            paymentTerm: PaymentTermEnum::Cash,
            items: [$itemDto],
            customerId: null,
            dueDate: null,
            notes: 'Penjualan Langsung Tunai'
        );

        $transaction = $this->b2bService->createTransaction($dto, $this->user);

        $this->assertNull($transaction->customer_id);
        $this->assertEquals(SalesChannelEnum::Direct, $transaction->channel);
        $this->assertEquals(240000.0, (float) $transaction->total);
        $this->assertEquals(PaymentTermEnum::Cash, $transaction->invoice->payment_term);
    }

    public function test_it_issues_invoice_and_deducts_stock_with_fifo_costing(): void
    {
        // 1. Initial Stock: 20 pcs @ 50.000
        $this->costingService->recordIncomingStock(
            $this->business,
            $this->outlet,
            $this->inventoryItem,
            20.0,
            50000.0,
            InventoryMovementType::InitialStock,
            null,
            'Stok Awal',
            $this->user
        );

        // 2. Create Draft Transaction 5 pcs
        $itemDto = new CreateTransactionItemDTO(
            productId: $this->product->id,
            qty: 5.0,
            price: 100000.0,
            productItemId: $this->productItem->id,
            inventoryItemId: $this->inventoryItem->id
        );

        $dto = new CreateB2bTransactionDTO(
            outletId: $this->outlet->id,
            channel: SalesChannelEnum::Wholesale,
            transactionDate: new DateTimeImmutable('2026-09-30 10:00:00'),
            paymentTerm: PaymentTermEnum::Credit,
            items: [$itemDto],
            customerId: $this->customer->id
        );

        $draft = $this->b2bService->createTransaction($dto, $this->user);

        // 3. Issue Invoice
        $issued = $this->b2bService->issueInvoice($draft, $this->user);

        $this->assertEquals(TransactionStatus::Unpaid, $issued->status);
        $this->assertEquals(TransactionPaymentStatus::Unpaid, $issued->payment_status);
        $this->assertEquals(TransactionStatus::Unpaid, $issued->invoice->status);

        // 4. Verify Stock Deduction
        $balance = InventoryBalance::where('outlet_id', $this->outlet->id)
            ->where('inventory_item_id', $this->inventoryItem->id)
            ->first();

        $this->assertEquals(15.0, (float) $balance->current_stock); // 20 - 5

        // Verify Item Snapshot HPP
        $item = $issued->items->first();
        $this->assertEquals(50000.0, (float) $item->unit_cogs);
        $this->assertEquals(250000.0, (float) $item->cogs_amount); // 5 * 50.000

        // Verify InventoryMovement
        $movement = InventoryMovement::where('reference_type', Transaction::class)
            ->where('reference_id', $issued->id)
            ->first();

        $this->assertNotNull($movement);
        $this->assertEquals(InventoryMovementType::Sale, $movement->movement_type);
        $this->assertEquals(-5.0, (float) $movement->qty_change);
    }

    public function test_it_issues_invoice_with_initial_cash_payment_and_marks_paid(): void
    {
        // Add Stock
        $this->costingService->recordIncomingStock(
            $this->business,
            $this->outlet,
            $this->inventoryItem,
            10.0,
            40000.0,
            InventoryMovementType::InitialStock,
            null,
            'Stok Awal',
            $this->user
        );

        $itemDto = new CreateTransactionItemDTO(
            productId: $this->product->id,
            qty: 2.0,
            price: 100000.0,
            inventoryItemId: $this->inventoryItem->id
        );

        $dto = new CreateB2bTransactionDTO(
            outletId: $this->outlet->id,
            channel: SalesChannelEnum::Wholesale,
            transactionDate: new DateTimeImmutable('2026-09-30 10:00:00'),
            paymentTerm: PaymentTermEnum::Cash,
            items: [$itemDto]
        );

        $draft = $this->b2bService->createTransaction($dto, $this->user);

        $issued = $this->b2bService->issueInvoice($draft, $this->user, [
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 250000.0,
            'change_amount' => 50000.0,
            'payment_reference' => 'CASH-PAID',
        ]);

        $this->assertEquals(TransactionStatus::Paid, $issued->status);
        $this->assertEquals(TransactionPaymentStatus::Paid, $issued->payment_status);
        $this->assertEquals(200000.0, (float) $issued->total_paid);
        $this->assertEquals(0.0, (float) $issued->balance_due);
        $this->assertCount(1, $issued->payments);
    }

    public function test_it_throws_exception_when_issuing_with_insufficient_stock(): void
    {
        // Current Stock = 0
        $itemDto = new CreateTransactionItemDTO(
            productId: $this->product->id,
            qty: 5.0,
            price: 100000.0,
            inventoryItemId: $this->inventoryItem->id
        );

        $dto = new CreateB2bTransactionDTO(
            outletId: $this->outlet->id,
            channel: SalesChannelEnum::Wholesale,
            transactionDate: new DateTimeImmutable('2026-09-30 10:00:00'),
            paymentTerm: PaymentTermEnum::Credit,
            items: [$itemDto]
        );

        $draft = $this->b2bService->createTransaction($dto, $this->user);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Stok');

        $this->b2bService->issueInvoice($draft, $this->user);
    }

    public function test_it_throws_exception_when_issuing_non_draft_transaction(): void
    {
        $transaction = new Transaction;
        $transaction->outlet_id = $this->outlet->id;
        $transaction->channel = SalesChannelEnum::Wholesale;
        $transaction->transaction_number = 'TRX/TEST/0001';
        $transaction->transaction_date = now();
        $transaction->status = TransactionStatus::Unpaid;
        $transaction->payment_status = TransactionPaymentStatus::Unpaid;
        $transaction->created_by = $this->user->id;
        $transaction->updated_by = $this->user->id;
        $transaction->save();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Hanya draf yang dapat diterbitkan');

        $this->b2bService->issueInvoice($transaction, $this->user);
    }

    public function test_it_updates_due_date_successfully(): void
    {
        $itemDto = new CreateTransactionItemDTO(
            productId: $this->product->id,
            qty: 1.0,
            price: 50000.0
        );

        $dto = new CreateB2bTransactionDTO(
            outletId: $this->outlet->id,
            channel: SalesChannelEnum::Wholesale,
            transactionDate: new DateTimeImmutable('2026-09-30 10:00:00'),
            paymentTerm: PaymentTermEnum::Credit,
            items: [$itemDto],
            dueDate: new DateTimeImmutable('2026-10-15 10:00:00')
        );

        $draft = $this->b2bService->createTransaction($dto, $this->user);

        $updated = $this->b2bService->updateDueDate($draft, '2026-11-15', $this->user, 'Permintaan pelanggan');

        $this->assertEquals('2026-11-15', $updated->invoice->due_date->format('Y-m-d'));
    }

    public function test_it_cancels_transaction_and_restores_fifo_stock(): void
    {
        // 1. Initial Stock: 10 pcs @ 50.000
        $this->costingService->recordIncomingStock(
            $this->business,
            $this->outlet,
            $this->inventoryItem,
            10.0,
            50000.0,
            InventoryMovementType::InitialStock,
            null,
            'Stok Awal',
            $this->user
        );

        // 2. Draft & Issue 4 pcs
        $itemDto = new CreateTransactionItemDTO(
            productId: $this->product->id,
            qty: 4.0,
            price: 100000.0,
            inventoryItemId: $this->inventoryItem->id
        );

        $dto = new CreateB2bTransactionDTO(
            outletId: $this->outlet->id,
            channel: SalesChannelEnum::Wholesale,
            transactionDate: new DateTimeImmutable('2026-09-30 10:00:00'),
            paymentTerm: PaymentTermEnum::Credit,
            items: [$itemDto]
        );

        $draft = $this->b2bService->createTransaction($dto, $this->user);
        $issued = $this->b2bService->issueInvoice($draft, $this->user);

        // Stock is now 6
        $this->assertEquals(6.0, (float) InventoryBalance::where('inventory_item_id', $this->inventoryItem->id)->value('current_stock'));

        // 3. Cancel Transaction
        $cancelled = $this->b2bService->cancelTransaction($issued, $this->user, 'Pesanan dibatalkan pembeli');

        $this->assertEquals(TransactionStatus::Cancel, $cancelled->status);
        $this->assertEquals(TransactionStatus::Cancel, $cancelled->invoice->status);

        // 4. Verify Stock Restored to 10
        $this->assertEquals(10.0, (float) InventoryBalance::where('inventory_item_id', $this->inventoryItem->id)->value('current_stock'));
    }

    public function test_it_evaluates_and_applies_auto_promotion_with_snapshot(): void
    {
        Promotion::create([
            'business_id' => $this->business->id,
            'name' => 'Diskon Promo Grosir 10%',
            'status' => 'active',
            'target_scope' => 'transaction',
            'discount_type' => 'percentage',
            'discount_value' => 10.0,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'applies_to_all_outlets' => true,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $itemDto = new CreateTransactionItemDTO(
            productId: $this->product->id,
            qty: 1.0,
            price: 100000.0,
        );

        $dto = new CreateB2bTransactionDTO(
            outletId: $this->outlet->id,
            channel: SalesChannelEnum::Wholesale,
            transactionDate: new DateTimeImmutable(now()->toDateTimeString()),
            paymentTerm: PaymentTermEnum::Cash,
            items: [$itemDto]
        );

        $transaction = $this->b2bService->createTransaction($dto, $this->user);

        $this->assertEquals('promo', $transaction->discount_type);
        $this->assertEquals(10000.0, (float) $transaction->discount_amount);
        $this->assertEquals(90000.0, (float) $transaction->total);
        $this->assertDatabaseHas('transaction_promos', [
            'transaction_id' => $transaction->id,
            'promo_name' => 'Diskon Promo Grosir 10%',
            'discount_amount' => 10000.0,
        ]);
    }

    public function test_it_allows_negative_stock_issuing_when_outlet_setting_enabled(): void
    {
        OutletSetting::create([
            'outlet_id' => $this->outlet->id,
            'category' => 'sales',
            'key' => 'allow_negative_stock_b2b',
            'value' => '1',
        ]);

        // Stock is 0
        $itemDto = new CreateTransactionItemDTO(
            productId: $this->product->id,
            qty: 5.0,
            price: 50000.0,
            inventoryItemId: $this->inventoryItem->id
        );

        $dto = new CreateB2bTransactionDTO(
            outletId: $this->outlet->id,
            channel: SalesChannelEnum::Wholesale,
            transactionDate: new DateTimeImmutable(now()->toDateTimeString()),
            paymentTerm: PaymentTermEnum::Credit,
            items: [$itemDto]
        );

        $draft = $this->b2bService->createTransaction($dto, $this->user);
        $issued = $this->b2bService->issueInvoice($draft, $this->user);

        $this->assertEquals(TransactionStatus::Unpaid, $issued->status);
    }

    public function test_it_evaluates_and_applies_item_level_promotion_with_item_snapshot(): void
    {
        $promo = Promotion::create([
            'business_id' => $this->business->id,
            'name' => 'Promo Diskon Produk Kopi 5rb',
            'status' => 'active',
            'target_scope' => 'product',
            'discount_type' => 'fixed',
            'discount_value' => 5000.0,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'applies_to_all_outlets' => true,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);
        $promo->products()->attach($this->product->id);

        $itemDto = new CreateTransactionItemDTO(
            productId: $this->product->id,
            qty: 2.0,
            price: 50000.0,
            productItemId: $this->productItem->id,
            inventoryItemId: $this->inventoryItem->id
        );

        $dto = new CreateB2bTransactionDTO(
            outletId: $this->outlet->id,
            channel: SalesChannelEnum::Wholesale,
            transactionDate: new DateTimeImmutable(now()->toDateTimeString()),
            paymentTerm: PaymentTermEnum::Cash,
            items: [$itemDto]
        );

        $transaction = $this->b2bService->createTransaction($dto, $this->user);

        // Subtotal kotor: 2 * 50.000 = 100.000
        // Item promo: 2 * 5.000 = 10.000
        // Item subtotal = 90.000
        // Header subtotal = 90.000, Header discount = 0, Total = 90.000
        $this->assertEquals(90000.0, (float) $transaction->subtotal);
        $this->assertEquals(0.0, (float) $transaction->discount_amount);
        $this->assertEquals(90000.0, (float) $transaction->total);

        $item = $transaction->items->first();
        $this->assertEquals(10000.0, (float) $item->discount_amount);
        $this->assertEquals('Promo Diskon Produk Kopi 5rb', $item->promo_name);
        $this->assertEquals(90000.0, (float) $item->subtotal);

        $this->assertDatabaseHas('transaction_promos', [
            'transaction_id' => $transaction->id,
            'transaction_item_id' => $item->id,
            'promo_name' => 'Promo Diskon Produk Kopi 5rb',
            'target_scope' => 'product',
            'discount_amount' => 10000.0,
        ]);

        $this->assertCount(1, $transaction->itemPromos);
        $this->assertCount(0, $transaction->transactionPromos);
        $this->assertEquals('Promo Diskon Produk Kopi 5rb', $item->appliedPromo->promo_name);
    }

    public function test_it_evaluates_mixed_item_and_transaction_promotions_without_double_deduction(): void
    {
        // 1. Promo Item: Fixed Rp 5.000 per product item
        $itemPromo = Promotion::create([
            'business_id' => $this->business->id,
            'name' => 'Promo Item Rp 5.000',
            'status' => 'active',
            'target_scope' => 'product',
            'discount_type' => 'fixed',
            'discount_value' => 5000.0,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'applies_to_all_outlets' => true,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);
        $itemPromo->products()->attach($this->product->id);

        // 2. Promo Header: Percentage 10% on transaction
        Promotion::create([
            'business_id' => $this->business->id,
            'name' => 'Promo Header 10%',
            'status' => 'active',
            'target_scope' => 'transaction',
            'discount_type' => 'percentage',
            'discount_value' => 10.0,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'applies_to_all_outlets' => true,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $itemDto = new CreateTransactionItemDTO(
            productId: $this->product->id,
            qty: 2.0,
            price: 50000.0,
            productItemId: $this->productItem->id,
            inventoryItemId: $this->inventoryItem->id
        );

        $dto = new CreateB2bTransactionDTO(
            outletId: $this->outlet->id,
            channel: SalesChannelEnum::Wholesale,
            transactionDate: new DateTimeImmutable(now()->toDateTimeString()),
            paymentTerm: PaymentTermEnum::Cash,
            items: [$itemDto]
        );

        $transaction = $this->b2bService->createTransaction($dto, $this->user);

        // Item Gross: 2 * 50.000 = 100.000
        // Item Promo: 2 * 5.000 = 10.000
        // Net Item Subtotal: 90.000 -> Header Subtotal = 90.000
        // Transaction Promo: 10% of matchingSubtotal (100.000) = 10.000
        // Grand Total: 90.000 - 10.000 = 80.000 (No double counting on item discount!)
        $this->assertEquals(90000.0, (float) $transaction->subtotal);
        $this->assertEquals(10000.0, (float) $transaction->discount_amount);
        $this->assertEquals('promo', $transaction->discount_type);
        $this->assertEquals(80000.0, (float) $transaction->total);

        // Item snapshot verification
        $item = $transaction->items->first();
        $this->assertEquals(10000.0, (float) $item->discount_amount);
        $this->assertEquals('Promo Item Rp 5.000', $item->promo_name);
        $this->assertEquals(90000.0, (float) $item->subtotal);

        // Verify Snapshots in DB
        $this->assertCount(1, $transaction->itemPromos);
        $this->assertCount(1, $transaction->transactionPromos);

        $this->assertDatabaseHas('transaction_promos', [
            'transaction_id' => $transaction->id,
            'transaction_item_id' => $item->id,
            'promo_name' => 'Promo Item Rp 5.000',
            'discount_amount' => 10000.0,
        ]);

        $this->assertDatabaseHas('transaction_promos', [
            'transaction_id' => $transaction->id,
            'transaction_item_id' => null,
            'promo_name' => 'Promo Header 10%',
            'discount_amount' => 10000.0,
        ]);
    }

    public function test_create_transaction_enforces_official_catalog_price(): void
    {
        // Setup official catalog price: 75.000
        $this->productItem->prices()->create([
            'product_id' => $this->product->id,
            'outlet_id' => $this->outlet->id,
            'amount' => 75000,
        ]);

        // Client attempts to submit a manipulated price: 10.000
        $itemDto = new CreateTransactionItemDTO(
            productId: $this->product->id,
            qty: 2.0,
            price: 10000.0, // Tampered price
            productItemId: $this->productItem->id,
            inventoryItemId: $this->inventoryItem->id
        );

        $dto = new CreateB2bTransactionDTO(
            outletId: $this->outlet->id,
            channel: SalesChannelEnum::Wholesale,
            transactionDate: new DateTimeImmutable(now()->toDateTimeString()),
            paymentTerm: PaymentTermEnum::Cash,
            items: [$itemDto]
        );

        $transaction = $this->b2bService->createTransaction($dto, $this->user);

        // Price should be anchored to official 75.000, Subtotal = 2 * 75.000 = 150.000
        $item = $transaction->items->first();
        $this->assertEquals(75000.0, (float) $item->price);
        $this->assertEquals(150000.0, (float) $item->subtotal);
        $this->assertEquals(150000.0, (float) $transaction->subtotal);
        $this->assertEquals(150000.0, (float) $transaction->total);
    }

    public function test_create_transaction_across_different_outlets_does_not_collide_and_scopes_sequences_correctly(): void
    {
        $secondOutlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Secondary Branch',
        ]);

        $itemDto = new CreateTransactionItemDTO(
            productId: $this->product->id,
            qty: 1.0,
            price: 100000.0,
            productItemId: $this->productItem->id,
            inventoryItemId: $this->inventoryItem->id
        );

        $date = new DateTimeImmutable('2026-10-01 10:00:00');

        // Transaksi 1 di Outlet 1
        $dto1 = new CreateB2bTransactionDTO(
            outletId: $this->outlet->id,
            channel: SalesChannelEnum::Wholesale,
            transactionDate: $date,
            paymentTerm: PaymentTermEnum::Cash,
            items: [$itemDto]
        );
        $tx1 = $this->b2bService->createTransaction($dto1, $this->user);

        // Transaksi 1 di Outlet 2 (periode sama, tidak boleh unique collision!)
        $dto2 = new CreateB2bTransactionDTO(
            outletId: $secondOutlet->id,
            channel: SalesChannelEnum::Wholesale,
            transactionDate: $date,
            paymentTerm: PaymentTermEnum::Cash,
            items: [$itemDto]
        );
        $tx2 = $this->b2bService->createTransaction($dto2, $this->user);

        // Transaksi 2 di Outlet 1 (urutan berlanjut)
        $dto3 = new CreateB2bTransactionDTO(
            outletId: $this->outlet->id,
            channel: SalesChannelEnum::Wholesale,
            transactionDate: $date,
            paymentTerm: PaymentTermEnum::Cash,
            items: [$itemDto]
        );
        $tx3 = $this->b2bService->createTransaction($dto3, $this->user);

        $this->assertEquals('TRX/MAIN-B2B-OUTLET/202610/0001', $tx1->transaction_number);
        $this->assertEquals('INV/MAIN-B2B-OUTLET/202610/0001', $tx1->invoice->invoice_number);

        $this->assertEquals('TRX/SECONDARY-BRANCH/202610/0001', $tx2->transaction_number);
        $this->assertEquals('INV/SECONDARY-BRANCH/202610/0001', $tx2->invoice->invoice_number);

        $this->assertEquals('TRX/MAIN-B2B-OUTLET/202610/0002', $tx3->transaction_number);
        $this->assertEquals('INV/MAIN-B2B-OUTLET/202610/0002', $tx3->invoice->invoice_number);
    }
}
