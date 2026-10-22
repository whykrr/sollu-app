<?php

namespace App\Services\App\Master;

use App\Models\Master\Product;
use App\Models\Master\RecipeVersion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RecipeService
{
    public function __construct(
        protected AuditLogService $auditLogService
    ) {}

    public function syncRecipe(Product $product, array $items, ?string $productItemId = null): RecipeVersion
    {
        return DB::transaction(function () use ($product, $items, $productItemId) {
            $query = $product->recipeVersions();
            if ($productItemId) {
                $query->where('product_item_id', $productItemId);
            } else {
                $query->whereNull('product_item_id');
            }
            $query->update(['is_active' => false]);

            $nextVersion = ($product->recipeVersions()->max('version_number') ?? 0) + 1;

            $recipe = $product->recipeVersions()->create([
                'product_item_id' => $productItemId,
                'version_number' => $nextVersion,
                'is_active' => true,
                'effective_from' => now(),
            ]);

            foreach ($items as $item) {
                $recipe->items()->create([
                    'product_item_id' => $item['product_item_id'],
                    'qty' => $item['qty'],
                    'uom' => $item['uom'],
                ]);
            }

            $product->update(['has_recipe' => true]);

            $this->auditLogService->log(
                $product->business_id,
                'recipe',
                $recipe->id,
                'created',
                null,
                $recipe->load('items')->toArray()
            );

            return $recipe;
        });
    }

    public function syncVariantRecipes(Product $product, array $recipes): Collection
    {
        $synced = collect();
        foreach ($recipes as $entry) {
            $productItemId = $entry['product_item_id'] ?? null;
            $items = $entry['items'] ?? [];
            if (! empty($items)) {
                $synced->push($this->syncRecipe($product, $items, $productItemId));
            }
        }

        return $synced;
    }

    public function calculateEstimatedCost(RecipeVersion $recipe, ?string $outletId = null): float
    {
        $totalCost = 0.0;
        $recipe->loadMissing('items.productItem.inventoryItem.balances');

        foreach ($recipe->items as $item) {
            $balances = $item->productItem?->inventoryItem?->balances;
            if (! $balances || $balances->isEmpty()) {
                continue;
            }

            $effectiveCost = $outletId
                ? ($balances->firstWhere('outlet_id', $outletId)?->average_cost ?? 0.0)
                : ($balances->avg('average_cost') ?? 0.0);

            $totalCost += ((float) $item->qty * (float) $effectiveCost);
        }

        return round($totalCost, 2);
    }
}
