<?php

declare(strict_types=1);

namespace Tests\Feature\Services\Transaction;

use App\DTOs\Transaction\RecordPaymentDTO;
use App\Enums\PaymentTermEnum;
use App\Enums\SalesChannelEnum;
use App\Enums\TransactionPaymentStatus;
use App\Enums\TransactionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Master\PaymentMethod;
use App\Models\Outlet;
use App\Models\Sales\Transaction;
use App\Models\Sales\TransactionInvoice;
use App\Models\User;
use App\Services\App\Transaction\TransactionPaymentService;
use DateTimeImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class TransactionPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;

    protected Outlet $outlet;

    protected User $user;

    protected PaymentMethod $cashMethod;

    protected PaymentMethod $bankMethod;

    protected TransactionPaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $this->business = Business::create([
            'name' => 'Payment Merchant',
            'owner_name' => 'Owner',
            'email' => 'payment_'.uniqid().'@test.com',
            'phone' => '081299991',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $this->outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Payment Test Outlet',
        ]);

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Finance Staff',
            'email' => 'finance_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->cashMethod = PaymentMethod::create([
            'business_id' => $this->business->id,
            'name' => 'Kas Tunai',
            'code' => 'cash',
            'type' => 'cash',
            'is_active' => true,
        ]);

        $this->bankMethod = PaymentMethod::create([
            'business_id' => $this->business->id,
            'name' => 'Transfer Bank Mandiri',
            'code' => 'mandiri_transfer',
            'type' => 'bank_transfer',
            'is_active' => true,
        ]);

        $this->paymentService = app(TransactionPaymentService::class);
    }

    private function createUnpaidTransaction(float $total = 1000000.0): Transaction
    {
        $transaction = new Transaction;
        $transaction->outlet_id = $this->outlet->id;
        $transaction->channel = SalesChannelEnum::Wholesale;
        $transaction->transaction_number = 'TRX/202609/0001';
        $transaction->transaction_date = now();
        $transaction->subtotal = $total;
        $transaction->total = $total;
        $transaction->total_paid = 0.0;
        $transaction->balance_due = $total;
        $transaction->status = TransactionStatus::Unpaid;
        $transaction->payment_status = TransactionPaymentStatus::Unpaid;
        $transaction->created_by = $this->user->id;
        $transaction->updated_by = $this->user->id;
        $transaction->save();

        $invoice = new TransactionInvoice;
        $invoice->transaction_id = $transaction->id;
        $invoice->invoice_number = 'INV/202609/0001';
        $invoice->invoice_date = now();
        $invoice->payment_term = PaymentTermEnum::Credit;
        $invoice->status = TransactionStatus::Unpaid;
        $invoice->created_by = $this->user->id;
        $invoice->save();

        return $transaction;
    }

    public function test_it_records_down_payment_and_updates_status_to_partial(): void
    {
        $transaction = $this->createUnpaidTransaction(1000000.0);

        $dto = new RecordPaymentDTO(
            paymentMethodId: $this->bankMethod->id,
            amount: 400000.0,
            paymentDate: new DateTimeImmutable('2026-09-30 14:00:00'),
            changeAmount: 0.0,
            paymentReference: 'DP-MANDIRI-01',
            notes: 'Uang Muka 40%'
        );

        $updated = $this->paymentService->recordPayment($transaction, $dto, $this->user);

        $this->assertEquals(TransactionStatus::Partial, $updated->status);
        $this->assertEquals(TransactionPaymentStatus::Partial, $updated->payment_status);
        $this->assertEquals(400000.0, (float) $updated->total_paid);
        $this->assertEquals(600000.0, (float) $updated->balance_due);

        $this->assertCount(1, $updated->payments);
        $payment = $updated->payments->first();
        $this->assertEquals(400000.0, (float) $payment->amount);
        $this->assertEquals('DP-MANDIRI-01', $payment->payment_reference);

        // Invoice status synced
        $this->assertEquals(TransactionStatus::Partial, $updated->invoice->status);
    }

    public function test_it_records_second_installment_and_marks_paid_when_balance_cleared(): void
    {
        $transaction = $this->createUnpaidTransaction(1000000.0);

        // First payment (400.000)
        $dto1 = new RecordPaymentDTO(
            paymentMethodId: $this->bankMethod->id,
            amount: 400000.0,
            paymentDate: new DateTimeImmutable('2026-09-30 14:00:00'),
        );
        $this->paymentService->recordPayment($transaction, $dto1, $this->user);

        // Second payment (600.000 pelunasan)
        $dto2 = new RecordPaymentDTO(
            paymentMethodId: $this->cashMethod->id,
            amount: 600000.0,
            paymentDate: new DateTimeImmutable('2026-10-05 10:00:00'),
            notes: 'Pelunasan Akhir'
        );
        $paid = $this->paymentService->recordPayment($transaction, $dto2, $this->user);

        $this->assertEquals(TransactionStatus::Paid, $paid->status);
        $this->assertEquals(TransactionPaymentStatus::Paid, $paid->payment_status);
        $this->assertEquals(1000000.0, (float) $paid->total_paid);
        $this->assertEquals(0.0, (float) $paid->balance_due);
        $this->assertCount(2, $paid->payments);

        // Invoice status synced
        $this->assertEquals(TransactionStatus::Paid, $paid->invoice->status);
    }

    public function test_it_handles_overpayment_with_cash_change(): void
    {
        $transaction = $this->createUnpaidTransaction(250000.0);

        $dto = new RecordPaymentDTO(
            paymentMethodId: $this->cashMethod->id,
            amount: 300000.0,
            paymentDate: new DateTimeImmutable('2026-09-30 15:00:00'),
            changeAmount: 50000.0,
            notes: 'Bayar 300rb kembali 50rb'
        );

        $paid = $this->paymentService->recordPayment($transaction, $dto, $this->user);

        $this->assertEquals(TransactionStatus::Paid, $paid->status);
        $this->assertEquals(TransactionPaymentStatus::Paid, $paid->payment_status);
        // Net paid = 300.000 - 50.000 = 250.000
        $this->assertEquals(250000.0, (float) $paid->total_paid);
        $this->assertEquals(0.0, (float) $paid->balance_due);
    }

    public function test_it_throws_exception_when_paying_draft_transaction(): void
    {
        $transaction = new Transaction;
        $transaction->outlet_id = $this->outlet->id;
        $transaction->channel = SalesChannelEnum::Wholesale;
        $transaction->transaction_number = 'TRX/202609/DRAFT';
        $transaction->transaction_date = now();
        $transaction->total = 100000.0;
        $transaction->status = TransactionStatus::Draft;
        $transaction->payment_status = TransactionPaymentStatus::Draft;
        $transaction->created_by = $this->user->id;
        $transaction->updated_by = $this->user->id;
        $transaction->save();

        $dto = new RecordPaymentDTO(
            paymentMethodId: $this->cashMethod->id,
            amount: 100000.0,
            paymentDate: now()
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Tidak dapat mencatat pembayaran pada draf.');

        $this->paymentService->recordPayment($transaction, $dto, $this->user);
    }

    public function test_it_throws_exception_when_paying_cancelled_transaction(): void
    {
        $transaction = $this->createUnpaidTransaction(100000.0);
        $transaction->status = TransactionStatus::Cancel;
        $transaction->save();

        $dto = new RecordPaymentDTO(
            paymentMethodId: $this->cashMethod->id,
            amount: 50000.0,
            paymentDate: now()
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Transaksi sudah dibatalkan.');

        $this->paymentService->recordPayment($transaction, $dto, $this->user);
    }

    public function test_it_throws_exception_when_paying_already_paid_transaction(): void
    {
        $transaction = $this->createUnpaidTransaction(100000.0);
        $transaction->payment_status = TransactionPaymentStatus::Paid;
        $transaction->status = TransactionStatus::Paid;
        $transaction->save();

        $dto = new RecordPaymentDTO(
            paymentMethodId: $this->cashMethod->id,
            amount: 50000.0,
            paymentDate: now()
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Transaksi sudah lunas.');

        $this->paymentService->recordPayment($transaction, $dto, $this->user);
    }

    public function test_it_throws_exception_when_amount_is_zero_or_negative(): void
    {
        $transaction = $this->createUnpaidTransaction(100000.0);

        $dto = new RecordPaymentDTO(
            paymentMethodId: $this->cashMethod->id,
            amount: 0.0,
            paymentDate: now()
        );

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Nominal pembayaran harus lebih dari 0.');

        $this->paymentService->recordPayment($transaction, $dto, $this->user);
    }
}
