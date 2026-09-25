<?php

namespace App\Services\App\Transaction;

use App\Enums\PromoStatus;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryItem;
use App\Models\Master\Customer;
use App\Models\Master\ModifierGroup;
use App\Models\Master\ModifierOption;
use App\Models\Master\PaymentMethod;
use App\Models\Master\Product;
use App\Models\Master\ProductCategory;
use App\Models\Master\ProductImage;
use App\Models\Master\ProductPrice;
use App\Models\Master\VariantGroup;
use App\Models\Master\VariantGroupOption;
use App\Models\OutletDevice;
use App\Models\OutletSetting;
use App\Models\Promo;
use App\Models\Sales\Transaction;
use App\Services\App\Outlet\OutletProvisioningService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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

        $productPrices = ProductPrice::whereIn('product_id', $productIds)
            ->where(function ($q) use ($outletId) {
                $q->where('outlet_id', $outletId)->orWhereNull('outlet_id');
            })
            ->get()
            ->makeHidden('outlet_id');

        $productImages = ProductImage::whereIn('product_id', $productIds)->get();

        $variantGroups = VariantGroup::whereIn('product_id', $productIds)->get();

        $variantGroupOptions = VariantGroupOption::whereIn('variant_group_id', $variantGroups->pluck('id'))->get();

        $productModifierGroups = DB::table('product_modifier_groups')
            ->whereIn('product_id', $productIds)
            ->get();

        // 3. Data modifier
        $modifierGroups = ModifierGroup::whereIn('id', $productModifierGroups->pluck('modifier_group_id'))
            ->get()
            ->makeHidden('business_id');

        $modifierOptions = ModifierOption::whereIn('modifier_group_id', $modifierGroups->pluck('id'))->get();

        // 4. Pendukung lainnya
        $customers = Customer::where('business_id', $businessId)
            ->get()
            ->makeHidden('business_id');

        $paymentMethods = PaymentMethod::where('business_id', $businessId)
            ->activeForOutlet($outletId)
            ->get()
            ->makeHidden('business_id');

        $outletSettings = OutletSetting::where('outlet_id', $outletId)
            ->get()
            ->makeHidden('outlet_id');

        // Auto-provision if either payment methods or outlet settings are completely missing
        if ($paymentMethods->isEmpty() || $outletSettings->isEmpty()) {
            app(OutletProvisioningService::class)->provisionAll($device->outlet);

            $paymentMethods = PaymentMethod::where('business_id', $businessId)
                ->activeForOutlet($outletId)
                ->get()
                ->makeHidden('business_id');

            $outletSettings = OutletSetting::where('outlet_id', $outletId)
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
            $logoUrl = url(Storage::url($business->logo));
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
        $inventoryItems = InventoryItem::whereHas('productItem', fn ($q) => $q->whereIn('product_id', $productIds))
            ->get()
            ->makeHidden('business_id');

        $inventoryBalances = InventoryBalance::whereIn('inventory_item_id', $inventoryItems->pluck('id'))
            ->where('outlet_id', $outletId)
            ->get()
            ->makeHidden(['business_id', 'outlet_id']);

        $inventoryItemVariantGroupOptions = collect();

        // 6. Promos Aktif untuk Outlet ini
        $promos = Promo::where('business_id', $businessId)
            ->where('status', PromoStatus::Active->value)
            ->where(function ($q) use ($outletId) {
                $q->whereHas('outlets', fn ($q) => $q->where('outlets.id', $outletId))
                    ->orWhere('applies_to_all_outlets', true);
            })
            ->get()
            ->makeHidden('business_id');

        // 7. Transaksi 1 bulan terakhir
        $transactions = Transaction::with([
            'items',
            'items.modifiers',
            'payments',
            'promos',
        ])
            ->where('outlet_id', $outletId)
            ->where('created_at', '>=', now()->subMonth())
            ->get();

        $transactionData = [];
        $transactionItems = [];
        $transactionItemModifiers = [];
        $transactionPayments = [];
        $transactionPromos = [];

        foreach ($transactions as $transaction) {
            $tArray = $transaction->toArray();

            foreach ($transaction->items as $item) {
                $iArray = $item->toArray();
                foreach ($item->modifiers as $mod) {
                    $transactionItemModifiers[] = $mod->toArray();
                }
                unset($iArray['modifiers']);
                $transactionItems[] = $iArray;
            }
            unset($tArray['items']);

            foreach ($transaction->payments as $payment) {
                $transactionPayments[] = $payment->toArray();
            }
            unset($tArray['payments']);

            foreach ($transaction->promos as $promo) {
                $transactionPromos[] = $promo->toArray();
            }
            unset($tArray['promos']);

            $transactionData[] = $tArray;
        }

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
            'transactions' => $transactionData,
            'transaction_items' => $transactionItems,
            'transaction_item_modifiers' => $transactionItemModifiers,
            'transaction_payments' => $transactionPayments,
            'transaction_promos' => $transactionPromos,
        ];
    }
}
