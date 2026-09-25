<?php

namespace Tests\Unit\Services\App\Transaction;

use App\Contracts\Audit\ActivityLoggerInterface;
use App\Enums\TransactionPaymentStatus;
use App\Enums\TransactionStatus;
use App\Events\Transaction\TransactionCompleted;
use App\Events\Transaction\TransactionReversed;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryItem;
use App\Models\Master\PaymentMethod;
use App\Models\Master\ProductItem;
use App\Models\Outlet;
use App\Models\OutletSetting;
use App\Models\User;
use App\Services\App\Transaction\InvoiceTransactionService;
use App\Services\App\Transaction\PriceCalculationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class InvoiceTransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $priceCalculationServiceMock;

    protected $auditLoggerMock;

    protected InvoiceTransactionService $service;

    protected Business $business;

    protected Outlet $outlet;

    protected InventoryItem $inventoryItem;

    protected User $user;

    protected PaymentMethod $paymentMethod;

    protected function setUp(): void
    {
        parent::setUp();

        Event::fake([
            TransactionCompleted::class,
            TransactionReversed::class,
        ]);

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $this->business = Business::create([
            'name' => 'Test Business',
            'owner_name' => 'Owner',
            'email' => 'business@test.com',
            'phone' => '08000000',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $this->outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Test Outlet',
        ]);

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Staff Sales',
            'email' => 'sales@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->paymentMethod = PaymentMethod::create([
            'business_id' => $this->business->id,
            'name' => 'Kas Tunai',
            'type' => 'cash',
        ]);

        $productItem = ProductItem::create([
            'business_id' => $this->business->id,
            'name' => 'Barang Uji 1',
            'sku' => 'SKU-001',
            'item_type' => 'variant_sku',
            'track_inventory' => true,
            'is_active' => true,
        ]);

        $this->inventoryItem = InventoryItem::create([
            'business_id' => $this->business->id,
            'product_item_id' => $productItem->id,
            'is_active' => true,
        ]);

        InventoryBalance::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'current_stock' => 20,
        ]);

        $this->priceCalculationServiceMock = Mockery::mock(PriceCalculationService::class);
        $this->auditLoggerMock = Mockery::mock(ActivityLoggerInterface::class);

        $this->service = new InvoiceTransactionService(
            $this->priceCalculationServiceMock,
            $this->auditLoggerMock
        );
    }

    public function test_it_creates_draft_invoice_transaction(): void
    {
        $payload = [
            'outlet_id' => $this->outlet->id,
            'channel' => 'wholesale',
            'transaction_date' => now()->toDateString(),
            'payment_term' => 'credit',
            'due_date' => now()->addDays(30)->toDateString(),
            'items' => [
                [
                    'inventory_item_id' => $this->inventoryItem->id,
                    'product_name' => 'Barang Uji 1',
                    'qty' => 5,
                    'price' => 100000,
                    'discount_amount' => 10000,
                ],
            ],
            'shipping_fee' => 15000,
            'tax_amount' => 0,
            'service_charge_amount' => 0,
            'notes' => 'Catatan Faktur',
            'terms_and_conditions' => 'Syarat Faktur Standar',
        ];

        $transaction = $this->service->createTransaction($payload, $this->user);

        $this->assertNotNull($transaction);
        $this->assertEquals(TransactionStatus::Draft, $transaction->status);
        $this->assertEquals(TransactionPaymentStatus::Draft, $transaction->payment_status);
        $this->assertEquals(490000 + 15000, $transaction->total); // (5*100000 - 10000) + 15000
        $this->assertEquals(0, $transaction->total_paid);
        $this->assertEquals($transaction->total, $transaction->balance_due);
        $this->assertNotNull($transaction->invoice);
        $this->assertEquals('credit', $transaction->invoice->payment_term);
        $this->assertCount(1, $transaction->items);
    }

    public function test_check_stock_availability_validates_correctly(): void
    {
        $validItems = [
            [
                'inventory_item_id' => $this->inventoryItem->id,
                'qty' => 10,
                'product_name' => 'Barang Uji 1',
            ],
        ];

        // Should not throw exception when stock is sufficient (available: 20, requested: 10)
        $this->service->checkStockAvailability($validItems, $this->outlet->id);
        $this->assertTrue(true);

        // Should throw ValidationException when requested exceeds available (available: 20, requested: 25)
        $excessItems = [
            [
                'inventory_item_id' => $this->inventoryItem->id,
                'qty' => 25,
                'product_name' => 'Barang Uji 1',
            ],
        ];

        $this->expectException(ValidationException::class);
        $this->service->checkStockAvailability($excessItems, $this->outlet->id);
    }

    public function test_check_stock_availability_allows_negative_when_setting_enabled(): void
    {
        OutletSetting::create([
            'outlet_id' => $this->outlet->id,
            'category' => 'pos',
            'key' => 'allow_negative_stock',
            'value' => [true],
            'type' => 'boolean',
        ]);

        $excessItems = [
            [
                'inventory_item_id' => $this->inventoryItem->id,
                'qty' => 50,
                'product_name' => 'Barang Uji 1',
            ],
        ];

        // Should not throw because allow_negative_stock is true
        $this->service->checkStockAvailability($excessItems, $this->outlet->id);
        $this->assertTrue(true);
    }

    public function test_it_issues_cash_invoice_and_dispatches_event(): void
    {
        $transaction = $this->service->createTransaction([
            'outlet_id' => $this->outlet->id,
            'payment_term' => 'cash',
            'items' => [
                [
                    'inventory_item_id' => $this->inventoryItem->id,
                    'qty' => 2,
                    'price' => 50000,
                ],
            ],
        ], $this->user);

        $this->auditLoggerMock->shouldReceive('log')->once();

        $issued = $this->service->issueInvoice($transaction, $this->user, [
            'payment_method_id' => $this->paymentMethod->id,
            'paid_amount' => 100000,
            'payment_notes' => 'Lunas Tunai',
        ]);

        $this->assertEquals(TransactionStatus::Paid, $issued->status);
        $this->assertEquals(TransactionPaymentStatus::Paid, $issued->payment_status);
        $this->assertEquals(100000, $issued->total_paid);
        $this->assertEquals(0, $issued->balance_due);
        $this->assertCount(1, $issued->payments);

        Event::assertDispatched(TransactionCompleted::class);
    }

    public function test_it_issues_credit_invoice_with_dp(): void
    {
        $transaction = $this->service->createTransaction([
            'outlet_id' => $this->outlet->id,
            'payment_term' => 'credit',
            'items' => [
                [
                    'inventory_item_id' => $this->inventoryItem->id,
                    'qty' => 2,
                    'price' => 100000,
                ],
            ],
        ], $this->user);

        $this->auditLoggerMock->shouldReceive('log')->once();

        $issued = $this->service->issueInvoice($transaction, $this->user, [
            'payment_method_id' => $this->paymentMethod->id,
            'paid_amount' => 50000,
            'payment_notes' => 'Uang Muka 25%',
        ]);

        $this->assertEquals(TransactionStatus::Unpaid, $issued->status);
        $this->assertEquals(TransactionPaymentStatus::Partial, $issued->payment_status);
        $this->assertEquals(50000, $issued->total_paid);
        $this->assertEquals(150000, $issued->balance_due);
        $this->assertCount(1, $issued->payments);

        Event::assertDispatched(TransactionCompleted::class);
    }

    public function test_it_issues_credit_invoice_without_dp(): void
    {
        $transaction = $this->service->createTransaction([
            'outlet_id' => $this->outlet->id,
            'payment_term' => 'credit',
            'items' => [
                [
                    'inventory_item_id' => $this->inventoryItem->id,
                    'qty' => 1,
                    'price' => 100000,
                ],
            ],
        ], $this->user);

        $this->auditLoggerMock->shouldReceive('log')->once();

        $issued = $this->service->issueInvoice($transaction, $this->user, [
            'paid_amount' => 0,
        ]);

        $this->assertEquals(TransactionStatus::Unpaid, $issued->status);
        $this->assertEquals(TransactionPaymentStatus::Unpaid, $issued->payment_status);
        $this->assertEquals(0, $issued->total_paid);
        $this->assertEquals(100000, $issued->balance_due);
        $this->assertCount(0, $issued->payments);

        Event::assertDispatched(TransactionCompleted::class);
    }

    public function test_it_records_payment_and_clears_balance(): void
    {
        $transaction = $this->service->createTransaction([
            'outlet_id' => $this->outlet->id,
            'payment_term' => 'credit',
            'items' => [
                [
                    'inventory_item_id' => $this->inventoryItem->id,
                    'qty' => 1,
                    'price' => 100000,
                ],
            ],
        ], $this->user);

        $this->auditLoggerMock->shouldReceive('log')->twice();

        // 1. Issue with no DP
        $this->service->issueInvoice($transaction, $this->user);

        // 2. Record full payment
        $paid = $this->service->recordPayment($transaction, [
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 100000,
            'notes' => 'Pelunasan Faktur',
        ], $this->user);

        $this->assertEquals(TransactionStatus::Paid, $paid->status);
        $this->assertEquals(TransactionPaymentStatus::Paid, $paid->payment_status);
        $this->assertEquals(100000, $paid->total_paid);
        $this->assertEquals(0, $paid->balance_due);
        $this->assertCount(1, $paid->payments);
    }

    public function test_it_cancels_draft_invoice_without_stock_reversal(): void
    {
        $transaction = $this->service->createTransaction([
            'outlet_id' => $this->outlet->id,
            'payment_term' => 'credit',
            'items' => [
                [
                    'inventory_item_id' => $this->inventoryItem->id,
                    'qty' => 1,
                    'price' => 100000,
                ],
            ],
        ], $this->user);

        $this->auditLoggerMock->shouldReceive('log')->once();

        $cancelled = $this->service->cancelInvoice($transaction, $this->user);

        $this->assertEquals(TransactionStatus::Cancel, $cancelled->status);
        Event::assertNotDispatched(TransactionReversed::class);
    }

    public function test_it_cancels_issued_invoice_with_stock_reversal(): void
    {
        $transaction = $this->service->createTransaction([
            'outlet_id' => $this->outlet->id,
            'payment_term' => 'credit',
            'items' => [
                [
                    'inventory_item_id' => $this->inventoryItem->id,
                    'qty' => 1,
                    'price' => 100000,
                ],
            ],
        ], $this->user);

        $this->auditLoggerMock->shouldReceive('log')->twice();

        $this->service->issueInvoice($transaction, $this->user);
        $cancelled = $this->service->cancelInvoice($transaction, $this->user);

        $this->assertEquals(TransactionStatus::Cancel, $cancelled->status);
        Event::assertDispatched(TransactionReversed::class);
    }

    public function test_it_voids_paid_invoice_with_stock_reversal(): void
    {
        $transaction = $this->service->createTransaction([
            'outlet_id' => $this->outlet->id,
            'payment_term' => 'cash',
            'items' => [
                [
                    'inventory_item_id' => $this->inventoryItem->id,
                    'qty' => 1,
                    'price' => 100000,
                ],
            ],
        ], $this->user);

        $this->auditLoggerMock->shouldReceive('log')->twice();

        $this->service->issueInvoice($transaction, $this->user, [
            'payment_method_id' => $this->paymentMethod->id,
            'paid_amount' => 100000,
        ]);

        $voided = $this->service->voidInvoice($transaction, $this->user);

        $this->assertEquals(TransactionStatus::Void, $voided->status);
        Event::assertDispatched(TransactionReversed::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
