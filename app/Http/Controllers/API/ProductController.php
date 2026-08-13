<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProductResource;
use App\Models\Master\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Search products for dropdowns / select2 API.
     */
    public function search(Request $request)
    {
        $request->validate([
            'query' => 'nullable|string|min:3',
            'search' => 'nullable|string|min:3',
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

        $activeProductPromos = \App\Models\Promo::currentBusiness()
            ->active()
            ->where('target_type', \App\Enums\PromoTarget::Product->value)
            ->when($outletId, function ($q, $outletId) {
                $q->where(function ($sub) use ($outletId) {
                    $sub->where('applies_to_all_outlets', true)
                        ->orWhereHas('outlets', fn ($o) => $o->where('outlet_id', $outletId));
                });
            })
            ->whereHas('products', fn ($p) => $p->whereIn('product_id', $productIds))
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
     * Search products based on inventory_item (mimicking StockPurchasesController searchItems pattern).
     */
    public function searchByInventoryItem(Request $request)
    {
        $search = $request->get('search') ?: $request->get('query');
        $outletId = $request->get('outlet_id');
        $limit = $request->get('limit', 50);

        $items = \App\Models\Inventory\InventoryItem::currentBusiness()
            ->where('is_active', true)
            ->with([
                'uom:id,name',
                'variantGroupOptions:id',
                'product:id,name,code,track_inventory',
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
                    $q->whereLike('name', "%{$search}%")
                        ->orWhereLike('sku', "%{$search}%")
                        ->orWhereLike('barcode', "%{$search}%");
                });
            })
            ->limit($limit)
            ->get();

        $productIds = $items->pluck('product_id')->filter()->unique()->values()->toArray();

        $activeProductPromos = \App\Models\Promo::currentBusiness()
            ->active()
            ->where('target_type', \App\Enums\PromoTarget::Product->value)
            ->when($outletId, function ($q, $outletId) {
                $q->where(function ($sub) use ($outletId) {
                    $sub->where('applies_to_all_outlets', true)
                        ->orWhereHas('outlets', fn ($o) => $o->where('outlet_id', $outletId));
                });
            })
            ->whereHas('products', fn ($p) => $p->whereIn('product_id', $productIds))
            ->with('products:id')
            ->get();

        $result = $items->map(function ($item) use ($outletId, $activeProductPromos) {
            $product = $item->product;
            $productId = $product?->id ?? $item->product_id ?? $item->id;
            $productName = $item->name ?: ($product?->name ?? '');
            $code = $product?->code ?? $item->sku ?? $item->barcode ?? '-';
            $trackInventory = $item->track_inventory ?? $product?->track_inventory ?? true;

            $price = 0;
            if ($product && $product->relationLoaded('prices')) {
                $outletPrice = $outletId ? $product->prices->firstWhere('outlet_id', $outletId) : null;
                $generalPrice = $product->prices->firstWhere('outlet_id', null);
                $price = $outletPrice?->amount ?? $generalPrice?->amount ?? 0;
            }

            $currentStock = 0;
            if ($item->relationLoaded('balances')) {
                $balance = $item->balances->first();
                if ($balance) {
                    $currentStock = floatval($balance->current_stock);
                }
            }

            $matchingPromos = $activeProductPromos->filter(function ($promo) use ($productId) {
                return $promo->products->contains('id', $productId);
            })->map(fn ($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'promo_type' => is_object($p->promo_type) ? $p->promo_type->value : $p->promo_type,
                'target_type' => is_object($p->target_type) ? $p->target_type->value : $p->target_type,
                'discount_value' => floatval($p->discount_value),
                'max_discount' => $p->max_discount ? floatval($p->max_discount) : null,
            ])->values()->toArray();

            return [
                'id' => $item->id,
                'inventory_item_id' => $item->id,
                'product_id' => $productId,
                'variant_group_option_id' => $item->variantGroupOptions->first()?->id ?? null,
                'name' => $productName,
                'code' => $code,
                'sku' => $item->sku,
                'barcode' => $item->barcode,
                'uom' => $item->uom?->name,
                'price' => floatval($price),
                'track_inventory' => (bool) $trackInventory,
                'current_stock' => $currentStock,
                'active_promos' => $matchingPromos,
            ];
        });

        return response()->json($result);
    }
}
