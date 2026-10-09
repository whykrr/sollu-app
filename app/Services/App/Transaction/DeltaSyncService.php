<?php

declare(strict_types=1);

namespace App\Services\App\Transaction;

use App\Models\Inventory\InventoryBalance;
use App\Models\Master\Product;
use App\Models\Master\ProductItem;
use App\Models\Master\ProductPrice;
use App\Models\OutletDevice;
use App\Services\Pos\PosPriceResolver;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DeltaSyncService
{
    public function __construct(
        private readonly PosPriceResolver $priceResolver,
    ) {}

    /**
     * Build lightweight, query-level optimized delta master catalog payload.
     * Zero cache policy: direct index seek without Redis cache overhead.
     *
     * @param  array<string>|null  $entities  List of specific entity types to sync, or null for all entities.
     */
    public function getDelta(OutletDevice $device, string $updatedSince, ?array $entities = null): array
    {
        $since = Carbon::parse($updatedSince);
        $outlet = $device->outlet;
        $outletId = $outlet->id;
        $businessId = $outlet->business_id;

        // 1. Ambil ID produk yang aktif di outlet ini (fast compound index seek)
        $outletProductRows = DB::table('outlet_product')
            ->where('outlet_id', $outletId)
            ->where('is_enabled', true)
            ->get();

        $productIds = $outletProductRows->pluck('product_id')->all();

        if (empty($productIds)) {
            return [
                'synced_at' => now()->toIso8601String(),
                'updated_products' => [],
                'updated_product_items' => [],
                'updated_prices' => [],
                'updated_inventory_balances' => [],
                'deleted_product_ids' => [],
                'deleted_product_item_ids' => [],
            ];
        }

        $shouldFetchBalances = empty($entities) || in_array('inventory_balance', $entities, true);
        $shouldFetchProducts = empty($entities) || in_array('product', $entities, true);
        $shouldFetchProductItems = empty($entities) || in_array('product_item', $entities, true);
        $shouldFetchPrices = empty($entities) || in_array('product_price', $entities, true) || in_array('price', $entities, true);
        $shouldFetchDeletes = empty($entities) || in_array('product', $entities, true) || in_array('product_item', $entities, true);

        // 2 & 4. Produk & Harga yang diperbarui sejak updated_since
        $updatedProducts = collect();
        $updatedPrices = [];

        if ($shouldFetchProducts || $shouldFetchPrices) {
            $targetProductIds = [];

            if ($shouldFetchProducts) {
                $targetProductIds = Product::where('business_id', $businessId)
                    ->whereIn('id', $productIds)
                    ->where('updated_at', '>', $since)
                    ->pluck('id')
                    ->all();
            }

            if ($shouldFetchPrices) {
                $priceProductIds = ProductPrice::whereIn('product_id', $productIds)
                    ->where(function ($q) use ($outletId) {
                        $q->where('outlet_id', $outletId)
                            ->orWhereNull('outlet_id');
                    })
                    ->where('updated_at', '>', $since)
                    ->pluck('product_id')
                    ->all();

                $targetProductIds = array_values(array_unique(array_merge($targetProductIds, $priceProductIds)));
            }

            if (! empty($targetProductIds)) {
                $loadedProducts = Product::with(['productItems.uom', 'productItems.inventoryItem'])
                    ->where('business_id', $businessId)
                    ->whereIn('id', $targetProductIds)
                    ->get();

                $resolvedPrices = $this->priceResolver->resolveForOutlet($loadedProducts, $targetProductIds, $outletId);

                $updatedProducts = $loadedProducts->makeHidden('business_id');
                $updatedPrices = $resolvedPrices;
            }
        }

        // 3. Product items (varian, SKU, barcode) yang dibuat atau diperbarui sejak updated_since
        $updatedProductItems = $shouldFetchProductItems
            ? ProductItem::with(['uom', 'inventoryItem'])
                ->where('business_id', $businessId)
                ->whereIn('product_id', $productIds)
                ->where('updated_at', '>', $since)
                ->get()
                ->makeHidden('business_id')
            : collect();

        // 5. Saldo stok inventori yang bermutasi sejak updated_since pada outlet ini
        $updatedInventoryBalances = $shouldFetchBalances
            ? InventoryBalance::where('outlet_id', $outletId)
                ->where('updated_at', '>', $since)
                ->get()
                ->makeHidden(['business_id', 'outlet_id'])
            : collect();

        // 6 & 7. Produk dan product items yang di-soft-delete atau dinonaktifkan sejak updated_since
        $deletedProductIds = [];
        $deletedProductItemIds = [];

        if ($shouldFetchDeletes) {
            $softDeletedProductIds = Product::withTrashed()
                ->where('business_id', $businessId)
                ->whereNotNull('deleted_at')
                ->where('deleted_at', '>', $since)
                ->pluck('id')
                ->all();

            $disabledOutletProductIds = DB::table('outlet_product')
                ->where('outlet_id', $outletId)
                ->where('is_enabled', false)
                ->where('updated_at', '>', $since)
                ->pluck('product_id')
                ->all();

            $deletedProductIds = array_values(array_unique(array_merge($softDeletedProductIds, $disabledOutletProductIds)));

            $deletedProductItemIds = array_values(array_unique(ProductItem::withTrashed()
                ->where('business_id', $businessId)
                ->whereNotNull('deleted_at')
                ->where('deleted_at', '>', $since)
                ->pluck('id')
                ->all()));
        }

        return [
            'synced_at' => now()->toIso8601String(),
            'updated_products' => $updatedProducts,
            'updated_product_items' => $updatedProductItems,
            'updated_prices' => $updatedPrices,
            'updated_inventory_balances' => $updatedInventoryBalances,
            'deleted_product_ids' => $deletedProductIds,
            'deleted_product_item_ids' => $deletedProductItemIds,
        ];
    }
}
