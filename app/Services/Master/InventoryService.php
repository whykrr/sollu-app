<?php

namespace App\Services\Master;

use App\Models\Master\InventoryItem;

class InventoryService
{
    public function createVariantInventory(array $data)
    {
        $item = InventoryItem::create([
            'business_id' => $data['business_id'],
            'item_type' => 'variant_sku',
            'product_id' => $data['product_id'],
            'sku' => $data['sku'] ?? null,
            'barcode' => $data['barcode'] ?? null,
            'track_inventory' => $data['track_inventory'],
            'current_stock' => $data['stock'] ?? 0,
        ]);

        if (isset($data['options'])) {
            $item->variantGroupOptions()->sync($data['options']);
        }

        if ($data['track_inventory'] && isset($data['stock']) && $data['stock'] > 0) {
            $item->movements()->create([
                'business_id' => $data['business_id'],
                'movement_type' => 'adjustment',
                'qty_change' => $data['stock'],
                'stock_before' => 0,
                'stock_after' => $data['stock'],
                'purchase_price' => $data['purchase_price'] ?? null,
                'description' => $data['description'] ?? 'Stok Awal Produk Baru',
                'created_by' => auth()->id(),
            ]);
        }

        return $item;
    }
}
