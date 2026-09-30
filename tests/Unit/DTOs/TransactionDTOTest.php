<?php

declare(strict_types=1);

namespace Tests\Unit\DTOs;

use App\DTOs\Transaction\CreateB2bTransactionDTO;
use App\DTOs\Transaction\CreateTransactionItemDTO;
use App\DTOs\Transaction\RecordPaymentDTO;
use App\Enums\PaymentTermEnum;
use App\Enums\SalesChannelEnum;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class TransactionDTOTest extends TestCase
{
    public function test_create_transaction_item_dto_instantiation(): void
    {
        $dto = new CreateTransactionItemDTO(
            productId: 'prod-123',
            qty: 5.5,
            price: 25000.0,
            productItemId: 'pi-456',
            inventoryItemId: 'inv-789',
            discountAmount: 5000.0,
            notes: 'Test Item Note'
        );

        $this->assertSame('prod-123', $dto->productId);
        $this->assertSame(5.5, $dto->qty);
        $this->assertSame(25000.0, $dto->price);
        $this->assertSame('pi-456', $dto->productItemId);
        $this->assertSame('inv-789', $dto->inventoryItemId);
        $this->assertSame(5000.0, $dto->discountAmount);
        $this->assertSame('Test Item Note', $dto->notes);
    }

    public function test_create_b2b_transaction_dto_instantiation_and_defaults(): void
    {
        $now = new DateTimeImmutable('2026-09-30 10:00:00');
        $dueDate = new DateTimeImmutable('2026-10-30 10:00:00');

        $item = new CreateTransactionItemDTO(
            productId: 'prod-1',
            qty: 2.0,
            price: 50000.0,
        );

        $dto = new CreateB2bTransactionDTO(
            outletId: 'outlet-uuid-1',
            channel: SalesChannelEnum::Wholesale,
            transactionDate: $now,
            paymentTerm: PaymentTermEnum::Credit,
            items: [$item],
            customerId: 'cust-uuid-1',
            dueDate: $dueDate,
            notes: 'Wholesale order',
            discountType: 'fixed',
            discountValue: 10000.0,
            shippingFee: 15000.0,
            serviceChargeAmount: 2000.0,
            paymentTermCode: 'net_30'
        );

        $this->assertSame('outlet-uuid-1', $dto->outletId);
        $this->assertSame(SalesChannelEnum::Wholesale, $dto->channel);
        $this->assertSame($now, $dto->transactionDate);
        $this->assertSame(PaymentTermEnum::Credit, $dto->paymentTerm);
        $this->assertCount(1, $dto->items);
        $this->assertSame('cust-uuid-1', $dto->customerId);
        $this->assertSame($dueDate, $dto->dueDate);
        $this->assertSame('Wholesale order', $dto->notes);
        $this->assertSame('fixed', $dto->discountType);
        $this->assertSame(10000.0, $dto->discountValue);
        $this->assertSame(15000.0, $dto->shippingFee);
        $this->assertSame(2000.0, $dto->serviceChargeAmount);
        $this->assertSame('net_30', $dto->paymentTermCode);
    }

    public function test_record_payment_dto_instantiation(): void
    {
        $paymentDate = new DateTimeImmutable('2026-09-30 12:00:00');

        $dto = new RecordPaymentDTO(
            paymentMethodId: 'pm-uuid-1',
            amount: 100000.0,
            paymentDate: $paymentDate,
            changeAmount: 5000.0,
            paymentReference: 'TRF-BCA-9988',
            notes: 'Pembayaran DP'
        );

        $this->assertSame('pm-uuid-1', $dto->paymentMethodId);
        $this->assertSame(100000.0, $dto->amount);
        $this->assertSame($paymentDate, $dto->paymentDate);
        $this->assertSame(5000.0, $dto->changeAmount);
        $this->assertSame('TRF-BCA-9988', $dto->paymentReference);
        $this->assertSame('Pembayaran DP', $dto->notes);
    }
}
