<?php

namespace App\Services\Inventory;

use App\Models\Business;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryItem;
use Illuminate\Support\Facades\DB;

class RawMaterialService
{
    /**
     * Create a new raw material and initialize balances for active outlets.
     *
     * @param array $data
     * @param Business $business
     * @return InventoryItem
     */
    public function createRawMaterial(array $data, Business $business): InventoryItem
    {
        return DB::transaction(function () use ($data, $business) {
            $data['business_id'] = $business->id;
            $data['item_type']   = 'raw_material';

            $item = InventoryItem::create($data);

            // Initialize balances for all active outlets
            $outlets = $business->outlets()->active()->get();
            foreach ($outlets as $outlet) {
                InventoryBalance::create([
                    'business_id'       => $business->id,
                    'outlet_id'         => $outlet->id,
                    'inventory_item_id' => $item->id,
                    'current_stock'     => 0,
                ]);
            }

            return $item;
        });
    }

    /**
     * Update an existing raw material.
     *
     * @param InventoryItem $item
     * @param array $data
     * @return InventoryItem
     */
    public function updateRawMaterial(InventoryItem $item, array $data): InventoryItem
    {
        $item->update($data);
        return $item;
    }
}
