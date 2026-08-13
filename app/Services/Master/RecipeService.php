<?php

namespace App\Services\Master;

use App\Models\Master\Product;

class RecipeService
{
    private AuditLogService $auditLogService;

    public function __construct(AuditLogService $auditLogService)
    {
        $this->auditLogService = $auditLogService;
    }

    public function syncRecipe(Product $product, array $items)
    {
        // Deactivate all previous versions
        $product->recipeVersions()->update(['is_active' => false]);

        $nextVersion = $product->recipeVersions()->max('version_number') + 1;

        $recipe = $product->recipeVersions()->create([
            'version_number' => $nextVersion,
            'is_active' => true,
            'effective_from' => now(),
        ]);

        foreach ($items as $item) {
            $recipe->items()->create([
                'inventory_item_id' => $item['inventory_item_id'],
                'qty' => $item['qty'],
                'uom' => $item['uom'],
            ]);
        }

        $this->auditLogService->log($product->business_id, 'recipe', $recipe->id, 'created', null, $recipe->load('items')->toArray());

        return $recipe;
    }
}
