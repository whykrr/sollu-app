<?php

namespace Tests\Unit\Services\App\Transaction;

use App\Contracts\Audit\ActivityLoggerInterface;
use App\Enums\TransactionPaymentStatus;
use App\Enums\TransactionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Master\Customer;
use App\Models\Outlet;
use App\Models\Sales\Transaction;
use App\Models\User;
use App\Services\App\Transaction\B2bTransactionService;
use App\Services\App\Transaction\PriceCalculationService;
use App\Services\App\Transaction\TransactionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class B2bTransactionServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $priceCalculationServiceMock;

    protected $baseTransactionServiceMock;

    protected $auditLoggerMock;

    protected $service;

    protected $user;

    protected $business;

    protected $outlet;

    protected $customer;

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

        $this->customer = Customer::create([
            'business_id' => $this->business->id,
            'name' => 'Test Customer',
            'phone' => '08111',
        ]);

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Test User',
            'email' => 'user@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->priceCalculationServiceMock = Mockery::mock(PriceCalculationService::class);
        $this->baseTransactionServiceMock = Mockery::mock(TransactionService::class);
        $this->auditLoggerMock = Mockery::mock(ActivityLoggerInterface::class);

        $this->service = new B2bTransactionService(
            $this->priceCalculationServiceMock,
            $this->baseTransactionServiceMock,
            $this->auditLoggerMock
        );
    }

    public function test_it_creates_b2b_transaction_successfully(): void
    {
        $data = [
            'outlet_id' => $this->outlet->id,
            'customer_id' => $this->customer->id,
            'channel' => 'wholesale',
            'transaction_date' => now()->toDateString(),
            'manual_discount_amount' => 10000,
            'tax_amount' => 5000,
            'shipping_fee' => 15000,
            'service_charge_amount' => 0,
            'payment_term' => 'credit',
            'due_date' => now()->addDays(30)->toDateString(),
            'notes' => 'Test Note',
            'items' => [
                [
                    'product_id' => null,
                    'inventory_item_id' => null,
                    'product_name' => 'Produk A',
                    'qty' => 2,
                    'price' => 50000,
                    'discount_amount' => 0,
                ],
            ],
            'action' => 'draft',
        ];

        $this->auditLoggerMock
            ->shouldReceive('log')
            ->once();

        $transaction = $this->service->createTransaction($data, $this->user);

        $this->assertInstanceOf(Transaction::class, $transaction);
        $this->assertEquals(100000, $transaction->subtotal); // 2 * 50000
        $this->assertEquals(10000, $transaction->discount_amount);
        $this->assertEquals(110000, $transaction->total); // 100000 - 10000 + 5000 + 15000
        $this->assertEquals(110000, $transaction->balance_due);
        $this->assertEquals(TransactionStatus::Draft, $transaction->status);
        $this->assertEquals(TransactionPaymentStatus::Draft, $transaction->payment_status);
        $this->assertNotNull($transaction->invoice);
        $this->assertEquals('credit', $transaction->invoice->payment_term);
        $this->assertEquals($data['due_date'], $transaction->invoice->due_date->toDateString());
    }

    public function test_it_issues_invoice_successfully(): void
    {
        $transaction = Transaction::create([
            'outlet_id' => $this->outlet->id,
            'transaction_number' => 'B2B-TEST',
            'status' => TransactionStatus::Draft,
            'payment_status' => TransactionPaymentStatus::Draft,
            'total' => 100000,
            'balance_due' => 100000,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $transaction->invoice()->create([
            'invoice_number' => 'INV-TEST',
            'invoice_date' => now()->toDateString(),
            'payment_term' => 'credit',
            'status' => TransactionStatus::Draft,
        ]);

        $this->baseTransactionServiceMock
            ->shouldReceive('issueInvoice')
            ->with($transaction, $this->user, [])
            ->once()
            ->andReturnUsing(function ($tx) {
                $tx->status = TransactionStatus::Unpaid;
                $tx->payment_status = TransactionPaymentStatus::Unpaid;
                $tx->invoice->status = TransactionStatus::Unpaid;

                return $tx;
            });

        $issuedTransaction = $this->service->issueInvoice($transaction, $this->user);

        $this->assertEquals(TransactionStatus::Unpaid, $issuedTransaction->status);
        $this->assertEquals(TransactionPaymentStatus::Unpaid, $issuedTransaction->payment_status);
        $this->assertEquals(TransactionStatus::Unpaid, $issuedTransaction->invoice->status);
    }

    public function test_it_throws_error_when_issuing_non_draft_invoice(): void
    {
        $transaction = Transaction::create([
            'outlet_id' => $this->outlet->id,
            'transaction_number' => 'B2B-TEST',
            'status' => TransactionStatus::Paid,
            'payment_status' => TransactionPaymentStatus::Paid,
            'total' => 100000,
            'balance_due' => 0,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $this->baseTransactionServiceMock
            ->shouldReceive('issueInvoice')
            ->with($transaction, $this->user, [])
            ->once()
            ->andThrow(new \Exception('Hanya transaksi draf yang dapat diterbitkan.'));

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Hanya transaksi draf yang dapat diterbitkan.');

        $this->service->issueInvoice($transaction, $this->user);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
