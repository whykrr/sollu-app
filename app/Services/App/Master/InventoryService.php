<?php

namespace App\Services\App\Master;

use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryItem;
use App\Models\Master\ProductItem;
use App\Models\Outlet;

class InventoryService
{
    /**
     * Link or create an InventoryItem for a ProductItem and initialize balances.
     *
     * @param  array<string, mixed>  $data
     * @param  array<string>|null  $activeOutletIds
     */
    public function linkInventoryItem(ProductItem $productItem, array $data = [], ?array $activeOutletIds = null): InventoryItem
    {
        $existing = InventoryItem::where('business_id', $productItem->business_id)
            ->where('product_item_id', $productItem->id)
            ->first();

        $item = InventoryItem::updateOrCreate([
            'business_id' => $productItem->business_id,
            'product_item_id' => $productItem->id,
        ], [
            'name' => $productItem->name,
            'uom_id' => $productItem->uom_id ?? ($data['uom_id'] ?? null),
            'minimum_stock' => array_key_exists('min_stock', $data)
                ? ($data['min_stock'] ?? 0)
                : ($existing?->minimum_stock ?? 0),
            'is_active' => $productItem->is_active ?? true,
        ]);

        if ($productItem->track_inventory) {
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
            $outletIds = $item->productItem?->product()
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
