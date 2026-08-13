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
        $outletId = $request->query('outlet_id');
        $price = 0;
        if ($this->relationLoaded('prices')) {
            $outletPrice = $outletId ? $this->prices->firstWhere('outlet_id', $outletId) : null;
            $generalPrice = $this->prices->firstWhere('outlet_id', null);
            $price = $outletPrice?->amount ?? $generalPrice?->amount ?? 0;
        }

        $currentStock = 0;
        if ($this->relationLoaded('inventoryItems')) {
            foreach ($this->inventoryItems as $item) {
                if ($item->relationLoaded('balances')) {
                    $balance = $item->balances->first();
                    if ($balance) {
                        $currentStock += floatval($balance->current_stock);
                    }
                }
            }
        }

        $activePromos = [];
        if ($this->relationLoaded('activePromos')) {
            $activePromos = $this->activePromos->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'promo_type' => is_object($p->promo_type) ? $p->promo_type->value : $p->promo_type,
                'target_type' => is_object($p->target_type) ? $p->target_type->value : $p->target_type,
                'discount_value' => floatval($p->discount_value),
                'max_discount' => $p->max_discount ? floatval($p->max_discount) : null,
            ])->toArray();
        }

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
            'price' => floatval($price),
            'current_stock' => $currentStock,
            'active_promos' => $activePromos,
        ];
    }
}
