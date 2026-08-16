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
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
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

        // Auto-provision if either payment methods or outlet settings are completely missing
        if ($paymentMethods->isEmpty() || $outletSettings->isEmpty()) {
            app(\App\Services\Outlet\OutletProvisioningService::class)->provisionAll($device->outlet);

            $paymentMethods = PaymentMethod::where('business_id', $businessId)
                ->activeForOutlet($outletId)
                ->get()
                ->makeHidden('business_id');

            $outletSettings = \App\Models\OutletSetting::where('outlet_id', $outletId)
                ->get()
                ->makeHidden('outlet_id');
        }

        // Build structured settings object
        $financialTax = (float) ($outletSettings->firstWhere('key', 'tax')?->value ?? 0.0);
        $financialServiceFee = (float) ($outletSettings->firstWhere('key', 'service_fee')?->value ?? 0.0);
        $taxIncluded = (bool) ($outletSettings->firstWhere('key', 'tax_included_in_price')?->value ?? false);
        $roundingEnabled = (bool) ($outletSettings->firstWhere('key', 'rounding_enabled')?->value ?? false);
        $roundingMode = (string) ($outletSettings->firstWhere('key', 'rounding_mode')?->value ?? 'nearest');

        $receiptSetting = $outletSettings->where('category', 'receipt')->firstWhere('key', 'layout_config')?->value ?? [
            'paper_size' => '58mm',
            'auto_print' => true,
            'print_kitchen_copy' => false,
            'print_checker_copy' => false,
            'show_logo' => true,
            'custom_header_title' => null,
            'header_notes' => 'Terima kasih atas kunjungan Anda!',
            'show_address' => true,
            'show_phone' => true,
            'show_email' => false,
            'show_cashier_name' => true,
            'show_customer_name' => true,
            'show_order_type' => true,
            'show_modifiers' => true,
            'show_item_notes' => true,
            'show_tax_detail' => true,
            'show_service_charge' => false,
            'footer_notes' => 'Barang yang sudah dibeli tidak dapat ditukar atau dikembalikan.',
            'social_media_info' => null,
            'wifi_info' => null,
            'show_qr_code' => false,
            'qr_type' => 'invoice',
        ];

        $outlet = $device->outlet;
        $business = $outlet->business;
        $logoUrl = null;
        if ($outlet->logo_url) {
            $logoUrl = str_starts_with($outlet->logo_url, 'http') ? $outlet->logo_url : url($outlet->logo_url);
        } elseif ($business?->logo) {
            $logoUrl = url(\Illuminate\Support\Facades\Storage::url($business->logo));
        }

        $receiptSetting['logo_url'] = $logoUrl;

        $structuredSettings = [
            'tax_percentage' => $financialTax,
            'service_charge_percentage' => $financialServiceFee,
            'tax_included_in_price' => $taxIncluded,
            'rounding_enabled' => $roundingEnabled,
            'rounding_mode' => $roundingMode,
            'receipt' => $receiptSetting,
        ];

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

        // 6. Promos Aktif untuk Outlet ini
        $promos = \App\Models\Promo::where('business_id', $businessId)
            ->where('status', \App\Enums\PromoStatus::Active->value)
            ->where(function ($q) use ($outletId) {
                $q->whereHas('outlets', fn ($q) => $q->where('outlets.id', $outletId))
                    ->orWhere('applies_to_all_outlets', true);
            })
            ->get()
            ->makeHidden('business_id');

        return [
            'outlet' => [
                'id' => $outlet->id,
                'name' => $outlet->name,
                'address' => $outlet->address,
                'phone' => $outlet->phone,
                'email' => $outlet->email,
                'logo_url' => $logoUrl,
            ],
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
            'settings' => $structuredSettings,
            'outlet_products' => $outletProducts,
            'inventory_items' => $inventoryItems,
            'inventory_balances' => $inventoryBalances,
            'inventory_item_variant_group_options' => $inventoryItemVariantGroupOptions,
            'promos' => $promos,
        ];
    }
}
