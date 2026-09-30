<?php

declare(strict_types=1);

namespace App\DTOs\Transaction;

readonly class CreateTransactionItemDTO
{
    public function __construct(
        public string $productId,
        public float $qty,
        public float $price,
        public ?string $productItemId = null,
        public ?string $inventoryItemId = null,
        public float $discountAmount = 0.0,
        public ?string $notes = null,
    ) {}
}
