<?php

namespace App\Services\App\Master;

use App\Models\Inventory\InventoryBalance;
use App\Models\Master\InventoryItem;
use App\Models\Outlet;

class InventoryService
{
    /**
     * Create variant inventory item and initialize balances for active outlets.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string>|null  $activeOutletIds
     */
    public function createVariantInventory(array $data, ?array $activeOutletIds = null): InventoryItem
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
            $this->syncInventoryBalances($item, $activeOutletIds);
        }

        return $item;
    }

    /**
     * Sync inventory balances for specified active outlets or active product outlets.
     * Note: Non-active outlets are NOT deleted to preserve historical ledger & valuation data.
     *
     * @param  array<string>|null  $targetOutletIds
     */
    public function syncInventoryBalances(InventoryItem $item, ?array $targetOutletIds = null): void
    {
        if (! $item->track_inventory) {
            return;
        }

        if ($targetOutletIds !== null) {
            $outletIds = array_values(array_filter($targetOutletIds));
        } elseif ($item->product_id) {
            // Load outlets enabled for this product
            $outletIds = $item->product()
                ->first()
                ?->outlets()
                ->wherePivot('is_enabled', true)
                ->pluck('outlets.id')
                ->all() ?? [];
        } else {
            // Fallback for standalone / raw material items: all active business outlets
            $outletIds = Outlet::query()
                ->where('business_id', $item->business_id)
                ->where('is_active', true)
                ->pluck('id')
                ->all();
        }

        foreach ($outletIds as $outletId) {
            InventoryBalance::firstOrCreate([
                'business_id' => $item->business_id,
                'outlet_id' => $outletId,
                'inventory_item_id' => $item->id,
            ], [
                'current_stock' => 0,
            ]);
        }
    }
}
