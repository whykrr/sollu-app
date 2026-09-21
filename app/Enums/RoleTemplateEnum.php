<?php

namespace App\Enums;

enum RoleTemplateEnum: string
{
    // F&B Templates
    case CASHIER_FNB = 'cashier_fnb';
    case WAITER = 'waiter';
    case KITCHEN_BARISTA = 'kitchen_barista';
    case MANAGER_FNB = 'manager_fnb';
    case SUPERVISOR_FNB = 'supervisor_fnb';

    // Retail Templates
    case CASHIER_RETAIL = 'cashier_retail';
    case INVENTORY_STAFF = 'inventory_staff';
    case SHOP_ASSISTANT = 'shop_assistant';
    case STORE_MANAGER_RETAIL = 'store_manager_retail';

    // Service / Jasa Templates
    case FRONT_DESK_SERVICE = 'front_desk_service';
    case SERVICE_STAFF = 'service_staff';
    case SERVICE_MANAGER = 'service_manager';

    // General / Lintas Industri
    case FINANCE_ACCOUNTING = 'finance_accounting';

    public function label(): string
    {
        return match ($this) {
            self::CASHIER_FNB => 'Kasir Resto & Kafe',
            self::WAITER => 'Pelayan / Waiter',
            self::KITCHEN_BARISTA => 'Dapur & Barista',
            self::MANAGER_FNB => 'Manajer Outlet F&B',
            self::SUPERVISOR_FNB => 'Supervisor Shift F&B',

            self::CASHIER_RETAIL => 'Kasir Toko & Retail',
            self::INVENTORY_STAFF => 'Admin Gudang & Stok',
            self::SHOP_ASSISTANT => 'Pramuniaga / Sales',
            self::STORE_MANAGER_RETAIL => 'Manajer Toko Retail',

            self::FRONT_DESK_SERVICE => 'Resepsionis & Kasir Jasa',
            self::SERVICE_STAFF => 'Terapis / Stylist / Teknisi',
            self::SERVICE_MANAGER => 'Manajer Operasional Jasa',

            self::FINANCE_ACCOUNTING => 'Staf Keuangan & Akunting',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::CASHIER_FNB => 'Transaksi kasir, pembayaran pesanan meja, cetak struk, dan buka/tutup shift kasir.',
            self::WAITER => 'Mencatat pesanan meja pelanggan (dine-in/takeaway), tambah pesanan, dan hold pesanan ke dapur.',
            self::KITCHEN_BARISTA => 'Memantau antrean pesanan masuk (KDS), melihat resep menu, dan cek ketersediaan bahan baku.',
            self::MANAGER_FNB => 'Pengawasan penuh operasional outlet, otorisasi void & diskon, stock opname, dan laporan penjualan.',
            self::SUPERVISOR_FNB => 'Pengawas shift operasional, wewenang void transaksi meja dan diskon khusus saat shift berjalan.',

            self::CASHIER_RETAIL => 'Scan barcode produk, pencatatan transaksi cepat, penerbitan nota, dan buka/tutup shift.',
            self::INVENTORY_STAFF => 'Kelola stok masuk/keluar, stock opname, purchase order ke supplier, dan terima barang vendor.',
            self::SHOP_ASSISTANT => 'Pengecekan ketersediaan stok produk & varian serta pendaftaran pelanggan baru tanpa akses kasir.',
            self::STORE_MANAGER_RETAIL => 'Kelola produk/promo, otorisasi transfer stok cabang, dan laporan audit penjualan.',

            self::FRONT_DESK_SERVICE => 'Reservasi & pembayaran layanan jasa, pencatatan member pelanggan, dan cetak nota transaksi.',
            self::SERVICE_STAFF => 'Melihat daftar antrean pelanggan, input status pengerjaan servis selesai, dan cek bahan layanan.',
            self::SERVICE_MANAGER => 'Kelola paket layanan, komisi staf, serta laporan performa shift dan kepuasan pelanggan.',

            self::FINANCE_ACCOUNTING => 'Audit seluruh laporan penjualan, arus kas kasir, laba kotor, dan ekspor berkas keuangan.',
        };
    }

    public function category(): string
    {
        return match ($this) {
            self::CASHIER_FNB,
            self::WAITER,
            self::KITCHEN_BARISTA,
            self::MANAGER_FNB,
            self::SUPERVISOR_FNB => 'fnb',

            self::CASHIER_RETAIL,
            self::INVENTORY_STAFF,
            self::SHOP_ASSISTANT,
            self::STORE_MANAGER_RETAIL => 'retail',

            self::FRONT_DESK_SERVICE,
            self::SERVICE_STAFF,
            self::SERVICE_MANAGER => 'service',

            self::FINANCE_ACCOUNTING => 'general',
        };
    }

    public function categoryLabel(): string
    {
        return match ($this->category()) {
            'fnb' => 'F&B / Kuliner',
            'retail' => 'Retail & Toko',
            'service' => 'Jasa & Layanan',
            'general' => 'Umum / Keuangan',
            default => 'Lainnya',
        };
    }

    /**
     * @return array<string>
     */
    public function businessTypes(): array
    {
        return match ($this) {
            self::CASHIER_FNB,
            self::WAITER,
            self::KITCHEN_BARISTA,
            self::MANAGER_FNB,
            self::SUPERVISOR_FNB => ['fnb', 'restaurant', 'cafe', 'bakery', 'coffee_shop'],

            self::CASHIER_RETAIL,
            self::INVENTORY_STAFF,
            self::SHOP_ASSISTANT,
            self::STORE_MANAGER_RETAIL => ['retail', 'minimarket', 'grocery', 'boutique', 'pharmacy', 'electronic'],

            self::FRONT_DESK_SERVICE,
            self::SERVICE_STAFF,
            self::SERVICE_MANAGER => ['service', 'barbershop', 'salon', 'spa', 'laundry', 'workshop'],

            self::FINANCE_ACCOUNTING => ['fnb', 'retail', 'service', 'general'],
        };
    }

    /**
     * @return array<string>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::CASHIER_FNB => [
                PermissionEnum::TRANSACTION_VIEW->value,
                PermissionEnum::TRANSACTION_CREATE->value,
                PermissionEnum::TRANSACTION_HOLD->value,
                PermissionEnum::TRANSACTION_REPRINT->value,
                PermissionEnum::TRANSACTION_RECORD_PAYMENT->value,
                PermissionEnum::TRANSACTION_OPEN_SHIFT->value,
                PermissionEnum::TRANSACTION_CLOSE_SHIFT->value,
                PermissionEnum::PRODUCT_VIEW->value,
                PermissionEnum::CUSTOMER_VIEW->value,
                PermissionEnum::CUSTOMER_CREATE->value,
            ],

            self::WAITER => [
                PermissionEnum::TRANSACTION_VIEW->value,
                PermissionEnum::TRANSACTION_CREATE->value,
                PermissionEnum::TRANSACTION_HOLD->value,
                PermissionEnum::PRODUCT_VIEW->value,
                PermissionEnum::CUSTOMER_VIEW->value,
            ],

            self::KITCHEN_BARISTA => [
                PermissionEnum::TRANSACTION_VIEW->value,
                PermissionEnum::PRODUCT_VIEW->value,
                PermissionEnum::PRODUCT_RECIPE->value,
                PermissionEnum::INVENTORY_VIEW->value,
                PermissionEnum::INVENTORY_STOCK_OPNAME->value,
            ],

            self::MANAGER_FNB => [
                PermissionEnum::OUTLET_VIEW->value,
                PermissionEnum::TRANSACTION_ALL->value,
                PermissionEnum::TRANSACTION_VIEW->value,
                PermissionEnum::TRANSACTION_CREATE->value,
                PermissionEnum::TRANSACTION_UPDATE->value,
                PermissionEnum::TRANSACTION_CANCEL->value,
                PermissionEnum::TRANSACTION_REFUND->value,
                PermissionEnum::TRANSACTION_DISCOUNT->value,
                PermissionEnum::TRANSACTION_HOLD->value,
                PermissionEnum::TRANSACTION_VOID->value,
                PermissionEnum::TRANSACTION_REPRINT->value,
                PermissionEnum::TRANSACTION_OPEN_SHIFT->value,
                PermissionEnum::TRANSACTION_CLOSE_SHIFT->value,
                PermissionEnum::TRANSACTION_ISSUE_INVOICE->value,
                PermissionEnum::TRANSACTION_RECORD_PAYMENT->value,
                PermissionEnum::PRODUCT_VIEW->value,
                PermissionEnum::PRODUCT_UPDATE->value,
                PermissionEnum::CATEGORY_VIEW->value,
                PermissionEnum::INVENTORY_VIEW->value,
                PermissionEnum::INVENTORY_ADJUSTMENT_READ->value,
                PermissionEnum::INVENTORY_ADJUSTMENT_CREATE->value,
                PermissionEnum::INVENTORY_ADJUSTMENT_APPROVE->value,
                PermissionEnum::INVENTORY_ADJUSTMENT_EXPORT->value,
                PermissionEnum::INVENTORY_ADJUSTMENT_FREEZE->value,
                PermissionEnum::INVENTORY_TRANSFER_READ->value,
                PermissionEnum::INVENTORY_TRANSFER_CREATE->value,
                PermissionEnum::INVENTORY_TRANSFER_UPDATE->value,
                PermissionEnum::INVENTORY_TRANSFER_APPROVE->value,
                PermissionEnum::INVENTORY_TRANSFER_SHIP->value,
                PermissionEnum::INVENTORY_TRANSFER_RECEIVE->value,
                PermissionEnum::INVENTORY_STOCK_OPNAME->value,
                PermissionEnum::PURCHASE_ORDER_VIEW->value,
                PermissionEnum::PURCHASE_ORDER_CREATE->value,
                PermissionEnum::PURCHASE_ORDER_UPDATE->value,
                PermissionEnum::PURCHASE_ORDER_APPROVE->value,
                PermissionEnum::PURCHASE_ORDER_CANCEL->value,
                PermissionEnum::PURCHASE_ORDER_RECEIVE->value,
                PermissionEnum::PURCHASE_ORDER_VOID->value,
                PermissionEnum::PURCHASE_ORDER_RETURN->value,
                PermissionEnum::PROMO_VIEW->value,
                PermissionEnum::CUSTOMER_VIEW->value,
                PermissionEnum::CUSTOMER_CREATE->value,
                PermissionEnum::CUSTOMER_UPDATE->value,
                PermissionEnum::REPORT_SALES->value,
                PermissionEnum::REPORT_INVENTORY->value,
                PermissionEnum::REPORT_SHIFT->value,
                PermissionEnum::REPORT_PRODUCT->value,
            ],

            self::SUPERVISOR_FNB => [
                PermissionEnum::TRANSACTION_VIEW->value,
                PermissionEnum::TRANSACTION_CREATE->value,
                PermissionEnum::TRANSACTION_UPDATE->value,
                PermissionEnum::TRANSACTION_CANCEL->value,
                PermissionEnum::TRANSACTION_DISCOUNT->value,
                PermissionEnum::TRANSACTION_HOLD->value,
                PermissionEnum::TRANSACTION_VOID->value,
                PermissionEnum::TRANSACTION_REPRINT->value,
                PermissionEnum::TRANSACTION_OPEN_SHIFT->value,
                PermissionEnum::TRANSACTION_CLOSE_SHIFT->value,
                PermissionEnum::PRODUCT_VIEW->value,
                PermissionEnum::INVENTORY_VIEW->value,
                PermissionEnum::INVENTORY_STOCK_OPNAME->value,
                PermissionEnum::REPORT_SHIFT->value,
            ],

            self::CASHIER_RETAIL => [
                PermissionEnum::TRANSACTION_VIEW->value,
                PermissionEnum::TRANSACTION_CREATE->value,
                PermissionEnum::TRANSACTION_HOLD->value,
                PermissionEnum::TRANSACTION_REPRINT->value,
                PermissionEnum::TRANSACTION_RECORD_PAYMENT->value,
                PermissionEnum::TRANSACTION_ISSUE_INVOICE->value,
                PermissionEnum::TRANSACTION_OPEN_SHIFT->value,
                PermissionEnum::TRANSACTION_CLOSE_SHIFT->value,
                PermissionEnum::PRODUCT_VIEW->value,
                PermissionEnum::CUSTOMER_VIEW->value,
                PermissionEnum::CUSTOMER_CREATE->value,
            ],

            self::INVENTORY_STAFF => [
                PermissionEnum::PRODUCT_VIEW->value,
                PermissionEnum::CATEGORY_VIEW->value,
                PermissionEnum::INVENTORY_ALL->value,
                PermissionEnum::INVENTORY_VIEW->value,
                PermissionEnum::INVENTORY_ADJUSTMENT_READ->value,
                PermissionEnum::INVENTORY_ADJUSTMENT_CREATE->value,
                PermissionEnum::INVENTORY_ADJUSTMENT_APPROVE->value,
                PermissionEnum::INVENTORY_ADJUSTMENT_EXPORT->value,
                PermissionEnum::INVENTORY_ADJUSTMENT_FREEZE->value,
                PermissionEnum::INVENTORY_TRANSFER_READ->value,
                PermissionEnum::INVENTORY_TRANSFER_CREATE->value,
                PermissionEnum::INVENTORY_TRANSFER_UPDATE->value,
                PermissionEnum::INVENTORY_TRANSFER_APPROVE->value,
                PermissionEnum::INVENTORY_TRANSFER_SHIP->value,
                PermissionEnum::INVENTORY_TRANSFER_RECEIVE->value,
                PermissionEnum::INVENTORY_STOCK_OPNAME->value,
                PermissionEnum::INVENTORY_MOVEMENT->value,
                PermissionEnum::INVENTORY_PURCHASE->value,
                PermissionEnum::INVENTORY_WASTE->value,
                PermissionEnum::INVENTORY_RECEIVE->value,
                PermissionEnum::SUPPLIER_VIEW->value,
                PermissionEnum::SUPPLIER_CREATE->value,
                PermissionEnum::SUPPLIER_UPDATE->value,
                PermissionEnum::PURCHASE_ORDER_VIEW->value,
                PermissionEnum::PURCHASE_ORDER_CREATE->value,
                PermissionEnum::PURCHASE_ORDER_UPDATE->value,
                PermissionEnum::PURCHASE_ORDER_RECEIVE->value,
                PermissionEnum::PURCHASE_ORDER_VOID->value,
                PermissionEnum::PURCHASE_ORDER_RETURN->value,
                PermissionEnum::REPORT_INVENTORY->value,
            ],

            self::SHOP_ASSISTANT => [
                PermissionEnum::PRODUCT_VIEW->value,
                PermissionEnum::CATEGORY_VIEW->value,
                PermissionEnum::CUSTOMER_VIEW->value,
                PermissionEnum::CUSTOMER_CREATE->value,
            ],

            self::STORE_MANAGER_RETAIL => [
                PermissionEnum::OUTLET_VIEW->value,
                PermissionEnum::TRANSACTION_ALL->value,
                PermissionEnum::TRANSACTION_VIEW->value,
                PermissionEnum::TRANSACTION_CREATE->value,
                PermissionEnum::TRANSACTION_UPDATE->value,
                PermissionEnum::TRANSACTION_CANCEL->value,
                PermissionEnum::TRANSACTION_REFUND->value,
                PermissionEnum::TRANSACTION_DISCOUNT->value,
                PermissionEnum::TRANSACTION_HOLD->value,
                PermissionEnum::TRANSACTION_VOID->value,
                PermissionEnum::TRANSACTION_REPRINT->value,
                PermissionEnum::TRANSACTION_OPEN_SHIFT->value,
                PermissionEnum::TRANSACTION_CLOSE_SHIFT->value,
                PermissionEnum::TRANSACTION_ISSUE_INVOICE->value,
                PermissionEnum::TRANSACTION_RECORD_PAYMENT->value,
                PermissionEnum::PRODUCT_VIEW->value,
                PermissionEnum::PRODUCT_CREATE->value,
                PermissionEnum::PRODUCT_UPDATE->value,
                PermissionEnum::CATEGORY_VIEW->value,
                PermissionEnum::INVENTORY_VIEW->value,
                PermissionEnum::INVENTORY_ADJUSTMENT_READ->value,
                PermissionEnum::INVENTORY_ADJUSTMENT_CREATE->value,
                PermissionEnum::INVENTORY_ADJUSTMENT_APPROVE->value,
                PermissionEnum::INVENTORY_TRANSFER_READ->value,
                PermissionEnum::INVENTORY_TRANSFER_CREATE->value,
                PermissionEnum::INVENTORY_TRANSFER_APPROVE->value,
                PermissionEnum::INVENTORY_STOCK_OPNAME->value,
                PermissionEnum::PURCHASE_ORDER_VIEW->value,
                PermissionEnum::PURCHASE_ORDER_CREATE->value,
                PermissionEnum::PURCHASE_ORDER_UPDATE->value,
                PermissionEnum::PURCHASE_ORDER_APPROVE->value,
                PermissionEnum::PURCHASE_ORDER_CANCEL->value,
                PermissionEnum::PURCHASE_ORDER_RECEIVE->value,
                PermissionEnum::PURCHASE_ORDER_VOID->value,
                PermissionEnum::PURCHASE_ORDER_RETURN->value,
                PermissionEnum::PROMO_VIEW->value,
                PermissionEnum::PROMO_CREATE->value,
                PermissionEnum::CUSTOMER_VIEW->value,
                PermissionEnum::CUSTOMER_CREATE->value,
                PermissionEnum::CUSTOMER_UPDATE->value,
                PermissionEnum::REPORT_SALES->value,
                PermissionEnum::REPORT_INVENTORY->value,
                PermissionEnum::REPORT_SHIFT->value,
                PermissionEnum::REPORT_PRODUCT->value,
            ],

            self::FRONT_DESK_SERVICE => [
                PermissionEnum::TRANSACTION_VIEW->value,
                PermissionEnum::TRANSACTION_CREATE->value,
                PermissionEnum::TRANSACTION_HOLD->value,
                PermissionEnum::TRANSACTION_REPRINT->value,
                PermissionEnum::TRANSACTION_RECORD_PAYMENT->value,
                PermissionEnum::TRANSACTION_ISSUE_INVOICE->value,
                PermissionEnum::TRANSACTION_OPEN_SHIFT->value,
                PermissionEnum::TRANSACTION_CLOSE_SHIFT->value,
                PermissionEnum::PRODUCT_VIEW->value,
                PermissionEnum::CUSTOMER_VIEW->value,
                PermissionEnum::CUSTOMER_CREATE->value,
                PermissionEnum::CUSTOMER_UPDATE->value,
            ],

            self::SERVICE_STAFF => [
                PermissionEnum::TRANSACTION_VIEW->value,
                PermissionEnum::PRODUCT_VIEW->value,
                PermissionEnum::CUSTOMER_VIEW->value,
                PermissionEnum::INVENTORY_VIEW->value,
            ],

            self::SERVICE_MANAGER => [
                PermissionEnum::OUTLET_VIEW->value,
                PermissionEnum::TRANSACTION_ALL->value,
                PermissionEnum::TRANSACTION_VIEW->value,
                PermissionEnum::TRANSACTION_CREATE->value,
                PermissionEnum::TRANSACTION_UPDATE->value,
                PermissionEnum::TRANSACTION_CANCEL->value,
                PermissionEnum::TRANSACTION_REFUND->value,
                PermissionEnum::TRANSACTION_DISCOUNT->value,
                PermissionEnum::TRANSACTION_HOLD->value,
                PermissionEnum::TRANSACTION_VOID->value,
                PermissionEnum::TRANSACTION_REPRINT->value,
                PermissionEnum::TRANSACTION_OPEN_SHIFT->value,
                PermissionEnum::TRANSACTION_CLOSE_SHIFT->value,
                PermissionEnum::PRODUCT_VIEW->value,
                PermissionEnum::PRODUCT_UPDATE->value,
                PermissionEnum::PROMO_VIEW->value,
                PermissionEnum::PROMO_CREATE->value,
                PermissionEnum::CUSTOMER_VIEW->value,
                PermissionEnum::CUSTOMER_CREATE->value,
                PermissionEnum::CUSTOMER_UPDATE->value,
                PermissionEnum::REPORT_SALES->value,
                PermissionEnum::REPORT_SHIFT->value,
                PermissionEnum::REPORT_PRODUCT->value,
                PermissionEnum::REPORT_CUSTOMER->value,
            ],

            self::FINANCE_ACCOUNTING => [
                PermissionEnum::REPORT_ALL->value,
                PermissionEnum::REPORT_SALES->value,
                PermissionEnum::REPORT_INVENTORY->value,
                PermissionEnum::REPORT_CASHFLOW->value,
                PermissionEnum::REPORT_SHIFT->value,
                PermissionEnum::REPORT_PRODUCT->value,
                PermissionEnum::REPORT_CUSTOMER->value,
                PermissionEnum::REPORT_EXPORT->value,
                PermissionEnum::TRANSACTION_VIEW->value,
                PermissionEnum::PURCHASE_ORDER_VIEW->value,
                PermissionEnum::INVENTORY_VIEW->value,
            ],
        };
    }

    /**
     * Hitung ringkasan kelompok hak akses aktif untuk template ini.
     *
     * @return array<int, array{key: string, label: string, count: int}>
     */
    public function summaryGroups(): array
    {
        $permissions = $this->permissions();
        $groupCounts = [];

        foreach ($permissions as $permValue) {
            $permEnum = PermissionEnum::tryFrom($permValue);
            if (! $permEnum) {
                continue;
            }

            $grpKey = $permEnum->group();
            $grpLabel = $permEnum->groupLabel();

            if (! isset($groupCounts[$grpKey])) {
                $groupCounts[$grpKey] = [
                    'key' => $grpKey,
                    'label' => $grpLabel,
                    'count' => 0,
                ];
            }

            $groupCounts[$grpKey]['count']++;
        }

        return array_values($groupCounts);
    }

    /**
     * Format array metadata template peran untuk antarmuka pengguna (Frontend).
     *
     * @return array<int, array{key: string, label: string, description: string, category: string, category_label: string, business_types: array<string>, permissions: array<string>, permissions_count: int, summary_groups: array<int, array{key: string, label: string, count: int}>}>
     */
    public static function formattedList(): array
    {
        return collect(self::cases())->map(function (self $case) {
            return [
                'key' => $case->value,
                'label' => $case->label(),
                'description' => $case->description(),
                'category' => $case->category(),
                'category_label' => $case->categoryLabel(),
                'business_types' => $case->businessTypes(),
                'permissions' => $case->permissions(),
                'permissions_count' => count($case->permissions()),
                'summary_groups' => $case->summaryGroups(),
            ];
        })->values()->toArray();
    }
}
