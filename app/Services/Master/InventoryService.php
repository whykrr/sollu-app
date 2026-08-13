<?php

namespace App\Services\Master;

use App\Models\Master\InventoryItem;

class InventoryService
{
    public function createVariantInventory(array $data)
    {
        $item = InventoryItem::create([
            'business_id' => $data['business_id'],
            'name' => $data['name'] ?? null,
            'item_type' => 'variant_sku',
            'product_id' => $data['product_id'],
            'sku' => $data['sku'] ?? null,
            'barcode' => $data['barcode'] ?? null,
            'track_inventory' => $data['track_inventory'],
            'min_stock' => $data['min_stock'] ?? 0,
            'uom_id' => $data['uom_id'] ?? null,
        ]);

        if (isset($data['options'])) {
            $item->variantGroupOptions()->sync($data['options']);
        }

        if ($item->track_inventory) {
            $this->syncInventoryBalances($item);
        }

        return $item;
    }

    public function syncInventoryBalances(InventoryItem $item)
    {
        if (! $item->track_inventory) {
            return;
        }

        // Fetch all active outlets for this business
        $outlets = \App\Models\Outlet::where('business_id', $item->business_id)
            ->where('is_active', true)
            ->get();

        foreach ($outlets as $outlet) {
            \App\Models\Inventory\InventoryBalance::firstOrCreate([
                'business_id' => $item->business_id,
                'outlet_id' => $outlet->id,
                'inventory_item_id' => $item->id,
            ], [
                'current_stock' => 0,
            ]);
        }
    }
}
