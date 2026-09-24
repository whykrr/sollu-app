<?php

namespace Tests\Unit\Services\App\Transaction;

use App\Contracts\Audit\ActivityLoggerInterface;
use App\Enums\TransactionStatus;
use App\Enums\TransactionPaymentStatus;
use App\Models\BusinessType;
use App\Models\Business;
use App\Models\Outlet;
use App\Models\Master\PaymentMethod;
use App\Models\Sales\Transaction;
use App\Models\User;
use App\Services\App\Transaction\TransactionPaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class TransactionPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected $auditLoggerMock;
    protected $service;
    protected $user;
    protected $business;
    protected $outlet;
    protected $paymentMethod;

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

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Test User',
            'email' => 'user@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->paymentMethod = PaymentMethod::create([
            'business_id' => $this->business->id,
            'name' => 'Cash',
            'code' => 'cash',
            'type' => 'cash',
            'is_active' => true,
        ]);

        $this->auditLoggerMock = Mockery::mock(ActivityLoggerInterface::class);

        $this->service = new TransactionPaymentService(
            $this->auditLoggerMock
        );
    }

    public function test_it_records_payment_successfully_for_partial_amount(): void
    {
        $transaction = Transaction::create([
            'outlet_id' => $this->outlet->id,
            'transaction_number' => 'B2B-TEST',
            'status' => TransactionStatus::Unpaid,
            'payment_status' => TransactionPaymentStatus::Unpaid,
            'total' => 100000,
            'total_paid' => 0,
            'balance_due' => 100000,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $transaction->invoice()->create([
            'invoice_number' => 'INV-TEST',
            'invoice_date' => now()->toDateString(),
            'payment_term' => 'credit',
            'status' => TransactionStatus::Unpaid,
        ]);

        $data = [
            'amount' => 50000,
            'payment_method_id' => $this->paymentMethod->id,
            'payment_date' => now()->toDateString(),
            'notes' => 'Test Payment',
        ];

        $this->auditLoggerMock
            ->shouldReceive('log')
            ->once();

        $updatedTransaction = $this->service->recordPayment($transaction, $data, $this->user);

        $this->assertEquals(50000, $updatedTransaction->total_paid);
        $this->assertEquals(50000, $updatedTransaction->balance_due);
        $this->assertEquals(TransactionStatus::Unpaid, $updatedTransaction->status);
        $this->assertEquals(TransactionPaymentStatus::Unpaid, $updatedTransaction->payment_status);
        
        $this->assertCount(1, $updatedTransaction->payments);
        $this->assertEquals(50000, $updatedTransaction->payments->first()->amount);
        $this->assertEquals(0, $updatedTransaction->payments->first()->change_amount);
    }

    public function test_it_records_payment_successfully_for_full_amount(): void
    {
        $transaction = Transaction::create([
            'outlet_id' => $this->outlet->id,
            'transaction_number' => 'B2B-TEST',
            'status' => TransactionStatus::Unpaid,
            'payment_status' => TransactionPaymentStatus::Unpaid,
            'total' => 100000,
            'total_paid' => 0,
            'balance_due' => 100000,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $transaction->invoice()->create([
            'invoice_number' => 'INV-TEST',
            'invoice_date' => now()->toDateString(),
            'payment_term' => 'credit',
            'status' => TransactionStatus::Unpaid,
        ]);

        $data = [
            'amount' => 100000,
            'payment_method_id' => $this->paymentMethod->id,
        ];

        $this->auditLoggerMock
            ->shouldReceive('log')
            ->once();

        $updatedTransaction = $this->service->recordPayment($transaction, $data, $this->user);

        $this->assertEquals(100000, $updatedTransaction->total_paid);
        $this->assertEquals(0, $updatedTransaction->balance_due);
        $this->assertEquals(TransactionStatus::Paid, $updatedTransaction->status);
        $this->assertEquals(TransactionPaymentStatus::Paid, $updatedTransaction->payment_status);
        $this->assertEquals(TransactionStatus::Paid, $updatedTransaction->invoice->status);
    }

    public function test_it_records_payment_with_change_amount(): void
    {
        $transaction = Transaction::create([
            'outlet_id' => $this->outlet->id,
            'transaction_number' => 'B2B-TEST',
            'status' => TransactionStatus::Unpaid,
            'payment_status' => TransactionPaymentStatus::Unpaid,
            'total' => 50000,
            'total_paid' => 0,
            'balance_due' => 50000,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $data = [
            'amount' => 100000,
            'payment_method_id' => $this->paymentMethod->id,
        ];

        $this->auditLoggerMock
            ->shouldReceive('log')
            ->once();

        $updatedTransaction = $this->service->recordPayment($transaction, $data, $this->user);

        $this->assertEquals(50000, $updatedTransaction->total_paid);
        $this->assertEquals(0, $updatedTransaction->balance_due);
        
        $this->assertEquals(100000, $updatedTransaction->payments->first()->amount);
        $this->assertEquals(50000, $updatedTransaction->payments->first()->change_amount);
    }

    public function test_it_throws_error_if_amount_is_zero(): void
    {
        $transaction = Transaction::create([
            'outlet_id' => $this->outlet->id,
            'transaction_number' => 'B2B-TEST',
            'status' => TransactionStatus::Unpaid,
            'payment_status' => TransactionPaymentStatus::Unpaid,
            'total' => 100000,
            'balance_due' => 100000,
        ]);

        $data = [
            'amount' => 0,
            'payment_method_id' => $this->paymentMethod->id,
        ];

        $this->expectException(ValidationException::class);
        $this->service->recordPayment($transaction, $data, $this->user);
    }

    public function test_it_throws_error_if_payment_method_is_invalid(): void
    {
        $transaction = Transaction::create([
            'outlet_id' => $this->outlet->id,
            'transaction_number' => 'B2B-TEST',
            'status' => TransactionStatus::Unpaid,
            'payment_status' => TransactionPaymentStatus::Unpaid,
            'total' => 100000,
            'balance_due' => 100000,
        ]);

        $data = [
            'amount' => 10000,
            'payment_method_id' => 9999, // Invalid
        ];

        $this->expectException(ValidationException::class);
        $this->service->recordPayment($transaction, $data, $this->user);
    }

    public function test_it_throws_error_if_transaction_is_already_paid(): void
    {
        $transaction = Transaction::create([
            'outlet_id' => $this->outlet->id,
            'transaction_number' => 'B2B-TEST',
            'status' => TransactionStatus::Paid,
            'payment_status' => TransactionPaymentStatus::Paid,
            'total' => 100000,
            'total_paid' => 100000,
            'balance_due' => 0,
        ]);

        $data = [
            'amount' => 10000,
            'payment_method_id' => $this->paymentMethod->id,
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Transaksi sudah lunas.');
        
        $this->service->recordPayment($transaction, $data, $this->user);
    }

    public function test_it_throws_error_if_transaction_is_draft(): void
    {
        $transaction = Transaction::create([
            'outlet_id' => $this->outlet->id,
            'transaction_number' => 'B2B-TEST',
            'status' => TransactionStatus::Draft,
            'payment_status' => TransactionPaymentStatus::Draft,
            'total' => 100000,
            'balance_due' => 100000,
        ]);

        $data = [
            'amount' => 10000,
            'payment_method_id' => $this->paymentMethod->id,
        ];

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Pembayaran hanya bisa dilakukan untuk transaksi berstatus Unpaid atau Partial.');
        
        $this->service->recordPayment($transaction, $data, $this->user);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
