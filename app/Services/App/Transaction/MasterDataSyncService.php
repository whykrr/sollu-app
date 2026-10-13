<?php

namespace App\Services\App\Transaction;

use App\Enums\PromotionStatus;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryItem;
use App\Models\Master\Customer;
use App\Models\Master\ModifierGroup;
use App\Models\Master\ModifierOption;
use App\Models\Master\PaymentMethod;
use App\Models\Master\Product;
use App\Models\Master\ProductCategory;
use App\Models\Master\ProductImage;
use App\Models\Master\VariantGroup;
use App\Models\Master\VariantGroupOption;
use App\Models\OutletDevice;
use App\Models\OutletSetting;
use App\Models\Promotion\Promotion;
use App\Services\App\Outlet\OutletProvisioningService;
use App\Services\Auth\UserPermissionCacheService;
use App\Services\Pos\PosPriceResolver;
use App\Services\Pos\PosSettingsPayloadBuilder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MasterDataSyncService
{
    public const MASTER_SYNC_CACHE_TTL = 60; // 60 seconds

    public function __construct(
        private readonly PosPriceResolver $priceResolver,
        private readonly PosSettingsPayloadBuilder $settingsPayloadBuilder,
    ) {}

    public function getPayload(OutletDevice $device, bool $force = false): array
    {
        $outletId = $device->outlet_id;

        return $this->buildPayload($device, $outletId);
    }

    private function buildPayload(OutletDevice $device, string $outletId): array
    {
        $outlet = $device->outlet;
        $businessId = $outlet->business_id;

        // 1. Ambil Produk & Outlet Products langsung via outlet_product table (fast index scan)
        $outletProductsRaw = DB::table('outlet_product')
            ->where('outlet_id', $outletId)
            ->where('is_enabled', true)
            ->get();

        $productIds = $outletProductsRaw->pluck('product_id')->all();

        $outletProducts = $outletProductsRaw->map(function ($item) {
            unset($item->outlet_id);

            return $item;
        });

        $products = ! empty($productIds)
            ? Product::with(['productItems.uom', 'productItems.inventoryItem'])
                ->where('business_id', $businessId)
                ->whereIn('id', $productIds)
                ->get()
                ->makeHidden('business_id')
            : collect();

        // 2. Data turunan produk
        $productCategories = ProductCategory::where('business_id', $businessId)
            ->orderBy('sort_order', 'asc')
            ->orderBy('name', 'asc')
            ->get()
            ->makeHidden('business_id');

        $productPrices = ! empty($productIds)
            ? $this->priceResolver->resolveForOutlet($products, $productIds, $outletId)
            : [];

        $productImages = ! empty($productIds)
            ? ProductImage::whereIn('product_id', $productIds)->get()
            : collect();

        $variantGroups = ! empty($productIds)
            ? VariantGroup::whereIn('product_id', $productIds)->get()
            : collect();

        $variantGroupIds = $variantGroups->pluck('id')->all();
        $variantGroupOptions = ! empty($variantGroupIds)
            ? VariantGroupOption::whereIn('variant_group_id', $variantGroupIds)->get()
            : collect();

        $productModifierGroups = ! empty($productIds)
            ? DB::table('product_modifier_groups')
                ->whereIn('product_id', $productIds)
                ->get()
            : collect();

        // 3. Data modifier dengan guards array kosong
        $modifierGroupIds = $productModifierGroups->pluck('modifier_group_id')->filter()->unique()->values()->all();
        $modifierGroups = ! empty($modifierGroupIds)
            ? ModifierGroup::whereIn('id', $modifierGroupIds)
                ->get()
                ->makeHidden('business_id')
            : collect();

        $modifierGroupPks = $modifierGroups->pluck('id')->all();
        $modifierOptions = ! empty($modifierGroupPks)
            ? ModifierOption::whereIn('modifier_group_id', $modifierGroupPks)->get()
            : collect();

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
            app(OutletProvisioningService::class)->provisionAll($outlet);

            $paymentMethods = PaymentMethod::where('business_id', $businessId)
                ->activeForOutlet($outletId)
                ->get()
                ->makeHidden('business_id');

            $outletSettings = OutletSetting::where('outlet_id', $outletId)
                ->get()
                ->makeHidden('outlet_id');
        }

        // Build structured settings object
        $structuredSettings = $this->settingsPayloadBuilder->buildStructuredSettings($outlet, $outletSettings);

        // 4b. Data Karyawan Terdaftar pada Outlet (lengkap dengan role & permissions, memanfaatkan Redis cache bersama pos:outlet:employees)
        $employeeCacheKey = "pos:outlet:{$outletId}:employees";
        $employees = Cache::remember($employeeCacheKey, 3600, function () use ($outlet, $businessId) {
            $permissionCacheService = app(UserPermissionCacheService::class);

            return $outlet->users()
                ->with(['roles:id,name,label'])
                ->select('users.id', 'users.name', 'users.email', 'users.pin', 'users.photo', 'users.is_root_user')
                ->get()
                ->map(function ($user) use ($businessId, $permissionCacheService) {
                    $role = $user->roles->first();

                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'pin' => $user->pin,
                        'photo' => $user->photo,
                        'role' => $user->is_root_user ? 'Akun Utama' : ($role?->label ?? 'Kasir'),
                        'is_root_user' => (bool) $user->is_root_user,
                        'permissions' => $permissionCacheService->getPermissions($user, $businessId),
                    ];
                })
                ->all();
        });

        // 5. Inventori Stok (optimasi direct lookup tanpa full sequential table scan)
        if (! empty($productIds)) {
            $productItemIds = DB::table('product_items')
                ->whereIn('product_id', $productIds)
                ->whereNull('deleted_at')
                ->pluck('id')
                ->all();

            $inventoryItems = ! empty($productItemIds)
                ? InventoryItem::with(['uom', 'productItem.uom'])
                    ->whereIn('product_item_id', $productItemIds)
                    ->get()
                    ->makeHidden('business_id')
                : collect();

            $inventoryItemIds = $inventoryItems->pluck('id')->all();

            $inventoryBalances = ! empty($inventoryItemIds)
                ? InventoryBalance::whereIn('inventory_item_id', $inventoryItemIds)
                    ->where('outlet_id', $outletId)
                    ->get()
                    ->makeHidden(['business_id', 'outlet_id'])
                : collect();
        } else {
            $inventoryItems = collect();
            $inventoryBalances = collect();
        }

        $inventoryItemVariantGroupOptions = collect();

        // 6. Promos Aktif untuk Outlet ini (menggunakan indexed promotion_outlets)
        $promos = Promotion::where('business_id', $businessId)
            ->where('status', PromotionStatus::Active->value)
            ->where(function ($q) use ($outletId) {
                $q->whereIn('id', DB::table('promotion_outlets')->where('outlet_id', $outletId)->select('promotion_id'))
                    ->orWhere('applies_to_all_outlets', true);
            })
            ->get()
            ->makeHidden('business_id');

        return [
            'synced_at' => now()->toIso8601String(),
            'outlet' => $this->settingsPayloadBuilder->buildOutletProfile($outlet),
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
            'employees' => $employees,
            'outlet_products' => $outletProducts,
            'inventory_items' => $inventoryItems,
            'inventory_balances' => $inventoryBalances,
            'inventory_item_variant_group_options' => $inventoryItemVariantGroupOptions,
            'promos' => $promos,
        ];
    }
}
