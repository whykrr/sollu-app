<?php

declare(strict_types=1);

namespace App\DTOs\Transaction;

use App\Enums\PaymentTermEnum;
use App\Enums\SalesChannelEnum;
use DateTimeInterface;

readonly class CreateB2bTransactionDTO
{
    /**
     * @param  array<int, CreateTransactionItemDTO>  $items
     */
    public function __construct(
        public string $outletId,
        public SalesChannelEnum $channel,
        public DateTimeInterface $transactionDate,
        public PaymentTermEnum $paymentTerm,
        public array $items,
        public ?string $customerId = null,
        public ?DateTimeInterface $dueDate = null,
        public ?string $notes = null,
        public ?string $discountType = null,
        public float $discountValue = 0.0,
        public float $taxAmount = 0.0,
        public float $shippingFee = 0.0,
        public float $serviceChargeAmount = 0.0,
        public ?string $paymentTermCode = 'custom',
        public ?string $promoCode = null,
    ) {}
}
