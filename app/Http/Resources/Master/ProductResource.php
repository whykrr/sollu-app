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
        $productItems = $this->relationLoaded('productItems') ? $this->productItems : collect();
        $inventoryItems = $productItems->pluck('inventoryItem')->filter();
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
        $minimumStock = $firstInvItem ? (float) ($firstInvItem->minimum_stock ?? 0) : 0.0;

        $outletsData = $this->relationLoaded('outlets')
            ? $this->outlets->map(fn ($o) => [
                'id' => $o->id,
                'name' => $o->name,
                'pivot' => [
                    'is_enabled' => (bool) ($o->pivot?->is_enabled ?? true),
                    'is_available' => (bool) ($o->pivot?->is_available ?? true),
                ],
            ])->all()
            : [];

        $formattedProductItems = [];
        foreach ($productItems as $item) {
            $invItem = $item->inventoryItem;
            $invItemId = $invItem?->id;
            $lockedOutlets = $invItemId && isset($lockedPairs[$invItemId])
                ? $lockedPairs[$invItemId]->pluck('outlet_id')->all()
                : [];
            $itemInitials = $invItemId && isset($initialMovements[$invItemId])
                ? $itemInitials = $initialMovements[$invItemId]->keyBy('outlet_id')
                : collect();

            $outletStocks = [];
            foreach ($outletsData as $outlet) {
                $outId = $outlet['id'];
                $isLocked = in_array($outId, $lockedOutlets, true);
                $initMov = $itemInitials->get($outId);

                $outletStocks[$outId] = [
                    'outlet_id' => $outId,
                    'outlet_name' => $outlet['name'] ?? '',
                    'can_edit_initial_stock' => ! $isLocked,
                    'initial_stock' => $initMov ? (float) $initMov->qty_change : 0.0,
                    'purchase_price' => $initMov ? (float) $initMov->unit_cost : 0.0,
                ];
            }

            $firstStock = ! empty($outletStocks) ? reset($outletStocks) : null;

            $formattedProductItems[] = [
                'id' => $item->id,
                'business_id' => $item->business_id,
                'product_id' => $item->product_id,
                'uom_id' => $item->uom_id,
                'item_type' => $item->item_type,
                'name' => $item->name,
                'variant_combination' => $item->variant_combination,
                'sku' => $item->sku,
                'barcode' => $item->barcode,
                'track_inventory' => (bool) $item->track_inventory,
                'is_show' => (bool) $item->is_show,
                'sellable' => (bool) $item->sellable,
                'is_active' => (bool) $item->is_active,
                'uom' => $item->relationLoaded('uom') && $item->uom ? [
                    'id' => $item->uom->id,
                    'name' => $item->uom->name,
                ] : null,
                'inventory_item' => $invItem ? [
                    'id' => $invItem->id,
                    'minimum_stock' => (float) ($invItem->minimum_stock ?? 0),
                ] : null,
                'initial_stocks_by_outlet' => $outletStocks,
                'can_edit_initial_stock' => empty($lockedOutlets),
                'initial_stock' => $firstStock['initial_stock'] ?? 0.0,
                'purchase_price' => $firstStock['purchase_price'] ?? 0.0,
            ];
        }

        $activeRecipe = null;
        if ($this->relationLoaded('activeRecipe') && $this->activeRecipe) {
            $activeRecipe = [
                'id' => $this->activeRecipe->id,
                'product_id' => $this->activeRecipe->product_id,
                'version_number' => $this->activeRecipe->version_number,
                'is_active' => (bool) $this->activeRecipe->is_active,
                'items' => $this->activeRecipe->relationLoaded('items')
                    ? $this->activeRecipe->items->map(fn ($it) => [
                        'id' => $it->id,
                        'product_item_id' => $it->product_item_id,
                        'qty' => (float) $it->qty,
                        'uom' => $it->uom ?? $it->productItem?->uom?->name ?? 'Pcs',
                    ])->all()
                    : [],
            ];
        }

        return [
            'id' => $this->id,
            'business_id' => $this->business_id,
            'product_category_id' => $this->product_category_id,
            'product_type' => is_object($this->product_type) ? $this->product_type->value : (string) $this->product_type,
            'has_variant' => (bool) $this->has_variant,
            'has_modifier' => (bool) $this->has_modifier,
            'has_recipe' => (bool) $this->has_recipe,
            'track_inventory' => (bool) $this->track_inventory,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'image_url' => $this->image_url,
            'cover_image_url' => $this->cover_image_url,
            'is_show' => (bool) $this->is_show,
            'sellable' => (bool) $this->sellable,
            'purchasable' => (bool) $this->purchasable,
            'minimum_stock' => $minimumStock,
            'category' => $this->relationLoaded('category') && $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->name,
            ] : null,
            'prices' => $this->relationLoaded('prices')
                ? $this->prices->map(fn ($p) => [
                    'id' => $p->id,
                    'product_id' => $p->product_id,
                    'product_item_id' => $p->product_item_id,
                    'outlet_id' => $p->outlet_id,
                    'amount' => (float) $p->amount,
                ])->all()
                : [],
            'outlets' => $outletsData,
            'product_items' => $formattedProductItems,
            'variant_groups' => $this->relationLoaded('variantGroups')
                ? $this->variantGroups->map(fn ($vg) => [
                    'id' => $vg->id,
                    'name' => $vg->name,
                    'sort_order' => $vg->sort_order,
                    'options' => $vg->relationLoaded('options')
                        ? $vg->options->map(fn ($opt) => [
                            'id' => $opt->id,
                            'name' => $opt->name,
                            'sort_order' => $opt->sort_order,
                        ])->all()
                        : [],
                ])->all()
                : [],
            'active_recipe' => $activeRecipe,
            'recipe_versions' => $activeRecipe ? [$activeRecipe] : [],
            'modifier_groups' => $this->relationLoaded('modifierGroups')
                ? $this->modifierGroups->map(fn ($mg) => [
                    'id' => $mg->id,
                    'name' => $mg->name,
                ])->all()
                : [],
            'images' => $this->relationLoaded('images')
                ? $this->images->map(fn ($img) => [
                    'id' => $img->id,
                    'product_id' => $img->product_id,
                    'product_item_id' => $img->product_item_id,
                    'image_url' => $img->image_url,
                    'sort_order' => $img->sort_order,
                ])->all()
                : [],
        ];
    }
}
