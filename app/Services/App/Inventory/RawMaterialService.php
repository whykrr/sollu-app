<?php

namespace App\Services\App\Inventory;

use App\Enums\InventoryMovementType;
use App\Models\Business;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryItem;
use App\Models\Master\ProductItem;
use App\Models\Outlet;
use Illuminate\Support\Facades\DB;

class RawMaterialService
{
    public function __construct(
        protected ?InventoryCostingService $costingService = null
    ) {
        $this->costingService = $costingService ?? app(InventoryCostingService::class);
    }

    /**
     * Create a new raw material and initialize balances for active outlets.
     */
    public function createRawMaterial(array $data, Business $business): InventoryItem
    {
        return DB::transaction(function () use ($data, $business) {
            $productItem = ProductItem::create([
                'business_id' => $business->id,
                'item_type' => 'raw_material',
                'name' => $data['name'],
                'sku' => $data['sku'] ?? null,
                'barcode' => $data['barcode'] ?? null,
                'track_inventory' => true,
                'is_show' => false,
                'sellable' => false,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $item = InventoryItem::create([
                'business_id' => $business->id,
                'product_item_id' => $productItem->id,
                'name' => $data['name'],
                'uom_id' => $data['uom_id'] ?? null,
                'minimum_stock' => $data['minimum_stock'] ?? 0,
                'is_active' => $data['is_active'] ?? true,
            ]);

            // Initialize balances for all active outlets
            $outlets = $business->outlets()->active()->get();
            foreach ($outlets as $outlet) {
                InventoryBalance::firstOrCreate([
                    'business_id' => $business->id,
                    'outlet_id' => $outlet->id,
                    'inventory_item_id' => $item->id,
                ], [
                    'current_stock' => 0,
                    'minimum_stock' => $data['minimum_stock'] ?? 0,
                ]);
            }

            // Catat stok awal jika diisi
            $qty = (float) ($data['initial_stock'] ?? 0);
            $cost = (float) ($data['purchase_price'] ?? $data['initial_cost'] ?? 0);

            if ($qty > 0) {
                $targetOutletId = $data['outlet_id'] ?? $outlets->first()?->id;
                $targetOutlet = $targetOutletId ? Outlet::find($targetOutletId) : $outlets->first();

                if ($targetOutlet) {
                    $this->costingService->recordIncomingStock(
                        business: $business,
                        outlet: $targetOutlet,
                        item: $item,
                        qty: $qty,
                        unitCost: $cost,
                        movementType: InventoryMovementType::InitialStock,
                        reference: null,
                        description: 'Stok Awal Bahan Baku Baru',
                        user: auth()->user()
                    );
                }
            }

            return $item->fresh('productItem');
        });
    }

    /**
     * Update an existing raw material.
     */
    public function updateRawMaterial(InventoryItem $item, array $data): InventoryItem
    {
        return DB::transaction(function () use ($item, $data) {
            if ($item->productItem) {
                $item->productItem->update(array_merge([
                    'track_inventory' => true,
                    'is_show' => false,
                    'sellable' => false,
                ], array_filter([
                    'name' => $data['name'] ?? null,
                    'sku' => $data['sku'] ?? null,
                    'barcode' => $data['barcode'] ?? null,
                    'is_active' => $data['is_active'] ?? null,
                ], fn ($val) => $val !== null)));
            }

            $item->update(array_filter([
                'name' => $data['name'] ?? null,
                'uom_id' => array_key_exists('uom_id', $data) ? $data['uom_id'] : null,
                'minimum_stock' => $data['minimum_stock'] ?? null,
                'is_active' => $data['is_active'] ?? null,
            ], fn ($val) => $val !== null));

            return $item->fresh('productItem');
        });
    }
}
