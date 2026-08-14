<?php

namespace App\Services\Transaction;

use App\Models\Master\Customer;
use App\Models\Master\PaymentMethod;
use App\Models\Master\Product;
use App\Models\Master\ProductCategory;
use App\Models\OutletDevice;
use Illuminate\Support\Facades\DB;

class MasterDataSyncService
{
    public function getPayload(OutletDevice $device): array
    {
        $outletId = $device->outlet_id;
        $businessId = $device->outlet->business_id;

        // 1. Ambil Produk (hanya yang aktif di outlet ini)
        $products = Product::where('business_id', $businessId)
            ->whereHas('outlets', function ($q) use ($outletId) {
                $q->where('outlet_id', $outletId)->where('is_enabled', true);
            })
            ->get()
            ->makeHidden('business_id');

        $productIds = $products->pluck('id')->toArray();

        // 2. Data turunan produk
        $productCategories = ProductCategory::where('business_id', $businessId)
            ->get()
            ->makeHidden('business_id');

        $productPrices = \App\Models\Master\ProductPrice::whereIn('product_id', $productIds)
            ->where(function ($q) use ($outletId) {
                $q->where('outlet_id', $outletId)->orWhereNull('outlet_id');
            })
            ->get()
            ->makeHidden('outlet_id');

        $productImages = \App\Models\Master\ProductImage::whereIn('product_id', $productIds)->get();

        $variantGroups = \App\Models\Master\VariantGroup::whereIn('product_id', $productIds)->get();

        $variantGroupOptions = \App\Models\Master\VariantGroupOption::whereIn('variant_group_id', $variantGroups->pluck('id'))->get();

        $productModifierGroups = DB::table('product_modifier_groups')
            ->whereIn('product_id', $productIds)
            ->get();

        // 3. Data modifier
        $modifierGroups = \App\Models\Master\ModifierGroup::whereIn('id', $productModifierGroups->pluck('modifier_group_id'))
            ->get()
            ->makeHidden('business_id');

        $modifierOptions = \App\Models\Master\ModifierOption::whereIn('modifier_group_id', $modifierGroups->pluck('id'))->get();

        // 4. Pendukung lainnya
        $customers = Customer::where('business_id', $businessId)
            ->get()
            ->makeHidden('business_id');

        $paymentMethods = PaymentMethod::where('business_id', $businessId)
            ->activeForOutlet($outletId)
            ->get()
            ->makeHidden('business_id');

        $outletSettings = \App\Models\OutletSetting::where('outlet_id', $outletId)
            ->get()
            ->makeHidden('outlet_id');

        $outletProducts = DB::table('outlet_product')
            ->where('outlet_id', $outletId)
            ->whereIn('product_id', $productIds)
            ->get()
            ->map(function ($item) {
                unset($item->outlet_id);

                return $item;
            });

        // 5. Inventori Stok
        $inventoryItems = \App\Models\Inventory\InventoryItem::whereIn('product_id', $productIds)
            ->get()
            ->makeHidden('business_id');

        $inventoryBalances = \App\Models\Inventory\InventoryBalance::whereIn('inventory_item_id', $inventoryItems->pluck('id'))
            ->where('outlet_id', $outletId)
            ->get()
            ->makeHidden(['business_id', 'outlet_id']);

        $inventoryItemVariantGroupOptions = DB::table('inventory_item_variant_group_option')
            ->whereIn('inventory_item_id', $inventoryItems->pluck('id'))
            ->get();

        return [
            'products' => $products,
            'product_categories' => $productCategories,
            'product_prices' => $productPrices,
            'product_images' => $productImages,
            'variant_groups' => $variantGroups,
            'variant_group_options' => $variantGroupOptions,
            'product_modifier_groups' => $productModifierGroups,
            'modifier_groups' => $modifierGroups,
            'modifier_options' => $modifierOptions,
            'customers' => $customers,
            'payment_methods' => $paymentMethods,
            'outlet_settings' => $outletSettings,
            'outlet_products' => $outletProducts,
            'inventory_items' => $inventoryItems,
            'inventory_balances' => $inventoryBalances,
            'inventory_item_variant_group_options' => $inventoryItemVariantGroupOptions,
        ];
    }
}
