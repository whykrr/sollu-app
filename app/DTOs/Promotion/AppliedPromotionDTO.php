<?php

namespace App\DTOs\Promotion;

readonly class AppliedPromotionDTO
{
    /**
     * @param  array<string>  $affectedItemIds
     */
    public function __construct(
        public string $promotionId,
        public string $promotionName,
        public ?string $promoCode = null,
        public string $discountType = 'percentage',
        public float $discountValue = 0.0,
        public float $discountAmount = 0.0,
        public string $allocationLevel = 'transaction',
        public string $targetScope = 'transaction',
        public array $affectedItemIds = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            promotionId: (string) ($data['promotion_id'] ?? $data['id'] ?? ''),
            promotionName: (string) ($data['promotion_name'] ?? $data['name'] ?? ''),
            promoCode: isset($data['promo_code']) ? (string) $data['promo_code'] : null,
            discountType: (string) ($data['discount_type'] ?? 'percentage'),
            discountValue: (float) ($data['discount_value'] ?? 0.0),
            discountAmount: (float) ($data['discount_amount'] ?? 0.0),
            allocationLevel: (string) ($data['allocation_level'] ?? 'transaction'),
            targetScope: (string) ($data['target_scope'] ?? 'transaction'),
            affectedItemIds: (array) ($data['affected_item_ids'] ?? []),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'promotion_id' => $this->promotionId,
            'promotion_name' => $this->promotionName,
            'promo_code' => $this->promoCode,
            'discount_type' => $this->discountType,
            'discount_value' => $this->discountValue,
            'discount_amount' => $this->discountAmount,
            'allocation_level' => $this->allocationLevel,
            'target_scope' => $this->targetScope,
            'affected_item_ids' => $this->affectedItemIds,
        ];
    }
}
