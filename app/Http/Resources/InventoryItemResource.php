<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventoryItemResource extends JsonResource
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
            'sku' => $this->sku,
            'barcode' => $this->barcode,
            'item_type' => $this->item_type,
            'track_inventory' => $this->track_inventory,
            'is_active' => $this->is_active,
            'uom' => $this->whenLoaded('uom', function () {
                return [
                    'id' => $this->uom->id,
                    'name' => $this->uom->name,
                ];
            }),
            'current_stock' => $this->when(isset($this->current_stock), $this->current_stock),
        ];
    }
}
