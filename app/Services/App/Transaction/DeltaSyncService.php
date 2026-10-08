<?php

declare(strict_types=1);

namespace App\Services\App\Transaction;

use App\Models\Inventory\InventoryBalance;
use App\Models\Master\Product;
use App\Models\Master\ProductItem;
use App\Models\Master\ProductPrice;
use App\Models\OutletDevice;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DeltaSyncService
{
    /**
     * Build lightweight, query-level optimized delta master catalog payload.
     * Zero cache policy: direct index seek without Redis cache overhead.
     */
    public function getDelta(OutletDevice $device, string $updatedSince): array
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

        // 2. Produk yang dibuat atau diperbarui sejak updated_since
        $updatedProducts = Product::with(['productItems.uom'])
            ->where('business_id', $businessId)
            ->whereIn('id', $productIds)
            ->where('updated_at', '>', $since)
            ->get()
            ->makeHidden('business_id');

        // 3. Product items (varian, SKU, barcode) yang dibuat atau diperbarui sejak updated_since
        $updatedProductItems = ProductItem::with(['uom', 'inventoryItem'])
            ->where('business_id', $businessId)
            ->whereIn('product_id', $productIds)
            ->where('updated_at', '>', $since)
            ->get()
            ->makeHidden('business_id');

        // 4. Harga produk yang diperbarui sejak updated_since
        $updatedPrices = ProductPrice::whereIn('product_id', $productIds)
            ->where(function ($q) use ($outletId) {
                $q->where('outlet_id', $outletId)
                    ->orWhereNull('outlet_id');
            })
            ->where('updated_at', '>', $since)
            ->get()
            ->makeHidden('outlet_id');

        // 5. Saldo stok inventori yang bermutasi sejak updated_since pada outlet ini
        $updatedInventoryBalances = InventoryBalance::where('outlet_id', $outletId)
            ->where('updated_at', '>', $since)
            ->get()
            ->makeHidden(['business_id', 'outlet_id']);

        // 6. Produk yang di-soft-delete atau dinonaktifkan di outlet_product sejak updated_since
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

        // 7. Product items yang di-soft-delete sejak updated_since
        $deletedProductItemIds = ProductItem::withTrashed()
            ->where('business_id', $businessId)
            ->whereNotNull('deleted_at')
            ->where('deleted_at', '>', $since)
            ->pluck('id')
            ->all();

        return [
            'synced_at' => now()->toIso8601String(),
            'updated_products' => $updatedProducts,
            'updated_product_items' => $updatedProductItems,
            'updated_prices' => $updatedPrices,
            'updated_inventory_balances' => $updatedInventoryBalances,
            'deleted_product_ids' => $deletedProductIds,
            'deleted_product_item_ids' => array_values(array_unique($deletedProductItemIds)),
        ];
    }
}
