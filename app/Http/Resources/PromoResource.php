<?php

namespace App\Http\Resources;

use App\Enums\PromotionApplicationMode;
use App\Enums\PromotionDiscountType;
use App\Enums\PromotionTargetScope;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PromoResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $discountType = $this->discount_type instanceof PromotionDiscountType ? $this->discount_type->value : $this->discount_type;
        $targetScope = $this->target_scope instanceof PromotionTargetScope ? $this->target_scope->value : $this->target_scope;
        $applicationMode = $this->application_mode instanceof PromotionApplicationMode ? $this->application_mode->value : $this->application_mode;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'application_mode' => $applicationMode,
            'promo_code' => $this->promo_code,
            'target_scope' => $targetScope,
            'target_type' => $targetScope, // Backwards compatibility
            'discount_type' => $discountType,
            'promo_type' => $discountType, // Backwards compatibility
            'discount_value' => (float) $this->discount_value,
            'max_discount' => $this->max_discount_amount ? (float) $this->max_discount_amount : null,
            'max_discount_amount' => $this->max_discount_amount ? (float) $this->max_discount_amount : null,
            'min_subtotal' => $this->min_subtotal ? (float) $this->min_subtotal : null,
            'min_quantity' => $this->min_quantity ? (float) $this->min_quantity : null,
            'applies_to_all_outlets' => (bool) $this->applies_to_all_outlets,
            'inventory_item_ids' => $this->relationLoaded('productItems') ? $this->productItems->pluck('id')->toArray() : [],
            'product_item_ids' => $this->relationLoaded('productItems') ? $this->productItems->pluck('id')->toArray() : [],
        ];
    }
}
