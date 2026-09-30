<?php

namespace App\Http\Controllers\API;

use App\Enums\PromotionTargetScope;
use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Inventory\InventoryItem;
use App\Models\Master\Product;
use App\Models\Promotion\Promotion;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Search products for dropdowns / select2 API.
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'nullable|string',
            'search' => 'nullable|string',
            'outlet_id' => 'nullable|uuid',
            'limit' => 'nullable|integer|min:1|max:100',
        ]);

        $limit = $request->query('limit', 20);
        $outletId = $request->query('outlet_id');
        $searchTerm = $request->query('query') ?: $request->query('search');

        $products = Product::currentBusiness()
            ->filters($request->only(['search', 'category', 'outlet']))
            ->when($searchTerm, function ($q, $search) {
                $q->where(function ($query) use ($search) {
                    $query->whereLike('name', "%{$search}%")
                        ->orWhereLike('code', "%{$search}%");
                });
            })
            ->when($outletId, function ($q, $outletId) {
                $q->whereHas('outlets', function ($query) use ($outletId) {
                    $query->where('outlet_id', $outletId);
                });
            })
            ->with([
                'prices' => function ($q) use ($outletId) {
                    if ($outletId) {
                        $q->where(function ($sub) use ($outletId) {
                            $sub->where('outlet_id', $outletId)->orWhereNull('outlet_id');
                        });
                    }
                },
                'inventoryItems.balances' => function ($q) use ($outletId) {
                    if ($outletId) {
                        $q->where('outlet_id', $outletId);
                    }
                },
            ])
            ->take($limit)
            ->get();

        $productIds = $products->pluck('id')->toArray();

        $activeProductPromos = Promotion::currentBusiness()
            ->active()
            ->where('target_scope', PromotionTargetScope::Product->value)
            ->when($outletId, function ($q, $outletId) {
                $q->where(function ($sub) use ($outletId) {
                    $sub->where('applies_to_all_outlets', true)
                        ->orWhereHas('outlets', fn ($o) => $o->where('outlets.id', $outletId));
                });
            })
            ->whereHas('products', fn ($p) => $p->whereIn('products.id', $productIds))
            ->with('products:id')
            ->get();

        $products->each(function ($product) use ($activeProductPromos) {
            $matchingPromos = $activeProductPromos->filter(function ($promo) use ($product) {
                return $promo->products->contains('id', $product->id);
            })->values();
            $product->setRelation('activePromos', $matchingPromos);
        });

        return $this->successResponse(ProductResource::collection($products));
    }

    /**
     * Search product items for Transaction & Sales modules (Strictly decoupled from Inventory domain).
     * Returns all product types: basic (single/variant), services, and bundles.
     */
    public function searchItems(Request $request)
    {
        $search = $request->get('search') ?: $request->get('query');
        $checkPromo = $request->boolean('check_promo', true);
        $outletId = $request->get('outlet_id');
        $limit = min((int) $request->get('limit', 50), 100);
        $businessId = $request->user()->business_id;

        $products = Product::currentBusiness($businessId)
            ->where('is_show', true)
            ->where('sellable', true)
            ->when($outletId, function ($query, $outletId) {
                $query->where(function ($q) use ($outletId) {
                    $q->whereDoesntHave('outlets')
                        ->orWhereHas('outlets', function ($oq) use ($outletId) {
                            $oq->where('outlets.id', $outletId)
                                ->where('outlet_product.is_enabled', true);
                        });
                });
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->whereLike('products.name', "%{$search}%")
                        ->orWhereLike('products.code', "%{$search}%")
                        ->orWhereHas('productItems', function ($pi) use ($search) {
                            $pi->whereLike('name', "%{$search}%")
                                ->orWhereLike('sku', "%{$search}%")
                                ->orWhereLike('barcode', "%{$search}%")
                                ->orWhereLike('variant_combination', "%{$search}%");
                        });
                });
            })
            ->with([
                'category:id,name',
                'prices' => function ($q) use ($outletId) {
                    if ($outletId) {
                        $q->where(function ($sub) use ($outletId) {
                            $sub->where('outlet_id', $outletId)->orWhereNull('outlet_id');
                        });
                    }
                },
                'productItems' => function ($q) use ($outletId) {
                    $q->where('is_active', true)
                        ->where('sellable', true)
                        ->with([
                            'uom:id,name',
                            'prices' => function ($pq) use ($outletId) {
                                if ($outletId) {
                                    $pq->where(function ($sub) use ($outletId) {
                                        $sub->where('outlet_id', $outletId)->orWhereNull('outlet_id');
                                    });
                                }
                            },
                        ]);
                },
            ])
            ->take($limit)
            ->get();

        $productIds = $products->pluck('id')->toArray();
        $productItemIds = $products->flatMap->productItems->pluck('id')->toArray();

        $activePromos = collect();
        if ($checkPromo) {
            $activePromos = Promotion::currentBusiness($businessId)
                ->active()
                ->when($outletId, function ($q, $outletId) {
                    $q->where(function ($sub) use ($outletId) {
                        $sub->where('applies_to_all_outlets', true)
                            ->orWhereHas('outlets', fn ($o) => $o->where('outlets.id', $outletId));
                    });
                })
                ->where(function ($q) use ($productIds, $productItemIds) {
                    $q->whereHas('products', fn ($p) => $p->whereIn('products.id', $productIds))
                        ->orWhereHas('productItems', fn ($pi) => $pi->whereIn('product_items.id', $productItemIds));
                })
                ->with(['products:id', 'productItems:id'])
                ->get();
        }

        $results = collect();

        foreach ($products as $product) {
            $productType = is_object($product->product_type) ? $product->product_type->value : (string) $product->product_type;
            $categoryName = $product->category?->name;

            if ($product->productItems->isNotEmpty()) {
                foreach ($product->productItems as $item) {
                    $price = 0;
                    if ($item->relationLoaded('prices')) {
                        $outletPrice = $outletId ? $item->prices->firstWhere('outlet_id', $outletId) : null;
                        $generalPrice = $item->prices->firstWhere('outlet_id', null);
                        $price = $outletPrice?->amount ?? $generalPrice?->amount ?? 0;
                    }

                    if ($price == 0 && $product->relationLoaded('prices')) {
                        $outletPrice = $outletId ? $product->prices->where('product_item_id', $item->id)->firstWhere('outlet_id', $outletId) : null;
                        $generalPrice = $product->prices->where('product_item_id', $item->id)->firstWhere('outlet_id', null);
                        $price = $outletPrice?->amount ?? $generalPrice?->amount ?? 0;
                    }

                    if ($price == 0 && $product->relationLoaded('prices')) {
                        $outletPrice = $outletId ? $product->prices->whereNull('product_item_id')->firstWhere('outlet_id', $outletId) : null;
                        $generalPrice = $product->prices->whereNull('product_item_id')->firstWhere('outlet_id', null);
                        $price = $outletPrice?->amount ?? $generalPrice?->amount ?? 0;
                    }

                    $matchingPromos = [];
                    if ($checkPromo && $activePromos->isNotEmpty()) {
                        $matchingPromos = $activePromos->filter(function ($promo) use ($product, $item) {
                            if ($promo->target_scope === PromotionTargetScope::Product || $promo->target_scope?->value === PromotionTargetScope::Product->value) {
                                return $promo->products->contains('id', $product->id);
                            }
                            if ($promo->target_scope === PromotionTargetScope::Variant || $promo->target_scope?->value === PromotionTargetScope::Variant->value) {
                                return $promo->productItems->contains('id', $item->id);
                            }

                            return false;
                        })->map(fn ($p) => [
                            'id' => $p->id,
                            'name' => $p->name,
                            'promo_type' => is_object($p->discount_type) ? $p->discount_type->value : $p->discount_type,
                            'target_type' => is_object($p->target_scope) ? $p->target_scope->value : $p->target_scope,
                            'discount_value' => floatval($p->discount_value),
                            'max_discount' => $p->max_discount_amount ? floatval($p->max_discount_amount) : null,
                        ])->values()->toArray();
                    }

                    $results->push([
                        'id' => $item->id,
                        'product_id' => $product->id,
                        'product_item_id' => $item->id,
                        'name' => $item->name ?: $product->name,
                        'product_name' => $product->name,
                        'code' => $product->code ?? '-',
                        'sku' => $item->sku ?: ($product->code ?: ''),
                        'barcode' => $item->barcode ?: '',
                        'uom' => $item->uom?->name ?: 'Pcs',
                        'price' => floatval($price),
                        'product_type' => $productType,
                        'category_name' => $categoryName,
                        'active_promos' => $matchingPromos,
                    ]);
                }
            } else {
                $price = 0;
                if ($product->relationLoaded('prices')) {
                    $outletPrice = $outletId ? $product->prices->whereNull('product_item_id')->firstWhere('outlet_id', $outletId) : null;
                    $generalPrice = $product->prices->whereNull('product_item_id')->firstWhere('outlet_id', null);
                    $price = $outletPrice?->amount ?? $generalPrice?->amount ?? 0;
                }

                $matchingPromos = [];
                if ($checkPromo && $activePromos->isNotEmpty()) {
                    $matchingPromos = $activePromos->filter(function ($promo) use ($product) {
                        return $promo->products->contains('id', $product->id);
                    })->map(fn ($p) => [
                        'id' => $p->id,
                        'name' => $p->name,
                        'promo_type' => is_object($p->discount_type) ? $p->discount_type->value : $p->discount_type,
                        'target_type' => is_object($p->target_scope) ? $p->target_scope->value : $p->target_scope,
                        'discount_value' => floatval($p->discount_value),
                        'max_discount' => $p->max_discount_amount ? floatval($p->max_discount_amount) : null,
                    ])->values()->toArray();
                }

                $results->push([
                    'id' => $product->id,
                    'product_id' => $product->id,
                    'product_item_id' => null,
                    'name' => $product->name,
                    'product_name' => $product->name,
                    'code' => $product->code ?? '-',
                    'sku' => $product->code ?? '',
                    'barcode' => '',
                    'uom' => 'Pcs',
                    'price' => floatval($price),
                    'product_type' => $productType,
                    'category_name' => $categoryName,
                    'active_promos' => $matchingPromos,
                ]);
            }
        }

        return response()->json($results->take($limit)->values()->toArray());
    }

    /**
     * Search products and variant items for Sales & Invoices (Dual View Mode: Product & Variant, including services).
     */
    public function searchByInventoryItem(Request $request)
    {
        $search = $request->get('search') ?: $request->get('query');
        $checkPromo = $request->boolean('check_promo', true);
        $outletId = $request->get('outlet_id');
        $limit = min((int) $request->get('limit', 50), 100);
        $businessId = $request->user()->business_id;

        // 1. Fetch tracked inventory items
        $inventoryItems = InventoryItem::query()
            ->where('inventory_items.business_id', $businessId)
            ->where('inventory_items.is_active', true)
            ->joinProductItem()
            ->with([
                'uom:id,name',
                'productItem.uom:id,name',
                'productItem.prices' => function ($q) use ($outletId) {
                    if ($outletId) {
                        $q->where(function ($sub) use ($outletId) {
                            $sub->where('outlet_id', $outletId)->orWhereNull('outlet_id');
                        });
                    }
                },
                'product:products.id,products.name,products.code,products.track_inventory,products.product_type',
                'product.prices' => function ($q) use ($outletId) {
                    if ($outletId) {
                        $q->where(function ($sub) use ($outletId) {
                            $sub->where('outlet_id', $outletId)->orWhereNull('outlet_id');
                        });
                    }
                },
                'balances' => function ($q) use ($outletId) {
                    if ($outletId) {
                        $q->where('outlet_id', $outletId);
                    }
                },
            ])
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->whereLike('inventory_items.name', "%{$search}%")
                        ->orWhereLike('product_items.name', "%{$search}%")
                        ->orWhereLike('product_items.sku', "%{$search}%")
                        ->orWhereLike('product_items.barcode', "%{$search}%")
                        ->orWhereLike('product_items.variant_combination', "%{$search}%")
                        ->orWhereHas('product', function ($pq) use ($search) {
                            $pq->where(function ($sub) use ($search) {
                                $sub->whereLike('products.name', "%{$search}%")
                                    ->orWhereLike('products.code', "%{$search}%");
                            });
                        });
                });
            })
            ->when($outletId, function ($query, $outletId) {
                $query->whereHas('product', function ($pq) use ($outletId) {
                    $pq->whereDoesntHave('outlets')
                        ->orWhereHas('outlets', function ($oq) use ($outletId) {
                            $oq->where('outlets.id', $outletId)
                                ->where('outlet_product.is_enabled', true);
                        });
                });
            })
            ->limit($limit)
            ->get();

        // 2. Fetch products without inventory items (Services, Bundles, Non-inventory basic products)
        $nonInventoryProducts = Product::currentBusiness($businessId)
            ->where('is_show', true)
            ->where('sellable', true)
            ->where(function ($q) {
                $q->where('product_type', 'service')
                    ->orWhere('product_type', 'bundle')
                    ->orWhere('track_inventory', false)
                    ->orWhereDoesntHave('inventoryItems');
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->whereLike('name', "%{$search}%")
                        ->orWhereLike('code', "%{$search}%");
                });
            })
            ->when($outletId, function ($query, $outletId) {
                $query->where(function ($q) use ($outletId) {
                    $q->whereDoesntHave('outlets')
                        ->orWhereHas('outlets', function ($oq) use ($outletId) {
                            $oq->where('outlets.id', $outletId)
                                ->where('outlet_product.is_enabled', true);
                        });
                });
            })
            ->with([
                'prices' => function ($q) use ($outletId) {
                    if ($outletId) {
                        $q->where(function ($sub) use ($outletId) {
                            $sub->where('outlet_id', $outletId)->orWhereNull('outlet_id');
                        });
                    }
                },
            ])
            ->limit($limit)
            ->get();

        // Promos resolution
        $activePromos = collect();
        if ($checkPromo) {
            $activePromos = Promotion::currentBusiness($businessId)
                ->active()
                ->when($outletId, function ($q, $outletId) {
                    $q->where(function ($sub) use ($outletId) {
                        $sub->where('applies_to_all_outlets', true)
                            ->orWhereHas('outlets', fn ($o) => $o->where('outlets.id', $outletId));
                    });
                })
                ->with(['products:id', 'productItems:id'])
                ->get();
        }

        $results = collect();

        // Map tracked inventory items
        foreach ($inventoryItems as $item) {
            $product = $item->productItem?->product ?? $item->product;
            $productId = $product?->id ?? $item->product_id;
            $productItemId = $item->product_item_id;
            $productName = $product?->name ?? $item->name ?? '';
            $itemName = $item->name ?: ($item->productItem?->name ?: $productName);
            $code = $product?->code ?? $item->sku ?? $item->barcode ?? '-';
            $sku = $item->sku ?: ($item->productItem?->sku ?: ($product?->code ?: ''));
            $barcode = $item->barcode ?: ($item->productItem?->barcode ?: '');
            $uomName = $item->uom?->name ?: ($item->productItem?->uom?->name ?: 'Pcs');
            $trackInventory = $item->track_inventory ?? $product?->track_inventory ?? true;

            // Resolve Price
            $price = 0;
            if ($item->productItem && $item->productItem->relationLoaded('prices')) {
                $outletPrice = $outletId ? $item->productItem->prices->firstWhere('outlet_id', $outletId) : null;
                $generalPrice = $item->productItem->prices->firstWhere('outlet_id', null);
                $price = $outletPrice?->amount ?? $generalPrice?->amount ?? 0;
            }
            if ($price == 0 && $product && $product->relationLoaded('prices')) {
                if ($productItemId) {
                    $outletPrice = $outletId ? $product->prices->where('product_item_id', $productItemId)->firstWhere('outlet_id', $outletId) : null;
                    $generalPrice = $product->prices->where('product_item_id', $productItemId)->firstWhere('outlet_id', null);
                    $price = $outletPrice?->amount ?? $generalPrice?->amount ?? 0;
                }
                if ($price == 0) {
                    $outletPrice = $outletId ? $product->prices->whereNull('product_item_id')->firstWhere('outlet_id', $outletId) : null;
                    $generalPrice = $product->prices->whereNull('product_item_id')->firstWhere('outlet_id', null);
                    $price = $outletPrice?->amount ?? $generalPrice?->amount ?? 0;
                }
            }

            // Resolve Stock
            $currentStock = 0;
            if ($item->relationLoaded('balances')) {
                $balance = $item->balances->first();
                if ($balance) {
                    $currentStock = floatval($balance->current_stock);
                }
            }

            // Matching promos
            $matchingPromos = [];
            if ($checkPromo && $activePromos->isNotEmpty()) {
                $matchingPromos = $activePromos->filter(function ($promo) use ($productId, $productItemId) {
                    if ($promo->target_scope === PromotionTargetScope::Product || $promo->target_scope?->value === PromotionTargetScope::Product->value) {
                        return $productId && $promo->products->contains('id', $productId);
                    }
                    if ($promo->target_scope === PromotionTargetScope::Variant || $promo->target_scope?->value === PromotionTargetScope::Variant->value) {
                        return $productItemId && $promo->productItems->contains('id', $productItemId);
                    }

                    return false;
                })->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'promo_type' => is_object($p->discount_type) ? $p->discount_type->value : $p->discount_type,
                    'target_type' => is_object($p->target_scope) ? $p->target_scope->value : $p->target_scope,
                    'discount_value' => floatval($p->discount_value),
                    'max_discount' => $p->max_discount_amount ? floatval($p->max_discount_amount) : null,
                ])->values()->toArray();
            }

            $results->push([
                'id' => $item->id,
                'inventory_item_id' => $item->id,
                'product_item_id' => $productItemId,
                'product_id' => $productId,
                'name' => $itemName,
                'product_name' => $productName,
                'code' => $code,
                'sku' => $sku,
                'barcode' => $barcode,
                'uom' => $uomName,
                'price' => floatval($price),
                'track_inventory' => (bool) $trackInventory,
                'current_stock' => $currentStock,
                'active_promos' => $matchingPromos,
            ]);
        }

        // Map non-inventory products (services / bundles / untracked)
        foreach ($nonInventoryProducts as $product) {
            $price = 0;
            if ($product->relationLoaded('prices')) {
                $outletPrice = $outletId ? $product->prices->whereNull('product_item_id')->firstWhere('outlet_id', $outletId) : null;
                $generalPrice = $product->prices->whereNull('product_item_id')->firstWhere('outlet_id', null);
                $price = $outletPrice?->amount ?? $generalPrice?->amount ?? 0;
            }

            $matchingPromos = [];
            if ($checkPromo && $activePromos->isNotEmpty()) {
                $matchingPromos = $activePromos->filter(function ($promo) use ($product) {
                    return $promo->products->contains('id', $product->id);
                })->map(fn ($p) => [
                    'id' => $p->id,
                    'name' => $p->name,
                    'promo_type' => is_object($p->discount_type) ? $p->discount_type->value : $p->discount_type,
                    'target_type' => is_object($p->target_scope) ? $p->target_scope->value : $p->target_scope,
                    'discount_value' => floatval($p->discount_value),
                    'max_discount' => $p->max_discount_amount ? floatval($p->max_discount_amount) : null,
                ])->values()->toArray();
            }

            $results->push([
                'id' => $product->id,
                'inventory_item_id' => null,
                'product_item_id' => null,
                'product_id' => $product->id,
                'name' => $product->name,
                'product_name' => $product->name,
                'code' => $product->code ?? '-',
                'sku' => $product->code ?? '',
                'barcode' => '',
                'uom' => 'Pcs',
                'price' => floatval($price),
                'track_inventory' => false,
                'current_stock' => 0,
                'active_promos' => $matchingPromos,
            ]);
        }

        return response()->json($results->take($limit)->values()->toArray());
    }
}
