<?php

declare(strict_types=1);

namespace App\DTOs\Transaction;

use DateTimeInterface;

readonly class RecordPaymentDTO
{
    public function __construct(
        public string $paymentMethodId,
        public float $amount,
        public DateTimeInterface $paymentDate,
        public float $changeAmount = 0.0,
        public ?string $paymentReference = null,
        public ?string $notes = null,
    ) {}
}
