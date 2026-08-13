<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
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
            'code' => $this->code,
            'product_type' => $this->product_type,
            'description' => $this->description,
            'cover_image_url' => $this->cover_image_url,
            'has_variant' => $this->has_variant,
            'track_inventory' => $this->track_inventory,
            'is_show' => $this->is_show,
            'sellable' => $this->sellable,
            'purchasable' => $this->purchasable,
        ];
    }
}
