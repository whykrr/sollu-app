<?php

namespace App\Services\App\Master;

use App\Enums\FeatureEnum;
use App\Enums\InventoryMovementType;
use App\Enums\ProductTypeEnum;
use App\Models\Business;
use App\Models\Inventory\InventoryItem;
use App\Models\Master\Product;
use App\Models\Master\ProductItem;
use App\Models\Outlet;
use App\Services\App\Inventory\InventoryCostingService;
use App\Services\Core\ImageOptimizerService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductService
{
    public function __construct(
        protected AuditLogService $auditLogService,
        protected InventoryService $inventoryService,
        protected RecipeService $recipeService,
        protected ?ImageOptimizerService $imageOptimizerService = null,
        protected ?InventoryCostingService $costingService = null
    ) {
        $this->imageOptimizerService = $imageOptimizerService ?? app(ImageOptimizerService::class);
        $this->costingService = $costingService ?? app(InventoryCostingService::class);
    }

    /**
     * Create a basic, service, or bundle product.
     */
    public function createProduct(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $productType = $data['product_type'] ?? 'basic';
            if ($productType === 'service') {
                $data['track_inventory'] = false;
                $data['has_variant'] = false;
                $data['has_recipe'] = false;
            } elseif ($productType === 'bundle') {
                $data['track_inventory'] = false;
                $data['has_variant'] = false;
                $data['has_modifier'] = false;
                $data['has_recipe'] = false;
            }

            // 1. Causality Rule: sellable = false => is_show = false
            $sellable = (bool) ($data['sellable'] ?? true);
            $isShow = $sellable ? (bool) ($data['is_show'] ?? true) : false;

            // 2. Feature Gating Resolution
            $business = Business::findCached($data['business_id']) ?? Business::find($data['business_id']);
            $hasActiveSub = $business && $business->getActiveSubscriptionWithPlan();

            $canUseVariant = ! $hasActiveSub || $business->hasFeature(FeatureEnum::PRODUCT_VARIANT);
            $hasVariant = $canUseVariant && ($data['has_variant'] ?? false);

            $canUseRecipe = ! $hasActiveSub || $business->hasFeature(FeatureEnum::PRODUCT_BOM);
            $hasRecipe = $canUseRecipe && ($data['has_recipe'] ?? false);

            $canUseModifier = ! $hasActiveSub || $business->hasFeature(FeatureEnum::PRODUCT_MODIFIER);
            $hasModifier = $canUseModifier && ($data['has_modifier'] ?? false);

            $product = Product::create([
                'business_id' => $data['business_id'],
                'product_category_id' => $data['product_category_id'] ?? null,
                'product_type' => $productType,
                'has_variant' => $hasVariant,
                'has_modifier' => $hasModifier,
                'has_recipe' => $hasRecipe,
                'track_inventory' => (bool) ($data['track_inventory'] ?? false),
                'code' => $data['code'] ?? null,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'image_url' => $data['image_url'] ?? null,
                'is_show' => $isShow,
                'sellable' => $sellable,
                'purchasable' => (bool) ($data['purchasable'] ?? false),
            ]);

            // 3. Outlets Sync & Resolution
            $activeOutletIds = $this->syncProductOutlets($product, $data['outlets'] ?? null);

            // 4. Unified Item Pattern
            if (! $product->has_variant) {
                $defaultSku = ! empty($data['code']) ? $data['code'] : ($product->code ?: ('PRD-'.strtoupper(Str::random(6))));

                $defaultItem = ProductItem::create([
                    'business_id' => $product->business_id,
                    'product_id' => $product->id,
                    'uom_id' => $product->track_inventory ? ($data['uom_id'] ?? null) : null,
                    'item_type' => 'variant_sku',
                    'name' => $product->name,
                    'variant_combination' => null, // INVARIANT: null for single products
                    'sku' => $defaultSku,
                    'barcode' => $data['barcode'] ?? null,
                    'track_inventory' => $product->track_inventory,
                    'is_show' => $product->is_show,
                    'sellable' => $product->sellable,
                    'is_active' => true,
                ]);

                // Decoupled Inventory: ONLY link when track_inventory is true
                if ($product->track_inventory) {
                    $invItem = $this->inventoryService->linkInventoryItem($defaultItem, [
                        'uom_id' => $data['uom_id'] ?? null,
                        'min_stock' => $data['minimum_stock'] ?? ($data['min_stock'] ?? 0),
                    ], $activeOutletIds);

                    $this->recordInitialStocksForProductItem($product, $invItem, $data, $activeOutletIds);
                }

                // Base Price linked to default ProductItem
                $product->prices()->create([
                    'outlet_id' => null,
                    'product_item_id' => $defaultItem->id,
                    'amount' => $data['base_price'],
                ]);

                // Outlet Prices linked to default ProductItem
                if (! empty($data['outlet_prices'])) {
                    foreach ($data['outlet_prices'] as $op) {
                        $product->prices()->create([
                            'outlet_id' => $op['outlet_id'],
                            'product_item_id' => $defaultItem->id,
                            'amount' => $op['amount'],
                        ]);
                    }
                }
            } else {
                // Varian: Parent Base Price
                $product->prices()->create([
                    'outlet_id' => null,
                    'product_item_id' => null,
                    'amount' => $data['base_price'],
                ]);

                $this->createVariantMatrix($product, $data, $activeOutletIds);
            }

            // Recipe / BOM
            if ($product->isBasic() && $product->has_recipe && ! empty($data['recipes'])) {
                $this->recipeService->syncRecipe($product, $data['recipes']);
            }

            // Bundle Components
            if ($product->isBundle() && ! empty($data['bundle_items'])) {
                foreach ($data['bundle_items'] as $idx => $bi) {
                    $product->bundleItems()->create([
                        'component_product_id' => $bi['component_product_id'],
                        'component_product_item_id' => $bi['component_product_item_id'] ?? null,
                        'qty' => $bi['qty'],
                        'sort_order' => $idx,
                    ]);
                }
            }

            // Modifiers
            if ($product->has_modifier && ! empty($data['modifier_groups'])) {
                $modIds = array_column($data['modifier_groups'], 'modifier_group_id');
                $product->modifierGroups()->sync($modIds);
            }

            // Multiple Images
            if (isset($data['images'])) {
                $this->processProductImages($product, $data['images']);
            }

            $this->auditLogService->log($product->business_id, 'product', $product->id, 'created', null, $product->toArray());

            return $product;
        });
    }

    /**
     * Create Variant Matrix and populate pivot table product_item_variant_group_options.
     */
    public function createVariantMatrix(Product $product, array $data, array $activeOutletIds): void
    {
        $optionMap = [];
        if (! empty($data['variants'])) {
            foreach ($data['variants'] as $idx => $vData) {
                $vg = $product->variantGroups()->create([
                    'name' => $vData['name'],
                    'sort_order' => $idx,
                ]);
                foreach ($vData['options'] as $oIdx => $oData) {
                    $opt = $vg->options()->create([
                        'name' => $oData['name'],
                        'sort_order' => $oIdx,
                    ]);
                    $optionMap[$vData['name'].':'.$oData['name']] = $opt->id;
                }
            }
        }

        if (! empty($data['variant_combinations'])) {
            foreach ($data['variant_combinations'] as $combo) {
                $optIds = [];
                foreach ($combo['options'] as $groupName => $optName) {
                    if (isset($optionMap[$groupName.':'.$optName])) {
                        $optIds[] = $optionMap[$groupName.':'.$optName];
                    }
                }

                $isTrackInventory = array_key_exists('track_inventory', $combo)
                    ? (bool) $combo['track_inventory']
                    : (bool) $product->track_inventory;

                $isSellable = array_key_exists('sellable', $combo)
                    ? (bool) $combo['sellable']
                    : (bool) $product->sellable;
                if (! $product->sellable) {
                    $isSellable = false;
                }

                $isActive = array_key_exists('is_active', $combo)
                    ? (bool) $combo['is_active']
                    : true;

                $isShow = array_key_exists('is_show', $combo)
                    ? (bool) $combo['is_show']
                    : (bool) $product->is_show;
                if (! $isSellable || ! $product->is_show) {
                    $isShow = false;
                }

                $comboString = implode(' - ', $combo['options']);
                $sku = $combo['sku'] ?? null;
                if (empty($sku)) {
                    $sku = ($product->code ?: 'PRD').'-'.strtoupper(Str::random(4));
                }

                $variantItem = ProductItem::create([
                    'business_id' => $product->business_id,
                    'product_id' => $product->id,
                    'uom_id' => $isTrackInventory ? ($data['uom_id'] ?? null) : null,
                    'item_type' => 'variant_sku',
                    'name' => $product->name.' - '.$comboString,
                    'variant_combination' => $comboString,
                    'sku' => $sku,
                    'barcode' => $combo['barcode'] ?? null,
                    'track_inventory' => $isTrackInventory,
                    'is_show' => $isShow,
                    'sellable' => $isSellable,
                    'is_active' => $isActive,
                ]);

                // Populate pivot table product_item_variant_group_options
                if (! empty($optIds)) {
                    $variantItem->variantGroupOptions()->sync($optIds);
                }

                // Decoupled Inventory: ONLY link when track_inventory AND is_active
                if ($isTrackInventory && $isActive) {
                    $invItem = $this->inventoryService->linkInventoryItem($variantItem, [
                        'uom_id' => $data['uom_id'] ?? null,
                    ], $activeOutletIds);

                    $this->recordInitialStocksForProductItem($product, $invItem, $combo, $activeOutletIds);
                }

                // Combination Base Price
                if (isset($combo['price'])) {
                    $product->prices()->create([
                        'outlet_id' => null,
                        'product_item_id' => $variantItem->id,
                        'amount' => $combo['price'],
                    ]);
                }

                // Combination Outlet Prices
                if (! empty($combo['outlet_prices'])) {
                    foreach ($combo['outlet_prices'] as $op) {
                        $product->prices()->create([
                            'outlet_id' => $op['outlet_id'],
                            'product_item_id' => $variantItem->id,
                            'amount' => $op['amount'],
                        ]);
                    }
                }
            }
        }
    }

    /**
     * Update product details, causality, items, and inventory decoupling.
     */
    public function updateProduct(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data) {
            $before = $product->toArray();

            $productType = $data['product_type'] ?? $product->product_type;
            if ($productType === 'service') {
                $data['track_inventory'] = false;
                $data['has_variant'] = false;
                $data['has_recipe'] = false;
            } elseif ($productType === 'bundle') {
                $data['track_inventory'] = false;
                $data['has_variant'] = false;
                $data['has_modifier'] = false;
                $data['has_recipe'] = false;
            }

            // Causality Rule
            $sellable = array_key_exists('sellable', $data)
                ? (bool) $data['sellable']
                : (bool) $product->sellable;

            $isShow = array_key_exists('is_show', $data)
                ? (bool) $data['is_show']
                : (bool) $product->is_show;

            if (! $sellable) {
                $isShow = false;
            }

            // Feature Gating Evaluation
            $business = Business::findCached($product->business_id) ?? Business::find($product->business_id);
            $hasActiveSub = $business && $business->getActiveSubscriptionWithPlan();

            $canUseVariant = ! $hasActiveSub || $business->hasFeature(FeatureEnum::PRODUCT_VARIANT);
            $hasVariant = $canUseVariant && (array_key_exists('has_variant', $data) ? (bool) $data['has_variant'] : (bool) $product->has_variant);

            $canUseRecipe = ! $hasActiveSub || $business->hasFeature(FeatureEnum::PRODUCT_BOM);
            $hasRecipe = $canUseRecipe && (array_key_exists('has_recipe', $data) ? (bool) $data['has_recipe'] : (bool) $product->has_recipe);

            $canUseModifier = ! $hasActiveSub || $business->hasFeature(FeatureEnum::PRODUCT_MODIFIER);
            $hasModifier = $canUseModifier && (array_key_exists('has_modifier', $data) ? (bool) $data['has_modifier'] : (bool) $product->has_modifier);

            $product->update([
                'product_category_id' => $data['product_category_id'] ?? $product->product_category_id,
                'product_type' => $productType,
                'has_variant' => $hasVariant,
                'has_modifier' => $hasModifier,
                'has_recipe' => $hasRecipe,
                'track_inventory' => array_key_exists('track_inventory', $data) ? (bool) $data['track_inventory'] : (bool) $product->track_inventory,
                'code' => array_key_exists('code', $data) ? $data['code'] : $product->code,
                'name' => $data['name'] ?? $product->name,
                'description' => $data['description'] ?? $product->description,
                'image_url' => $data['image_url'] ?? $product->image_url,
                'is_show' => $isShow,
                'sellable' => $sellable,
                'purchasable' => array_key_exists('purchasable', $data) ? (bool) $data['purchasable'] : (bool) $product->purchasable,
            ]);

            $activeOutletIds = $this->syncProductOutlets($product, $data['outlets'] ?? null);

            // Single Product Unified Item Handling
            if ($product->isBasic() && ! $product->has_variant) {
                $existingItems = ProductItem::where('product_id', $product->id)->get();
                $defaultItem = $existingItems->firstWhere('variant_combination', null) ?? $existingItems->first();

                $sku = array_key_exists('code', $data) ? $data['code'] : ($defaultItem?->sku ?: $product->code);
                if (empty($sku)) {
                    $sku = 'PRD-'.strtoupper(Str::random(6));
                }
                $barcode = array_key_exists('barcode', $data) ? $data['barcode'] : $defaultItem?->barcode;

                if (! $defaultItem) {
                    $defaultItem = ProductItem::create([
                        'business_id' => $product->business_id,
                        'product_id' => $product->id,
                        'uom_id' => $product->track_inventory ? ($data['uom_id'] ?? null) : null,
                        'item_type' => 'variant_sku',
                        'name' => $product->name,
                        'variant_combination' => null,
                        'sku' => $sku,
                        'barcode' => $barcode,
                        'track_inventory' => $product->track_inventory,
                        'is_show' => $product->is_show,
                        'sellable' => $product->sellable,
                        'is_active' => true,
                    ]);
                } else {
                    $defaultItem->update([
                        'name' => $product->name,
                        'variant_combination' => null,
                        'sku' => $sku,
                        'barcode' => $barcode,
                        'track_inventory' => $product->track_inventory,
                        'uom_id' => $product->track_inventory ? ($data['uom_id'] ?? $defaultItem->uom_id) : null,
                        'is_show' => $product->is_show,
                        'sellable' => $product->sellable,
                        'is_active' => true,
                    ]);
                }

                // Clean up any extra items if converting from variant to single
                if ($existingItems->count() > 1) {
                    $existingItems->where('id', '!=', $defaultItem->id)->each(function ($item) {
                        $item->update(['is_active' => false]);
                        if ($item->inventoryItem) {
                            $item->inventoryItem->update(['is_active' => false]);
                        }
                        $item->delete();
                    });
                }
                $product->variantGroups()->delete();

                // Decoupled Inventory
                if ($product->track_inventory) {
                    $this->inventoryService->linkInventoryItem($defaultItem, [
                        'uom_id' => $data['uom_id'] ?? null,
                    ], $activeOutletIds);
                } elseif ($defaultItem->inventoryItem) {
                    $defaultItem->inventoryItem->update(['is_active' => false]);
                }

                // Price Updates
                if (isset($data['base_price'])) {
                    $product->prices()->whereNull('outlet_id')->delete();
                    $product->prices()->create([
                        'outlet_id' => null,
                        'product_item_id' => $defaultItem->id,
                        'amount' => $data['base_price'],
                    ]);
                }

                if (isset($data['outlet_prices'])) {
                    $product->prices()->whereNotNull('outlet_id')->delete();
                    foreach ($data['outlet_prices'] as $op) {
                        $product->prices()->create([
                            'outlet_id' => $op['outlet_id'],
                            'product_item_id' => $defaultItem->id,
                            'amount' => $op['amount'],
                        ]);
                    }
                }
            } elseif ($product->isBasic() && $product->has_variant) {
                if (isset($data['variants'])) {
                    $this->updateVariantMatrix($product, $data, $activeOutletIds);
                }
                if (! $product->sellable) {
                    $product->productItems()->update([
                        'sellable' => false,
                        'is_show' => false,
                    ]);
                } elseif (! $product->is_show) {
                    $product->productItems()->update([
                        'is_show' => false,
                    ]);
                }
            } elseif (! $product->isBasic()) {
                $product->variantGroups()->delete();
            }

            // Modifier Sync
            if ($product->has_modifier && isset($data['modifier_groups'])) {
                $modIds = array_column($data['modifier_groups'], 'modifier_group_id');
                $product->modifierGroups()->sync($modIds);
            } elseif (! $product->has_modifier) {
                $product->modifierGroups()->sync([]);
            }

            // Recipe Sync
            if ($product->isBasic() && $product->has_recipe && ! empty($data['recipes'])) {
                $this->recipeService->syncRecipe($product, $data['recipes']);
            } elseif (! $product->has_recipe) {
                $product->recipeVersions()->where('is_active', true)->update(['is_active' => false]);
            }

            // Bundle Sync
            if ($product->isBundle() && isset($data['bundle_items'])) {
                $product->bundleItems()->delete();
                foreach ($data['bundle_items'] as $idx => $bi) {
                    $product->bundleItems()->create([
                        'component_product_id' => $bi['component_product_id'],
                        'component_product_item_id' => $bi['component_product_item_id'] ?? null,
                        'qty' => $bi['qty'],
                        'sort_order' => $idx,
                    ]);
                }
            } elseif (! $product->isBundle()) {
                $product->bundleItems()->delete();
            }

            // Images
            if (isset($data['images'])) {
                $this->processProductImages($product, $data['images']);
            }

            $this->auditLogService->log($product->business_id, 'product', $product->id, 'updated', $before, $product->fresh()->toArray());

            return $product;
        });
    }

    /**
     * Update Variant Matrix and sync pivot table product_item_variant_group_options.
     */
    public function updateVariantMatrix(Product $product, array $data, array $activeOutletIds): void
    {
        $optionMap = [];
        $currentGroupIds = [];
        foreach ($data['variants'] as $idx => $vData) {
            $vg = $product->variantGroups()->firstOrCreate(
                ['name' => $vData['name']],
                ['sort_order' => $idx]
            );
            $vg->update(['sort_order' => $idx]);
            $currentGroupIds[] = $vg->id;

            $currentOptionIds = [];
            foreach ($vData['options'] as $oIdx => $oData) {
                $opt = $vg->options()->firstOrCreate(
                    ['name' => $oData['name']],
                    ['sort_order' => $oIdx]
                );
                $opt->update(['sort_order' => $oIdx]);
                $currentOptionIds[] = $opt->id;
                $optionMap[$vData['name'].':'.$oData['name']] = $opt->id;
            }
            $vg->options()->whereNotIn('id', $currentOptionIds)->delete();
        }
        $product->variantGroups()->whereNotIn('id', $currentGroupIds)->delete();

        $processedItemIds = [];
        if (isset($data['variant_combinations'])) {
            foreach ($data['variant_combinations'] as $combo) {
                $optIds = [];
                foreach ($combo['options'] as $groupName => $optName) {
                    if (isset($optionMap[$groupName.':'.$optName])) {
                        $optIds[] = $optionMap[$groupName.':'.$optName];
                    }
                }

                $comboString = implode(' - ', $combo['options']);
                $invItem = null;

                if (! empty($combo['sku'])) {
                    $invItem = ProductItem::where('product_id', $product->id)
                        ->where('sku', $combo['sku'])
                        ->first();
                }
                if (! $invItem) {
                    $invItem = ProductItem::where('product_id', $product->id)
                        ->where('variant_combination', $comboString)
                        ->first();
                }

                $isTrackInventory = array_key_exists('track_inventory', $combo)
                    ? (bool) $combo['track_inventory']
                    : (bool) $product->track_inventory;

                $isSellable = array_key_exists('sellable', $combo)
                    ? (bool) $combo['sellable']
                    : (bool) $product->sellable;
                if (! $product->sellable) {
                    $isSellable = false;
                }

                $isActive = array_key_exists('is_active', $combo)
                    ? (bool) $combo['is_active']
                    : true;

                $isShow = array_key_exists('is_show', $combo)
                    ? (bool) $combo['is_show']
                    : (bool) $product->is_show;
                if (! $isSellable || ! $product->is_show) {
                    $isShow = false;
                }

                $sku = array_key_exists('sku', $combo) ? $combo['sku'] : $invItem?->sku;
                if (empty($sku)) {
                    $sku = ($product->code ?: 'PRD').'-'.strtoupper(Str::random(4));
                }

                if (! $invItem) {
                    $invItem = ProductItem::create([
                        'business_id' => $product->business_id,
                        'product_id' => $product->id,
                        'uom_id' => $isTrackInventory ? ($data['uom_id'] ?? null) : null,
                        'item_type' => 'variant_sku',
                        'name' => $product->name.' - '.$comboString,
                        'variant_combination' => $comboString,
                        'sku' => $sku,
                        'barcode' => $combo['barcode'] ?? null,
                        'track_inventory' => $isTrackInventory,
                        'is_show' => $isShow,
                        'sellable' => $isSellable,
                        'is_active' => $isActive,
                    ]);
                } else {
                    $invItem->update([
                        'name' => $product->name.' - '.$comboString,
                        'variant_combination' => $comboString,
                        'sku' => $sku,
                        'barcode' => array_key_exists('barcode', $combo) ? $combo['barcode'] : $invItem->barcode,
                        'uom_id' => $isTrackInventory ? ($data['uom_id'] ?? null) : null,
                        'track_inventory' => $isTrackInventory,
                        'is_show' => $isShow,
                        'sellable' => $isSellable,
                        'is_active' => $isActive,
                    ]);
                }

                $processedItemIds[] = $invItem->id;

                // Sync pivot table product_item_variant_group_options
                if (! empty($optIds)) {
                    $invItem->variantGroupOptions()->sync($optIds);
                }

                if ($isTrackInventory && $isActive) {
                    $this->inventoryService->linkInventoryItem($invItem, [
                        'uom_id' => $data['uom_id'] ?? null,
                    ], $activeOutletIds);
                } elseif ($invItem->inventoryItem) {
                    $invItem->inventoryItem->update([
                        'name' => $invItem->name,
                        'uom_id' => $isTrackInventory ? ($data['uom_id'] ?? null) : null,
                        'is_active' => false,
                    ]);
                }

                if (isset($combo['price'])) {
                    $product->prices()->updateOrCreate(
                        [
                            'outlet_id' => null,
                            'product_item_id' => $invItem->id,
                        ],
                        [
                            'amount' => $combo['price'],
                        ]
                    );
                }

                $product->prices()->where('product_item_id', $invItem->id)->whereNotNull('outlet_id')->delete();
                if (! empty($combo['outlet_prices'])) {
                    foreach ($combo['outlet_prices'] as $op) {
                        $product->prices()->create([
                            'outlet_id' => $op['outlet_id'],
                            'product_item_id' => $invItem->id,
                            'amount' => $op['amount'],
                        ]);
                    }
                }
            }
        }

        // Deactivate removed combinations
        ProductItem::where('product_id', $product->id)
            ->whereNotIn('id', $processedItemIds)
            ->each(function ($item) {
                $item->update(['is_active' => false]);
                if ($item->inventoryItem) {
                    $item->inventoryItem->update(['is_active' => false]);
                }
            });
    }

    /**
     * Create Service Product with Unified Item Pattern.
     */
    public function createService(array $data): Product
    {
        return DB::transaction(function () use ($data) {
            $sellable = (bool) ($data['sellable'] ?? true);
            $isShow = $sellable ? (bool) ($data['is_show'] ?? true) : false;

            $product = Product::create([
                'business_id' => $data['business_id'],
                'product_category_id' => $data['product_category_id'] ?? null,
                'product_type' => ProductTypeEnum::SERVICE,
                'has_variant' => false,
                'has_modifier' => false,
                'has_recipe' => false,
                'track_inventory' => false,
                'code' => $data['code'] ?? null,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'image_url' => $data['image_url'] ?? null,
                'is_show' => $isShow,
                'sellable' => $sellable,
                'purchasable' => false,
            ]);

            $this->syncProductOutlets($product, $data['outlets'] ?? null);

            // Unified Item Pattern: Default ProductItem for Service
            $defaultSku = ! empty($data['code']) ? $data['code'] : ('SRV-'.strtoupper(Str::random(6)));
            $defaultItem = ProductItem::create([
                'business_id' => $product->business_id,
                'product_id' => $product->id,
                'uom_id' => null,
                'item_type' => 'variant_sku',
                'name' => $product->name,
                'variant_combination' => null,
                'sku' => $defaultSku,
                'barcode' => $data['barcode'] ?? null,
                'track_inventory' => false,
                'is_show' => $product->is_show,
                'sellable' => $product->sellable,
                'is_active' => true,
            ]);

            // Base Price
            $product->prices()->create([
                'outlet_id' => null,
                'product_item_id' => $defaultItem->id,
                'amount' => $data['base_price'],
            ]);

            // Outlet Prices
            if (! empty($data['outlet_prices'])) {
                foreach ($data['outlet_prices'] as $op) {
                    $product->prices()->create([
                        'outlet_id' => $op['outlet_id'],
                        'product_item_id' => $defaultItem->id,
                        'amount' => $op['amount'],
                    ]);
                }
            }

            if (isset($data['images'])) {
                $this->processProductImages($product, $data['images']);
            }

            $this->auditLogService->log($product->business_id, 'product', $product->id, 'created', null, $product->toArray());

            return $product;
        });
    }

    /**
     * Update Service Product with Unified Item Pattern.
     */
    public function updateService(Product $service, array $data): Product
    {
        return DB::transaction(function () use ($service, $data) {
            $before = $service->toArray();

            $sellable = array_key_exists('sellable', $data) ? (bool) $data['sellable'] : (bool) $service->sellable;
            $isShow = array_key_exists('is_show', $data) ? (bool) $data['is_show'] : (bool) $service->is_show;
            if (! $sellable) {
                $isShow = false;
            }

            $service->update([
                'product_category_id' => $data['product_category_id'] ?? $service->product_category_id,
                'product_type' => ProductTypeEnum::SERVICE,
                'has_variant' => false,
                'has_modifier' => false,
                'has_recipe' => false,
                'track_inventory' => false,
                'code' => array_key_exists('code', $data) ? $data['code'] : $service->code,
                'name' => $data['name'] ?? $service->name,
                'description' => $data['description'] ?? $service->description,
                'image_url' => $data['image_url'] ?? $service->image_url,
                'is_show' => $isShow,
                'sellable' => $sellable,
                'purchasable' => false,
            ]);

            $this->syncProductOutlets($service, $data['outlets'] ?? null);

            // Single default item
            $defaultItem = ProductItem::where('product_id', $service->id)->first();
            $sku = array_key_exists('code', $data) ? $data['code'] : ($defaultItem?->sku ?: $service->code);
            if (empty($sku)) {
                $sku = 'SRV-'.strtoupper(Str::random(6));
            }

            if (! $defaultItem) {
                $defaultItem = ProductItem::create([
                    'business_id' => $service->business_id,
                    'product_id' => $service->id,
                    'item_type' => 'variant_sku',
                    'name' => $service->name,
                    'variant_combination' => null,
                    'sku' => $sku,
                    'track_inventory' => false,
                    'is_show' => $service->is_show,
                    'sellable' => $service->sellable,
                    'is_active' => true,
                ]);
            } else {
                $defaultItem->update([
                    'name' => $service->name,
                    'variant_combination' => null,
                    'sku' => $sku,
                    'track_inventory' => false,
                    'is_show' => $service->is_show,
                    'sellable' => $service->sellable,
                    'is_active' => true,
                ]);
            }

            // Update Prices
            if (isset($data['base_price'])) {
                $productPrice = $service->prices()->whereNull('outlet_id')->first();
                if ($productPrice) {
                    $productPrice->update([
                        'product_item_id' => $defaultItem->id,
                        'amount' => $data['base_price'],
                    ]);
                } else {
                    $service->prices()->create([
                        'outlet_id' => null,
                        'product_item_id' => $defaultItem->id,
                        'amount' => $data['base_price'],
                    ]);
                }
            }

            if (isset($data['outlet_prices'])) {
                $service->prices()->whereNotNull('outlet_id')->delete();
                foreach ($data['outlet_prices'] as $op) {
                    $service->prices()->create([
                        'outlet_id' => $op['outlet_id'],
                        'product_item_id' => $defaultItem->id,
                        'amount' => $op['amount'],
                    ]);
                }
            }

            $service->variantGroups()->delete();
            $service->bundleItems()->delete();
            $service->modifierGroups()->sync([]);

            if (isset($data['images'])) {
                $this->processProductImages($service, $data['images']);
            }

            $this->auditLogService->log($service->business_id, 'product', $service->id, 'updated', $before, $service->fresh()->toArray());

            return $service;
        });
    }

    /**
     * Soft delete product, cascading to child ProductItems and logging audit trail.
     */
    public function deleteProduct(Product $product): bool
    {
        return DB::transaction(function () use ($product) {
            $before = $product->toArray();

            $product->inventoryItems()->update(['inventory_items.is_active' => false]);
            $product->productItems()->delete();
            $deleted = $product->delete();

            $this->auditLogService->log($product->business_id, 'product', $product->id, 'deleted', $before, null);

            return (bool) $deleted;
        });
    }

    /**
     * Soft delete service product and its child items.
     */
    public function deleteService(Product $service): bool
    {
        return DB::transaction(function () use ($service) {
            $before = $service->toArray();

            $service->productItems()->delete();
            $deleted = $service->delete();

            $this->auditLogService->log($service->business_id, 'product', $service->id, 'deleted', $before, null);

            return (bool) $deleted;
        });
    }

    /**
     * Helper to sync product outlets and return active outlet IDs.
     */
    protected function syncProductOutlets(Product $product, ?array $outletsData): array
    {
        $activeOutletIds = [];

        if (! empty($outletsData)) {
            $syncData = [];
            foreach ($outletsData as $out) {
                $isEnabled = $out['is_enabled'] ?? true;
                $syncData[$out['outlet_id']] = [
                    'is_enabled' => $isEnabled,
                    'is_available' => $out['is_available'] ?? true,
                ];
                if ($isEnabled) {
                    $activeOutletIds[] = $out['outlet_id'];
                }
            }
            $product->outlets()->sync($syncData);
        } else {
            $businessOutlets = Outlet::where('business_id', $product->business_id)
                ->where('is_active', true)
                ->pluck('id');

            if ($businessOutlets->isNotEmpty()) {
                $syncData = [];
                foreach ($businessOutlets as $outletId) {
                    $syncData[$outletId] = [
                        'is_enabled' => true,
                        'is_available' => true,
                    ];
                    $activeOutletIds[] = $outletId;
                }
                $product->outlets()->sync($syncData);
            }
        }

        return $activeOutletIds;
    }

    private function processProductImages(Product $product, array $images): void
    {
        $existingImageUrls = $product->images()->pluck('image_url')->filter()->all();
        $keptImageUrls = [];

        $product->images()->delete();

        $firstImageUrl = null;

        foreach ($images as $idx => $img) {
            $imageUrl = $img['image_url'] ?? null;

            // Check if there is an uploaded file
            if (isset($img['image_file']) && $img['image_file'] instanceof UploadedFile) {
                $imageUrl = $this->imageOptimizerService->optimizeAndStore(
                    $img['image_file'],
                    'products',
                    'product'
                );
            } else {
                // If it is already a stored path/URL, clean it up to store only relative path
                $storagePrefix = '/storage/';
                $parsed = parse_url($imageUrl, PHP_URL_PATH);
                if ($parsed && strpos($parsed, $storagePrefix) !== false) {
                    $imageUrl = substr($parsed, strpos($parsed, $storagePrefix) + strlen($storagePrefix));
                }
            }

            if ($imageUrl) {
                $keptImageUrls[] = $imageUrl;
            }

            $product->images()->create([
                'image_url' => $imageUrl,
                'sort_order' => $img['sort_order'] ?? $idx,
            ]);

            if ($idx === 0) {
                $firstImageUrl = $imageUrl;
            }
        }

        // Delete orphaned image files from storage that are no longer kept
        foreach ($existingImageUrls as $oldUrl) {
            if (! in_array($oldUrl, $keptImageUrls, true)) {
                $this->imageOptimizerService->delete($oldUrl);
            }
        }

        // Keep products.image_url updated with the cover image (first image)
        $product->update([
            'image_url' => $firstImageUrl,
        ]);
    }

    /**
     * Catat mutasi stok awal saat produk baru disimpan.
     */
    protected function recordInitialStocksForProductItem(
        Product $product,
        InventoryItem $invItem,
        array $data,
        ?array $activeOutletIds = null
    ): void {
        $business = $product->business ?? Business::find($product->business_id);
        if (! $business) {
            return;
        }

        // 1. Jika ada alokasi per-outlet khusus (misal array initial_stocks)
        if (! empty($data['initial_stocks']) && is_array($data['initial_stocks'])) {
            foreach ($data['initial_stocks'] as $stockEntry) {
                $outletId = $stockEntry['outlet_id'] ?? null;
                $qty = (float) ($stockEntry['qty'] ?? $stockEntry['initial_stock'] ?? 0);
                $cost = (float) ($stockEntry['purchase_price'] ?? $stockEntry['unit_cost'] ?? 0);

                if ($outletId && $qty > 0) {
                    $outlet = Outlet::find($outletId);
                    if ($outlet && $outlet->business_id === $product->business_id) {
                        $this->costingService->recordIncomingStock(
                            business: $business,
                            outlet: $outlet,
                            item: $invItem,
                            qty: $qty,
                            unitCost: $cost,
                            movementType: InventoryMovementType::InitialStock,
                            reference: $product,
                            description: 'Stok Awal Produk Baru',
                            user: auth()->user()
                        );
                    }
                }
            }

            return;
        }

        // 2. Jika ada single initial_stock (outlet default / aktif)
        $qty = (float) ($data['initial_stock'] ?? 0);
        $cost = (float) ($data['purchase_price'] ?? $data['initial_cost'] ?? 0);

        if ($qty > 0) {
            $targetOutletId = $data['outlet_id'] ?? ($activeOutletIds[0] ?? null);
            if (! $targetOutletId) {
                $targetOutletId = Outlet::where('business_id', $product->business_id)
                    ->where('is_active', true)
                    ->value('id');
            }

            if ($targetOutletId) {
                $outlet = Outlet::find($targetOutletId);
                if ($outlet) {
                    $this->costingService->recordIncomingStock(
                        business: $business,
                        outlet: $outlet,
                        item: $invItem,
                        qty: $qty,
                        unitCost: $cost,
                        movementType: InventoryMovementType::InitialStock,
                        reference: $product,
                        description: 'Stok Awal Produk Baru',
                        user: auth()->user()
                    );
                }
            }
        }
    }
}
