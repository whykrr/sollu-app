<?php

namespace Tests\Unit\Services\App\Transaction;

use App\Contracts\Audit\ActivityLoggerInterface;
use App\Enums\TransactionPaymentStatus;
use App\Enums\TransactionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryItem;
use App\Models\Master\PaymentMethod;
use App\Models\Outlet;
use App\Models\OutletSetting;
use App\Models\Sales\Transaction;
use App\Models\User;
use App\Services\App\Transaction\PriceCalculationService;
use App\Services\App\Transaction\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class TransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $priceCalculationServiceMock;

    protected $auditLoggerMock;

    protected $service;

    protected $business;

    protected $outlet;

    protected $inventoryItem;

    protected function setUp(): void
    {
        parent::setUp();

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

        $this->inventoryItem = InventoryItem::create([
            'business_id' => $this->business->id,
            'name' => 'Test Item',
            'sku' => 'SKU-001',
            'item_type' => 'variant_sku',
            'current_stock' => 10,
            'track_inventory' => true,
            'is_active' => true,
        ]);

        // Create stock balance
        InventoryBalance::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'current_stock' => 10,
        ]);

        $this->priceCalculationServiceMock = Mockery::mock(PriceCalculationService::class);
        $this->auditLoggerMock = Mockery::mock(ActivityLoggerInterface::class);

        $this->service = new TransactionService(
            $this->priceCalculationServiceMock,
            $this->auditLoggerMock
        );
    }

    public function test_check_stock_availability_allows_valid_stock(): void
    {
        $items = [
            [
                'inventory_item_id' => $this->inventoryItem->id,
                'qty' => 5,
                'product_name' => 'Test Item',
            ],
        ];

        // Should not throw exception
        $this->service->checkStockAvailability($items, $this->outlet->id);

        $this->assertTrue(true);
    }

    public function test_check_stock_availability_throws_error_if_stock_insufficient(): void
    {
        OutletSetting::create([
            'outlet_id' => $this->outlet->id,
            'category' => 'pos',
            'key' => 'allow_negative_stock',
            'value' => [false],
            'type' => 'boolean',
        ]);

        $items = [
            [
                'inventory_item_id' => $this->inventoryItem->id,
                'qty' => 15,
                'product_name' => 'Test Item',
            ],
        ];

        $this->expectException(ValidationException::class);

        $this->service->checkStockAvailability($items, $this->outlet->id);
    }

    public function test_check_stock_availability_allows_negative_stock_if_setting_enabled(): void
    {
        OutletSetting::create([
            'outlet_id' => $this->outlet->id,
            'category' => 'pos',
            'key' => 'allow_negative_stock',
            'value' => [true],
            'type' => 'boolean',
        ]);

        $items = [
            [
                'inventory_item_id' => $this->inventoryItem->id,
                'qty' => 15, // Requested more than available (10)
                'product_name' => 'Test Item',
            ],
        ];

        // Should not throw exception
        $this->service->checkStockAvailability($items, $this->outlet->id);

        $this->assertTrue(true);
    }

    public function test_it_issues_cash_invoice_with_payment_method_and_marks_paid(): void
    {
        $user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Cashier',
            'email' => 'cashier@test.com',
            'password' => bcrypt('password'),
        ]);

        $pm = PaymentMethod::create([
            'business_id' => $this->business->id,
            'name' => 'Kas Tunai',
            'type' => 'cash',
        ]);

        $transaction = Transaction::create([
            'outlet_id' => $this->outlet->id,
            'transaction_number' => 'TRX-CASH-001',
            'status' => TransactionStatus::Draft,
            'payment_status' => TransactionPaymentStatus::Draft,
            'total' => 50000,
            'balance_due' => 50000,
            'total_paid' => 0,
        ]);

        $transaction->invoice()->create([
            'invoice_number' => 'INV-CASH-001',
            'invoice_date' => now()->toDateString(),
            'payment_term' => 'cash',
            'status' => TransactionStatus::Draft,
        ]);

        $this->auditLoggerMock
            ->shouldReceive('log')
            ->once();

        $issued = $this->service->issueInvoice($transaction, $user, [
            'payment_method_id' => $pm->id,
            'paid_amount' => 60000,
            'payment_notes' => 'Lunas Tunai',
        ]);

        $this->assertEquals(TransactionStatus::Paid, $issued->status);
        $this->assertEquals(TransactionPaymentStatus::Paid, $issued->payment_status);
        $this->assertEquals(50000, $issued->total_paid);
        $this->assertEquals(0, $issued->balance_due);
        $this->assertCount(1, $issued->payments);
        $this->assertEquals(60000, $issued->payments->first()->amount);
        $this->assertEquals(10000, $issued->payments->first()->change_amount);
    }

    public function test_it_issues_credit_invoice_with_dp_and_marks_partial(): void
    {
        $user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Sales',
            'email' => 'sales@test.com',
            'password' => bcrypt('password'),
        ]);

        $pm = PaymentMethod::create([
            'business_id' => $this->business->id,
            'name' => 'Transfer BCA',
            'type' => 'bank_transfer',
        ]);

        $transaction = Transaction::create([
            'outlet_id' => $this->outlet->id,
            'transaction_number' => 'TRX-CREDIT-001',
            'status' => TransactionStatus::Draft,
            'payment_status' => TransactionPaymentStatus::Draft,
            'total' => 100000,
            'balance_due' => 100000,
            'total_paid' => 0,
        ]);

        $transaction->invoice()->create([
            'invoice_number' => 'INV-CREDIT-001',
            'invoice_date' => now()->toDateString(),
            'payment_term' => 'credit',
            'due_date' => now()->addDays(14)->toDateString(),
            'status' => TransactionStatus::Draft,
        ]);

        $this->auditLoggerMock
            ->shouldReceive('log')
            ->once();

        $issued = $this->service->issueInvoice($transaction, $user, [
            'payment_method_id' => $pm->id,
            'paid_amount' => 40000,
            'payment_notes' => 'DP 40%',
        ]);

        $this->assertEquals(TransactionStatus::Unpaid, $issued->status);
        $this->assertEquals(TransactionPaymentStatus::Partial, $issued->payment_status);
        $this->assertEquals(40000, $issued->total_paid);
        $this->assertEquals(60000, $issued->balance_due);
        $this->assertCount(1, $issued->payments);
    }

    public function test_it_records_subsequent_payment_and_marks_paid_when_balance_cleared(): void
    {
        $user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Finance',
            'email' => 'finance@test.com',
            'password' => bcrypt('password'),
        ]);

        $pm = PaymentMethod::create([
            'business_id' => $this->business->id,
            'name' => 'Transfer Mandiri',
            'type' => 'bank_transfer',
        ]);

        $transaction = Transaction::create([
            'outlet_id' => $this->outlet->id,
            'transaction_number' => 'TRX-PARTIAL-001',
            'status' => TransactionStatus::Unpaid,
            'payment_status' => TransactionPaymentStatus::Partial,
            'total' => 100000,
            'total_paid' => 40000,
            'balance_due' => 60000,
        ]);

        $this->auditLoggerMock
            ->shouldReceive('log')
            ->once();

        $paid = $this->service->recordPayment($transaction, [
            'payment_method_id' => $pm->id,
            'amount' => 60000,
            'notes' => 'Pelunasan Akhir',
        ], $user);

        $this->assertEquals(TransactionStatus::Paid, $paid->status);
        $this->assertEquals(TransactionPaymentStatus::Paid, $paid->payment_status);
        $this->assertEquals(100000, $paid->total_paid);
        $this->assertEquals(0, $paid->balance_due);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
