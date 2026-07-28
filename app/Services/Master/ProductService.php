<?php

namespace App\Services\Master;

use App\Models\Master\Product;
use Illuminate\Support\Facades\DB;
use App\Models\Master\VariantGroup;
use App\Models\Master\VariantGroupOption;

class ProductService
{
    private AuditLogService $auditLogService;
    private InventoryService $inventoryService;
    private RecipeService $recipeService;

    public function __construct(
        AuditLogService $auditLogService,
        InventoryService $inventoryService,
        RecipeService $recipeService
    ) {
        $this->auditLogService = $auditLogService;
        $this->inventoryService = $inventoryService;
        $this->recipeService = $recipeService;
    }

    public function createProduct(array $data)
    {
        return DB::transaction(function () use ($data) {
            $productType = $data['product_type'];
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

            $product = Product::create([
                'business_id' => $data['business_id'],
                'product_category_id' => $data['product_category_id'] ?? null,
                'product_type' => $productType,
                'has_variant' => $data['has_variant'] ?? false,
                'has_modifier' => $data['has_modifier'] ?? false,
                'has_recipe' => $data['has_recipe'] ?? false,
                'track_inventory' => $data['track_inventory'] ?? false,
                'code' => $data['code'] ?? null,
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'image_url' => $data['image_url'] ?? null,
                'is_show' => $data['is_show'] ?? true,
                'sellable' => $data['sellable'] ?? true,
                'purchasable' => $data['purchasable'] ?? false,
            ]);

            // Base Price
            $product->prices()->create([
                'outlet_id' => null,
                'inventory_item_id' => null,
                'amount' => $data['base_price'],
            ]);

            // Outlet Prices
            if (!empty($data['outlet_prices'])) {
                foreach ($data['outlet_prices'] as $op) {
                    $product->prices()->create([
                        'outlet_id' => $op['outlet_id'],
                        'inventory_item_id' => null,
                        'amount' => $op['amount'],
                    ]);
                }
            }

            // Outlets Assignment
            if (!empty($data['outlets'])) {
                $syncData = [];
                foreach ($data['outlets'] as $out) {
                    $syncData[$out['outlet_id']] = [
                        'is_enabled' => $out['is_enabled'] ?? true,
                        'is_available' => $out['is_available'] ?? true,
                    ];
                }
                $product->outlets()->sync($syncData);
            }

            // Variants
            if ($product->product_type === 'basic' && $product->has_variant && !empty($data['variants'])) {
                $optionMap = []; // original option name to ID
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
                        $optionMap[$vData['name'] . ':' . $oData['name']] = $opt->id;
                    }
                }

                // Create Inventory Items for variant combinations
                if (!empty($data['variant_combinations'])) {
                    foreach ($data['variant_combinations'] as $combo) {
                        $optIds = [];
                        foreach ($combo['options'] as $groupName => $optName) {
                            if (isset($optionMap[$groupName . ':' . $optName])) {
                                $optIds[] = $optionMap[$groupName . ':' . $optName];
                            }
                        }

                        $invItem = $this->inventoryService->createVariantInventory([
                            'business_id' => $product->business_id,
                            'product_id' => $product->id,
                            'name' => $product->name . ' - ' . implode(' - ', $combo['options']),
                            'sku' => $combo['sku'] ?? null,
                            'barcode' => $combo['barcode'] ?? null,
                            'track_inventory' => $product->track_inventory,
                            'min_stock' => $combo['min_stock'] ?? 0,
                            'options' => $optIds,
                            'uom_id' => $product->track_inventory ? ($data['uom_id'] ?? null) : null,
                        ]);

                        if (isset($combo['price'])) {
                            $product->prices()->create([
                                'outlet_id' => null,
                                'inventory_item_id' => $invItem->id,
                                'amount' => $combo['price'],
                            ]);
                        }

                        if (!empty($combo['outlet_prices'])) {
                            foreach ($combo['outlet_prices'] as $op) {
                                $product->prices()->create([
                                    'outlet_id' => $op['outlet_id'],
                                    'inventory_item_id' => $invItem->id,
                                    'amount' => $op['amount'],
                                ]);
                            }
                        }
                    }
                }
            } elseif ($product->product_type === 'basic') {
                // Single variant inventory item
                $this->inventoryService->createVariantInventory([
                    'business_id' => $product->business_id,
                    'product_id' => $product->id,
                    'name' => $product->name,
                    'sku' => $data['code'] ?? null,
                    'barcode' => null,
                    'track_inventory' => $product->track_inventory,
                    'min_stock' => $data['min_stock'] ?? 0,
                    'uom_id' => $product->track_inventory ? ($data['uom_id'] ?? null) : null,
                ]);
            }

            // Recipe
            if ($product->product_type === 'basic' && $product->has_recipe && !empty($data['recipes'])) {
                $this->recipeService->syncRecipe($product, $data['recipes']);
            }

            // Bundle
            if ($product->product_type === 'bundle' && !empty($data['bundle_items'])) {
                foreach ($data['bundle_items'] as $idx => $bi) {
                    $product->bundleItems()->create([
                        'component_product_id' => $bi['component_product_id'],
                        'component_inventory_item_id' => $bi['component_inventory_item_id'] ?? null,
                        'qty' => $bi['qty'],
                        'sort_order' => $idx,
                    ]);
                }
            }

            // Modifiers
            if ($product->has_modifier && !empty($data['modifier_groups'])) {
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

    public function updateProduct(Product $product, array $data)
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

            $product->update([
                'product_category_id' => $data['product_category_id'] ?? $product->product_category_id,
                'product_type' => $productType,
                'has_variant' => $data['has_variant'] ?? $product->has_variant,
                'has_modifier' => $data['has_modifier'] ?? $product->has_modifier,
                'has_recipe' => $data['has_recipe'] ?? $product->has_recipe,
                'track_inventory' => $data['track_inventory'] ?? $product->track_inventory,
                'code' => $data['code'] ?? $product->code,
                'name' => $data['name'] ?? $product->name,
                'description' => $data['description'] ?? $product->description,
                'image_url' => $data['image_url'] ?? $product->image_url,
                'is_show' => $data['is_show'] ?? $product->is_show,
                'sellable' => $data['sellable'] ?? $product->sellable,
                'purchasable' => $data['purchasable'] ?? $product->purchasable,
            ]);

            // Price updates
            if (isset($data['base_price'])) {
                $product->prices()->whereNull('outlet_id')->whereNull('inventory_item_id')->delete();
                $product->prices()->create([
                    'outlet_id' => null,
                    'inventory_item_id' => null,
                    'amount' => $data['base_price'],
                ]);
            }

            if (isset($data['outlet_prices'])) {
                $product->prices()->whereNotNull('outlet_id')->whereNull('inventory_item_id')->delete();
                foreach ($data['outlet_prices'] as $op) {
                    $product->prices()->create([
                        'outlet_id' => $op['outlet_id'],
                        'inventory_item_id' => null,
                        'amount' => $op['amount'],
                    ]);
                }
            }

            // Outlet sync
            if (isset($data['outlets'])) {
                $syncData = [];
                foreach ($data['outlets'] as $out) {
                    $syncData[$out['outlet_id']] = [
                        'is_enabled' => $out['is_enabled'] ?? true,
                        'is_available' => $out['is_available'] ?? true,
                    ];
                }
                $product->outlets()->sync($syncData);
            }

            // Variant sync
            if ($product->product_type === 'basic' && $product->has_variant && isset($data['variants'])) {
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
                        $optionMap[$vData['name'] . ':' . $oData['name']] = $opt->id;
                    }
                    $vg->options()->whereNotIn('id', $currentOptionIds)->delete();
                }
                $product->variantGroups()->whereNotIn('id', $currentGroupIds)->delete();

                if (isset($data['variant_combinations'])) {
                    foreach ($data['variant_combinations'] as $combo) {
                        $optIds = [];
                        foreach ($combo['options'] as $groupName => $optName) {
                            if (isset($optionMap[$groupName . ':' . $optName])) {
                                $optIds[] = $optionMap[$groupName . ':' . $optName];
                            }
                        }

                        $invItem = null;
                        if (!empty($combo['sku'])) {
                            $invItem = \App\Models\Master\InventoryItem::where('product_id', $product->id)
                                ->where('sku', $combo['sku'])
                                ->first();
                        }
                        
                        if (!$invItem) {
                            $invItem = $this->inventoryService->createVariantInventory([
                                'business_id' => $product->business_id,
                                'product_id' => $product->id,
                                'name' => $product->name . ' - ' . implode(' - ', $combo['options']),
                                'sku' => $combo['sku'] ?? null,
                                'barcode' => $combo['barcode'] ?? null,
                                'track_inventory' => $product->track_inventory,
                                'min_stock' => $combo['min_stock'] ?? 0,
                                'options' => $optIds,
                                'uom_id' => $product->track_inventory ? ($data['uom_id'] ?? null) : null,
                            ]);
                        } else {
                            $invItem->update([
                                'name'            => $product->name . ' - ' . implode(' - ', $combo['options']),
                                'barcode'         => $combo['barcode'] ?? $invItem->barcode,
                                'track_inventory' => $product->track_inventory,
                                'min_stock'       => $combo['min_stock'] ?? $invItem->min_stock,
                                'uom_id'          => $product->track_inventory ? ($data['uom_id'] ?? null) : null,
                            ]);
                            $invItem->variantGroupOptions()->sync($optIds);
                        }

                        if (isset($combo['price'])) {
                            $product->prices()->updateOrCreate(
                                [
                                    'outlet_id' => null,
                                    'inventory_item_id' => $invItem->id,
                                ],
                                [
                                    'amount' => $combo['price'],
                                ]
                            );
                        }

                        $product->prices()->where('inventory_item_id', $invItem->id)->whereNotNull('outlet_id')->delete();
                        if (!empty($combo['outlet_prices'])) {
                            foreach ($combo['outlet_prices'] as $op) {
                                $product->prices()->create([
                                    'outlet_id' => $op['outlet_id'],
                                    'inventory_item_id' => $invItem->id,
                                    'amount' => $op['amount'],
                                ]);
                            }
                        }
                    }
                }
            } elseif ($product->product_type === 'basic' && !$product->has_variant) {
                $product->variantGroups()->delete();

                // Update or create single variant inventory item
                $invItem = \App\Models\Master\InventoryItem::where('product_id', $product->id)
                    ->where('item_type', 'variant_sku')
                    ->first();
                if ($invItem) {
                    $invItem->update([
                        'name'            => $product->name,
                        'track_inventory' => $product->track_inventory,
                        'uom_id'          => $product->track_inventory ? ($data['uom_id'] ?? null) : null,
                    ]);
                } else {
                    $this->inventoryService->createVariantInventory([
                        'business_id'     => $product->business_id,
                        'product_id'      => $product->id,
                        'name'            => $product->name,
                        'sku'             => $product->code ?? null,
                        'barcode'         => null,
                        'track_inventory' => $product->track_inventory,
                        'min_stock'       => $data['min_stock'] ?? 0,
                        'uom_id'          => $product->track_inventory ? ($data['uom_id'] ?? null) : null,
                    ]);
                }
            } elseif ($product->product_type !== 'basic') {
                $product->variantGroups()->delete();
            }

            // Modifier sync
            if ($product->has_modifier && isset($data['modifier_groups'])) {
                $modIds = array_column($data['modifier_groups'], 'modifier_group_id');
                $product->modifierGroups()->sync($modIds);
            } elseif (!$product->has_modifier) {
                $product->modifierGroups()->sync([]);
            }

            // Recipe sync
            if ($product->product_type === 'basic' && $product->has_recipe && isset($data['recipes'])) {
                $this->recipeService->syncRecipe($product, $data['recipes']);
            }

            // Bundle sync
            if ($product->product_type === 'bundle' && isset($data['bundle_items'])) {
                $product->bundleItems()->delete();
                foreach ($data['bundle_items'] as $idx => $bi) {
                    $product->bundleItems()->create([
                        'component_product_id' => $bi['component_product_id'],
                        'component_inventory_item_id' => $bi['component_inventory_item_id'] ?? null,
                        'qty' => $bi['qty'],
                        'sort_order' => $idx,
                    ]);
                }
            } elseif ($product->product_type !== 'bundle') {
                $product->bundleItems()->delete();
            }

            // Multiple Images Update
            if (isset($data['images'])) {
                $this->processProductImages($product, $data['images']);
            }

            $this->auditLogService->log($product->business_id, 'product', $product->id, 'updated', $before, $product->fresh()->toArray());

            return $product;
        });
    }

    private function processProductImages(Product $product, array $images)
    {
        $product->images()->delete();

        $firstImageUrl = null;

        foreach ($images as $idx => $img) {
            $imageUrl = $img['image_url'] ?? null;
            
            // Check if there is an uploaded file
            if (isset($img['image_file']) && $img['image_file'] instanceof \Illuminate\Http\UploadedFile) {
                $imageUrl = $img['image_file']->store('products');
            } else {
                // If it is already a stored path/URL, clean it up to store only relative path
                $storagePrefix = '/storage/';
                $parsed = parse_url($imageUrl, PHP_URL_PATH);
                if ($parsed && strpos($parsed, $storagePrefix) !== false) {
                    $imageUrl = substr($parsed, strpos($parsed, $storagePrefix) + strlen($storagePrefix));
                }
            }

            $product->images()->create([
                'image_url' => $imageUrl,
                'sort_order' => $img['sort_order'] ?? $idx,
            ]);

            if ($idx === 0) {
                $firstImageUrl = $imageUrl;
            }
        }

        // Keep products.image_url updated with the cover image (first image)
        $product->update([
            'image_url' => $firstImageUrl,
        ]);
    }
}
