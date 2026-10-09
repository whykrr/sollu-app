<?php

declare(strict_types=1);

namespace App\Services\Pos;

use App\Models\Master\Product;
use App\Models\Master\ProductPrice;
use Illuminate\Database\Eloquent\Collection;

class PosPriceResolver
{
    /**
     * Resolve effective prices for a collection of products for a specific outlet.
     *
     * Hierarchy: Base price -> Variant price -> Outlet override (most specific wins).
     *
     * @param  Collection<int, Product>  $products
     * @param  array<string>  $productIds
     * @return array<int, array<string, mixed>>
     */
    public function resolveForOutlet(Collection $products, array $productIds, string $outletId): array
    {
        if (empty($productIds) || $products->isEmpty()) {
            return [];
        }

        $allPrices = ProductPrice::whereIn('product_id', $productIds)
            ->where(function ($q) use ($outletId) {
                $q->where('outlet_id', $outletId)
                    ->orWhereNull('outlet_id');
            })
            ->get();

        $pricesByProduct = $allPrices->groupBy('product_id');
        $resolvedPrices = [];

        foreach ($products as $product) {
            $productPrices = $pricesByProduct->get($product->id, collect());

            // 1. Resolve Base Product Price
            $basePrices = $productPrices->whereNull('product_item_id');
            $winningBasePrice = $basePrices->firstWhere('outlet_id', $outletId)
                ?? $basePrices->firstWhere('outlet_id', null);

            // Fallback for single item product where price might be linked to the single item
            if (! $winningBasePrice && $product->productItems->isNotEmpty()) {
                $firstItem = $product->productItems->first();
                $itemPrices = $productPrices->where('product_item_id', $firstItem->id);
                $winningBasePrice = $itemPrices->firstWhere('outlet_id', $outletId)
                    ?? $itemPrices->firstWhere('outlet_id', null);
            }

            $winningBaseAmount = (float) ($winningBasePrice?->amount ?? 0.0);

            // Set resolved base price directly on product model attribute
            $product->setAttribute('price', $winningBaseAmount);

            // Add resolved base price to list
            $resolvedPrices[] = [
                'id' => $winningBasePrice?->id ?? ('price-base-'.$product->id),
                'product_id' => $product->id,
                'product_item_id' => null,
                'inventory_item_id' => null,
                'amount' => $winningBaseAmount,
            ];

            // 2. Resolve Variant Prices (if product has variants)
            if ($product->productItems->isNotEmpty() && $product->has_variant) {
                foreach ($product->productItems as $item) {
                    $itemPrices = $productPrices->where('product_item_id', $item->id);
                    $winningVariantPrice = $itemPrices->firstWhere('outlet_id', $outletId)
                        ?? $itemPrices->firstWhere('outlet_id', null);

                    $winningVariantAmount = $winningVariantPrice
                        ? (float) $winningVariantPrice->amount
                        : $winningBaseAmount;

                    $resolvedPrices[] = [
                        'id' => $winningVariantPrice?->id ?? ('price-variant-'.$item->id),
                        'product_id' => $product->id,
                        'product_item_id' => $item->id,
                        'inventory_item_id' => $item->inventoryItem?->id,
                        'amount' => $winningVariantAmount,
                    ];
                }
            } elseif ($product->productItems->isNotEmpty() && ! $product->has_variant) {
                $firstItem = $product->productItems->first();
                if ($firstItem->inventoryItem?->id) {
                    $resolvedPrices[] = [
                        'id' => 'price-item-'.$firstItem->id,
                        'product_id' => $product->id,
                        'product_item_id' => $firstItem->id,
                        'inventory_item_id' => $firstItem->inventoryItem->id,
                        'amount' => $winningBaseAmount,
                    ];
                }
            }
        }

        return $resolvedPrices;
    }
}
