<?php

namespace App\Http\Resources\Master;

use App\Enums\InventoryMovementType;
use App\Models\Inventory\InventoryMovement;
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
        $data = parent::toArray($request);

        $inventoryItems = $this->productItems
            ? $this->productItems->pluck('inventoryItem')->filter()
            : collect();
        $inventoryItemIds = $inventoryItems->pluck('id')->all();

        $lockedPairs = ! empty($inventoryItemIds)
            ? InventoryMovement::whereIn('inventory_item_id', $inventoryItemIds)
                ->where('movement_type', '!=', InventoryMovementType::InitialStock)
                ->select('inventory_item_id', 'outlet_id')
                ->distinct()
                ->get()
                ->groupBy('inventory_item_id')
            : collect();

        $initialMovements = ! empty($inventoryItemIds)
            ? InventoryMovement::whereIn('inventory_item_id', $inventoryItemIds)
                ->where('movement_type', InventoryMovementType::InitialStock)
                ->select('inventory_item_id', 'outlet_id', 'qty_change', 'unit_cost')
                ->get()
                ->groupBy('inventory_item_id')
            : collect();

        $firstInvItem = $inventoryItems->first();
        $data['minimum_stock'] = $firstInvItem ? (float) ($firstInvItem->minimum_stock ?? 0) : 0.0;

        if (isset($data['product_items']) && is_array($data['product_items'])) {
            foreach ($data['product_items'] as &$pItem) {
                $invItemId = $pItem['inventory_item']['id'] ?? null;
                $lockedOutlets = $invItemId && isset($lockedPairs[$invItemId])
                    ? $lockedPairs[$invItemId]->pluck('outlet_id')->all()
                    : [];
                $itemInitials = $invItemId && isset($initialMovements[$invItemId])
                    ? $initialMovements[$invItemId]->keyBy('outlet_id')
                    : collect();

                $outletStocks = [];
                if (! empty($data['outlets'])) {
                    foreach ($data['outlets'] as $outlet) {
                        $outId = $outlet['id'];
                        $isLocked = in_array($outId, $lockedOutlets);
                        $initMov = $itemInitials->get($outId);

                        $outletStocks[$outId] = [
                            'outlet_id' => $outId,
                            'outlet_name' => $outlet['name'] ?? '',
                            'can_edit_initial_stock' => ! $isLocked,
                            'initial_stock' => $initMov ? (float) $initMov->qty_change : 0.0,
                            'purchase_price' => $initMov ? (float) $initMov->unit_cost : 0.0,
                        ];
                    }
                }

                $pItem['initial_stocks_by_outlet'] = $outletStocks;
                $pItem['can_edit_initial_stock'] = empty($lockedOutlets);
                $firstStock = ! empty($outletStocks) ? reset($outletStocks) : null;
                $pItem['initial_stock'] = $firstStock['initial_stock'] ?? 0.0;
                $pItem['purchase_price'] = $firstStock['purchase_price'] ?? 0.0;
            }
        }

        return $data;
    }
}
