<?php

namespace App\Http\Resources;

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
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'promo_type' => is_object($this->promo_type) ? $this->promo_type->value : $this->promo_type,
            'target_type' => is_object($this->target_type) ? $this->target_type->value : $this->target_type,
            'discount_value' => floatval($this->discount_value),
            'max_discount' => $this->max_discount ? floatval($this->max_discount) : null,
            'applies_to_all_outlets' => $this->applies_to_all_outlets,
            'product_ids' => $this->relationLoaded('products') ? $this->products->pluck('id')->toArray() : [],
        ];
    }
}
