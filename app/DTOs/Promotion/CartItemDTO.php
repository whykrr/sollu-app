<?php

namespace App\DTOs\Promotion;

readonly class CartItemDTO
{
    public function __construct(
        public string $id,
        public string $productId,
        public string $productItemId,
        public ?string $categoryId = null,
        public float $quantity = 1.0,
        public float $unitPrice = 0.0,
        public float $subtotal = 0.0,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $quantity = (float) ($data['quantity'] ?? $data['qty'] ?? 1.0);
        $unitPrice = (float) ($data['unit_price'] ?? $data['price'] ?? 0.0);
        $subtotal = (float) ($data['subtotal'] ?? ($quantity * $unitPrice));

        return new self(
            id: (string) ($data['id'] ?? $data['item_id'] ?? $data['product_item_id'] ?? uniqid('item_')),
            productId: (string) ($data['product_id'] ?? ''),
            productItemId: (string) ($data['product_item_id'] ?? $data['inventory_item_id'] ?? $data['item_id'] ?? ''),
            categoryId: isset($data['category_id']) ? (string) $data['category_id'] : null,
            quantity: $quantity,
            unitPrice: $unitPrice,
            subtotal: $subtotal,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'product_id' => $this->productId,
            'product_item_id' => $this->productItemId,
            'category_id' => $this->categoryId,
            'quantity' => $this->quantity,
            'unit_price' => $this->unitPrice,
            'subtotal' => $this->subtotal,
        ];
    }
}
