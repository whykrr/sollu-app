<?php

namespace App\Services\Master;

use App\Models\Master\InventoryItem;

class InventoryService
{
    public function createVariantInventory(array $data)
    {
        $item = InventoryItem::create([
            'business_id'     => $data['business_id'],
            'item_type'       => 'variant_sku',
            'product_id'      => $data['product_id'],
            'sku'             => $data['sku'] ?? null,
            'barcode'         => $data['barcode'] ?? null,
            'track_inventory' => $data['track_inventory'],
            'min_stock'       => $data['min_stock'] ?? 0,
        ]);

        if (isset($data['options'])) {
            $item->variantGroupOptions()->sync($data['options']);
        }

        return $item;
    }
}
