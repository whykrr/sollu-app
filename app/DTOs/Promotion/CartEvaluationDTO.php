<?php

namespace App\DTOs\Promotion;

use Carbon\Carbon;

readonly class CartEvaluationDTO
{
    /**
     * @param  array<CartItemDTO>  $items
     */
    public function __construct(
        public string $businessId,
        public ?string $outletId = null,
        public ?string $channel = 'pos',
        public array $items = [],
        public ?string $promoCode = null,
        public ?Carbon $evaluatedAt = null,
        public ?float $subtotal = null,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $items = [];
        if (isset($data['items']) && is_array($data['items'])) {
            foreach ($data['items'] as $item) {
                if ($item instanceof CartItemDTO) {
                    $items[] = $item;
                } elseif (is_array($item)) {
                    $items[] = CartItemDTO::fromArray($item);
                }
            }
        }

        $evaluatedAt = null;
        if (! empty($data['evaluated_at'])) {
            $evaluatedAt = $data['evaluated_at'] instanceof Carbon ? $data['evaluated_at'] : Carbon::parse($data['evaluated_at']);
        } else {
            $evaluatedAt = now();
        }

        $calculatedSubtotal = 0.0;
        foreach ($items as $item) {
            $calculatedSubtotal += $item->subtotal;
        }

        return new self(
            businessId: (string) ($data['business_id'] ?? ''),
            outletId: isset($data['outlet_id']) ? (string) $data['outlet_id'] : null,
            channel: (string) ($data['channel'] ?? 'pos'),
            items: $items,
            promoCode: ! empty($data['promo_code']) ? trim((string) $data['promo_code']) : null,
            evaluatedAt: $evaluatedAt,
            subtotal: isset($data['subtotal']) ? (float) $data['subtotal'] : $calculatedSubtotal,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'business_id' => $this->businessId,
            'outlet_id' => $this->outletId,
            'channel' => $this->channel,
            'items' => array_map(fn (CartItemDTO $item) => $item->toArray(), $this->items),
            'promo_code' => $this->promoCode,
            'evaluated_at' => $this->evaluatedAt?->toIso8601String(),
            'subtotal' => $this->subtotal,
        ];
    }
}
