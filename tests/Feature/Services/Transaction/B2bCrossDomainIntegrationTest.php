<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Transaction;

use App\DTOs\Transaction\CreateB2bTransactionDTO;
use App\DTOs\Transaction\CreateTransactionItemDTO;
use App\DTOs\Transaction\RecordPaymentDTO;
use App\Enums\InventoryMovementType;
use App\Enums\PaymentTermEnum;
use App\Enums\ProductTypeEnum;
use App\Enums\SalesChannelEnum;
use App\Enums\TransactionPaymentStatus;
use App\Enums\TransactionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryItem;
use App\Models\Master\Customer;
use App\Models\Master\PaymentMethod;
use App\Models\Master\Product;
use App\Models\Master\ProductItem;
use App\Models\Outlet;
use App\Models\Sales\Transaction;
use App\Models\User;
use App\Services\App\Inventory\InventoryCostingService;
use App\Services\App\Transaction\B2bTransactionService;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class B2bCrossDomainIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected Business $tenantA;

    protected Business $tenantB;

    protected Outlet $outletA;

    protected Outlet $outletB;

    protected User $userA;

    protected User $userB;

    protected Customer $customerA;

    protected Customer $customerB;

    protected Product $productA;

    protected ProductItem $productItemA;

    protected InventoryItem $inventoryItemA;

    protected PaymentMethod $paymentMethodA;

    protected B2bTransactionService $b2bService;

    protected InventoryCostingService $costingService;

    protected function setUp(): void
    {
        parent::setUp();

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        // Tenant A
        $this->tenantA = Business::create([
            'name' => 'Tenant A Corporate',
            'owner_name' => 'Owner A',
            'email' => 'tenanta_'.uniqid().'@test.com',
            'phone' => '081111111',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $this->outletA = Outlet::create([
            'business_id' => $this->tenantA->id,
            'name' => 'Outlet A Main',
        ]);

        $this->userA = User::create([
            'business_id' => $this->tenantA->id,
            'name' => 'User Tenant A',
            'email' => 'usera_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->customerA = Customer::create([
            'business_id' => $this->tenantA->id,
            'name' => 'Corporate Client A',
            'phone' => '0812345678',
            'email' => 'clienta@test.com',
        ]);

        $this->productA = Product::create([
            'business_id' => $this->tenantA->id,
            'name' => 'Gula Pasir Kristal 50kg',
            'product_type' => ProductTypeEnum::BASIC,
            'is_active' => true,
        ]);

        $this->productItemA = ProductItem::create([
            'business_id' => $this->tenantA->id,
            'product_id' => $this->productA->id,
            'name' => 'Gula Pasir Kristal 50kg',
            'sku' => 'GPK-50KG',
            'item_type' => 'variant_sku',
            'track_inventory' => true,
            'is_active' => true,
        ]);

        $this->inventoryItemA = InventoryItem::create([
            'business_id' => $this->tenantA->id,
            'product_id' => $this->productA->id,
            'product_item_id' => $this->productItemA->id,
            'is_active' => true,
        ]);

        $this->paymentMethodA = PaymentMethod::create([
            'business_id' => $this->tenantA->id,
            'name' => 'Transfer BCA Tenant A',
            'code' => 'bca_tenanta',
            'type' => 'bank_transfer',
            'is_active' => true,
        ]);

        // Tenant B (for multi-tenant isolation verification)
        $this->tenantB = Business::create([
            'name' => 'Tenant B Enterprise',
            'owner_name' => 'Owner B',
            'email' => 'tenantb_'.uniqid().'@test.com',
            'phone' => '082222222',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $this->outletB = Outlet::create([
            'business_id' => $this->tenantB->id,
            'name' => 'Outlet B Main',
        ]);

        $this->userB = User::create([
            'business_id' => $this->tenantB->id,
            'name' => 'User Tenant B',
            'email' => 'userb_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->customerB = Customer::create([
            'business_id' => $this->tenantB->id,
            'name' => 'Corporate Client B',
            'phone' => '0898765432',
            'email' => 'clientb@test.com',
        ]);

        $this->b2bService = app(B2bTransactionService::class);
        $this->costingService = app(InventoryCostingService::class);
    }

    public function test_full_cross_domain_lifecycle_crm_product_promo_inventory_fifo_and_payment(): void
    {
        // ── 1. Inventory Batch Setup (FIFO Layers) ───────────────────
        // Batch 1: 10 karung @ 600.000
        $this->costingService->recordIncomingStock(
            $this->tenantA,
            $this->outletA,
            $this->inventoryItemA,
            10.0,
            600000.0,
            InventoryMovementType::InitialStock,
            null,
            'Batch Pembelian 1',
            $this->userA
        );

        // Batch 2: 10 karung @ 650.000 (Harga naik)
        $this->costingService->recordIncomingStock(
            $this->tenantA,
            $this->outletA,
            $this->inventoryItemA,
            10.0,
            650000.0,
            InventoryMovementType::Purchase,
            null,
            'Batch Pembelian 2',
            $this->userA
        );

        $this->assertEquals(20.0, (float) InventoryBalance::where('inventory_item_id', $this->inventoryItemA->id)->value('current_stock'));

        // ── 2. Create B2B Wholesale Order for 15 karung (Consumes Batch 1 (10 pcs) + Batch 2 (5 pcs)) ──
        $itemDto = new CreateTransactionItemDTO(
            productId: $this->productA->id,
            qty: 15.0,
            price: 750000.0, // Selling price
            productItemId: $this->productItemA->id,
            inventoryItemId: $this->inventoryItemA->id,
            discountAmount: 250000.0, // Promo / Diskon baris grosir
            notes: 'Harga Grosir Khusus Client A'
        );

        $dto = new CreateB2bTransactionDTO(
            outletId: $this->outletA->id,
            channel: SalesChannelEnum::Wholesale,
            transactionDate: new DateTimeImmutable('2026-09-30 08:30:00'),
            paymentTerm: PaymentTermEnum::Credit,
            items: [$itemDto],
            customerId: $this->customerA->id,
            dueDate: new DateTimeImmutable('2026-10-30 08:30:00'),
            notes: 'PO-CLIENTA-2026-001',
            discountType: 'fixed',
            discountValue: 100000.0, // Document-level promo
            taxAmount: 0.0,
            shippingFee: 50000.0,
            serviceChargeAmount: 0.0,
            paymentTermCode: 'net_30'
        );

        $draft = $this->b2bService->createTransaction($dto, $this->userA);

        // Subtotal = (15 * 750.000) - 250.000 = 11.000.000
        $this->assertEquals(11000000.0, (float) $draft->subtotal);
        // Total = 11.000.000 - 100.000 + 50.000 = 10.950.000
        $this->assertEquals(10950000.0, (float) $draft->total);
        $this->assertEquals(10950000.0, (float) $draft->balance_due);
        $this->assertEquals(TransactionStatus::Draft, $draft->status);

        // ── 3. Issue Invoice -> Triggers FIFO Stock Deduction ─────────
        $issued = $this->b2bService->issueInvoice($draft, $this->userA);

        $this->assertEquals(TransactionStatus::Unpaid, $issued->status);

        // Stock remaining = 20 - 15 = 5 karung
        $this->assertEquals(5.0, (float) InventoryBalance::where('inventory_item_id', $this->inventoryItemA->id)->value('current_stock'));

        // FIFO COGS: (10 pcs * 600.000) + (5 pcs * 650.000) = 6.000.000 + 3.250.000 = 9.250.000
        // Unit COGS = 9.250.000 / 15 = 616.666,6667
        $item = $issued->items->first();
        $this->assertEquals(9250000.0, round((float) $item->cogs_amount, 2));

        // ── 4. Multi-Stage Payment: DP 50% ───────────────────────────
        $dpDto = new RecordPaymentDTO(
            paymentMethodId: $this->paymentMethodA->id,
            amount: 5000000.0,
            paymentDate: new DateTimeImmutable('2026-09-30 09:00:00'),
            paymentReference: 'TRF-DP-5JT',
            notes: 'Uang Muka 5 Juta'
        );
        $partial = $this->b2bService->recordPayment($issued, $dpDto, $this->userA);

        $this->assertEquals(TransactionStatus::Partial, $partial->status);
        $this->assertEquals(TransactionPaymentStatus::Partial, $partial->payment_status);
        $this->assertEquals(5000000.0, (float) $partial->total_paid);
        $this->assertEquals(5950000.0, (float) $partial->balance_due);

        // ── 5. Second Payment: Full Pelunasan ────────────────────────
        $settleDto = new RecordPaymentDTO(
            paymentMethodId: $this->paymentMethodA->id,
            amount: 5950000.0,
            paymentDate: new DateTimeImmutable('2026-10-15 14:00:00'),
            paymentReference: 'TRF-PELUNASAN-5.95JT',
            notes: 'Pelunasan Faktur'
        );
        $paid = $this->b2bService->recordPayment($partial, $settleDto, $this->userA);

        $this->assertEquals(TransactionStatus::Paid, $paid->status);
        $this->assertEquals(TransactionPaymentStatus::Paid, $paid->payment_status);
        $this->assertEquals(10950000.0, (float) $paid->total_paid);
        $this->assertEquals(0.0, (float) $paid->balance_due);
        $this->assertCount(2, $paid->payments);
    }

    public function test_multi_tenant_isolation_assurance(): void
    {
        // Create Transaction in Tenant A
        $itemDto = new CreateTransactionItemDTO(
            productId: $this->productA->id,
            qty: 1.0,
            price: 100000.0,
        );

        $dto = new CreateB2bTransactionDTO(
            outletId: $this->outletA->id,
            channel: SalesChannelEnum::Wholesale,
            transactionDate: now(),
            paymentTerm: PaymentTermEnum::Cash,
            items: [$itemDto],
            customerId: $this->customerA->id
        );

        $txA = $this->b2bService->createTransaction($dto, $this->userA);

        // Verification: Outlet B / Tenant B does not have this transaction
        $this->assertDatabaseHas('transactions', [
            'id' => $txA->id,
            'outlet_id' => $this->outletA->id,
        ]);

        $this->assertDatabaseMissing('transactions', [
            'id' => $txA->id,
            'outlet_id' => $this->outletB->id,
        ]);
    }
}
