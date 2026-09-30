<?php

namespace App\DTOs\Promotion;

readonly class DiscountEvaluationResultDTO
{
    /**
     * @param  array<AppliedPromotionDTO>  $appliedPromotions
     * @param  array<string, float>  $itemDiscounts  Map of item_id => discount_amount
     */
    public function __construct(
        public float $originalSubtotal,
        public float $totalDiscount,
        public float $finalSubtotal,
        public array $appliedPromotions = [],
        public array $itemDiscounts = [],
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $appliedPromotions = [];
        if (isset($data['applied_promotions']) && is_array($data['applied_promotions'])) {
            foreach ($data['applied_promotions'] as $promo) {
                if ($promo instanceof AppliedPromotionDTO) {
                    $appliedPromotions[] = $promo;
                } elseif (is_array($promo)) {
                    $appliedPromotions[] = AppliedPromotionDTO::fromArray($promo);
                }
            }
        }

        return new self(
            originalSubtotal: (float) ($data['original_subtotal'] ?? 0.0),
            totalDiscount: (float) ($data['total_discount'] ?? 0.0),
            finalSubtotal: (float) ($data['final_subtotal'] ?? 0.0),
            appliedPromotions: $appliedPromotions,
            itemDiscounts: (array) ($data['item_discounts'] ?? []),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'original_subtotal' => $this->originalSubtotal,
            'total_discount' => $this->totalDiscount,
            'final_subtotal' => $this->finalSubtotal,
            'applied_promotions' => array_map(fn (AppliedPromotionDTO $p) => $p->toArray(), $this->appliedPromotions),
            'item_discounts' => $this->itemDiscounts,
        ];
    }
}
