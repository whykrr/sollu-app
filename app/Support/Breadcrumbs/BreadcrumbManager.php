<?php

namespace App\Support\Breadcrumbs;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Throwable;

class BreadcrumbManager
{
    /**
     * Generate breadcrumbs for App domain.
     *
     * @return array<int, array{label: string, url: ?string}>
     */
    public static function forApp(?Request $request = null): array
    {
        $request ??= request();
        $routeName = $request && $request->route() ? $request->route()->getName() : null;

        return (new static)->generate($routeName, 'app', $request);
    }

    /**
     * Generate breadcrumbs for Cockpit domain.
     *
     * @return array<int, array{label: string, url: ?string}>
     */
    public static function forCockpit(?Request $request = null): array
    {
        $request ??= request();
        $routeName = $request && $request->route() ? $request->route()->getName() : null;

        return (new static)->generate($routeName, 'cockpit', $request);
    }

    /**
     * Generate breadcrumb items based on route name and scope.
     *
     * @return array<int, array{label: string, url: ?string}>
     */
    public function generate(?string $routeName, string $scope = 'app', ?Request $request = null): array
    {
        // 1. Check for request-level custom override
        if ($request && $customCrumbs = $request->attributes->get('breadcrumbs')) {
            if (is_array($customCrumbs) && ! empty($customCrumbs)) {
                return $this->formatItems($customCrumbs);
            }
        }

        if (empty($routeName)) {
            return $this->getDefaultBreadcrumbs($scope);
        }

        // 2. Lookup in explicit registry
        $registry = $scope === 'cockpit' ? $this->getCockpitRegistry() : $this->getAppRegistry();

        if (isset($registry[$routeName])) {
            $items = is_callable($registry[$routeName])
                ? call_user_func($registry[$routeName], $request)
                : $registry[$routeName];

            return $this->formatItems($items);
        }

        // 3. Fallback: Auto-generate hierarchical breadcrumbs from route name
        return $this->autoGenerate($routeName, $scope);
    }

    /**
     * Safe URL route resolver.
     *
     * @param  array<string, mixed>  $parameters
     */
    public static function safeRoute(?string $name, array $parameters = []): ?string
    {
        if (empty($name)) {
            return null;
        }

        try {
            if (Route::has($name)) {
                return route($name, $parameters);
            }
        } catch (Throwable) {
            return null;
        }

        return null;
    }

    /**
     * Default breadcrumb trail when route name is missing or root.
     *
     * @return array<int, array{label: string, url: ?string}>
     */
    protected function getDefaultBreadcrumbs(string $scope): array
    {
        if ($scope === 'cockpit') {
            return [
                ['label' => 'Dashboard', 'url' => self::safeRoute('cockpit.dashboard')],
            ];
        }

        return [
            ['label' => 'Overview', 'url' => self::safeRoute('overview')],
        ];
    }

    /**
     * Registry for App routes.
     *
     * @return array<string, array<int, array{label: string, url: ?string}>|callable>
     */
    protected function getAppRegistry(): array
    {
        return [
            // Overview
            'overview' => [
                ['label' => 'Overview', 'url' => self::safeRoute('overview')],
            ],

            // Employees Module
            'employees.index' => [
                ['label' => 'Pegawai', 'url' => self::safeRoute('employees.index')],
            ],
            'employees.show' => [
                ['label' => 'Pegawai', 'url' => self::safeRoute('employees.index')],
                ['label' => 'Detail Pegawai', 'url' => null],
            ],

            // Master Products Module
            'master.categories.index' => [
                ['label' => 'Master Produk', 'url' => null],
                ['label' => 'Kategori Produk', 'url' => self::safeRoute('master.categories.index')],
            ],
            'master.modifiers.index' => [
                ['label' => 'Master Produk', 'url' => null],
                ['label' => 'Opsi Tambahan', 'url' => self::safeRoute('master.modifiers.index')],
            ],
            'master.products.index' => [
                ['label' => 'Master Produk', 'url' => null],
                ['label' => 'Produk', 'url' => self::safeRoute('master.products.index')],
            ],
            'master.products.create' => [
                ['label' => 'Master Produk', 'url' => null],
                ['label' => 'Produk', 'url' => self::safeRoute('master.products.index')],
                ['label' => 'Tambah Produk', 'url' => null],
            ],
            'master.products.edit' => [
                ['label' => 'Master Produk', 'url' => null],
                ['label' => 'Produk', 'url' => self::safeRoute('master.products.index')],
                ['label' => 'Edit Produk', 'url' => null],
            ],

            // Inventories Module
            'inventories.stocks.index' => [
                ['label' => 'Inventori', 'url' => null],
                ['label' => 'Stok Produk', 'url' => self::safeRoute('inventories.stocks.index')],
            ],
            'inventories.stocks.show' => [
                ['label' => 'Inventori', 'url' => null],
                ['label' => 'Stok Produk', 'url' => self::safeRoute('inventories.stocks.index')],
                ['label' => 'Detail Stok', 'url' => null],
            ],
            'inventories.movements.index' => [
                ['label' => 'Inventori', 'url' => null],
                ['label' => 'Pergerakan Stok', 'url' => self::safeRoute('inventories.movements.index')],
            ],
            'inventory.raw-materials.index' => [
                ['label' => 'Inventori', 'url' => null],
                ['label' => 'Bahan Baku', 'url' => self::safeRoute('inventory.raw-materials.index')],
            ],
            'inventory.suppliers.index' => [
                ['label' => 'Inventori', 'url' => null],
                ['label' => 'Pemasok / Supplier', 'url' => self::safeRoute('inventory.suppliers.index')],
            ],
            'inventory.suppliers.show' => [
                ['label' => 'Inventori', 'url' => null],
                ['label' => 'Pemasok / Supplier', 'url' => self::safeRoute('inventory.suppliers.index')],
                ['label' => 'Detail Pemasok', 'url' => null],
            ],
            'inventory.purchases.index' => [
                ['label' => 'Inventori', 'url' => null],
                ['label' => 'Pembelian (PO)', 'url' => self::safeRoute('inventory.purchases.index')],
            ],
            'inventory.purchases.show' => [
                ['label' => 'Inventori', 'url' => null],
                ['label' => 'Pembelian (PO)', 'url' => self::safeRoute('inventory.purchases.index')],
                ['label' => 'Detail Pembelian', 'url' => null],
            ],
            'inventory.opnames.index' => [
                ['label' => 'Inventori', 'url' => null],
                ['label' => 'Stok Opname', 'url' => self::safeRoute('inventory.opnames.index')],
            ],
            'inventory.opnames.show' => [
                ['label' => 'Inventori', 'url' => null],
                ['label' => 'Stok Opname', 'url' => self::safeRoute('inventory.opnames.index')],
                ['label' => 'Detail Opname', 'url' => null],
            ],
            'inventory.adjustments.index' => [
                ['label' => 'Inventori', 'url' => null],
                ['label' => 'Penyesuaian Stok', 'url' => self::safeRoute('inventory.adjustments.index')],
            ],
            'inventory.adjustments.show' => [
                ['label' => 'Inventori', 'url' => null],
                ['label' => 'Penyesuaian Stok', 'url' => self::safeRoute('inventory.adjustments.index')],
                ['label' => 'Detail Penyesuaian', 'url' => null],
            ],
            'inventory.transfers.index' => [
                ['label' => 'Inventori', 'url' => null],
                ['label' => 'Mutasi Stok', 'url' => self::safeRoute('inventory.transfers.index')],
            ],
            'inventory.transfers.show' => [
                ['label' => 'Inventori', 'url' => null],
                ['label' => 'Mutasi Stok', 'url' => self::safeRoute('inventory.transfers.index')],
                ['label' => 'Detail Mutasi', 'url' => null],
            ],

            // Transactions Module
            'transactions.sales.index' => [
                ['label' => 'Transaksi', 'url' => null],
                ['label' => 'Riwayat Penjualan', 'url' => self::safeRoute('transactions.sales.index')],
            ],
            'transactions.sales.show' => [
                ['label' => 'Transaksi', 'url' => null],
                ['label' => 'Riwayat Penjualan', 'url' => self::safeRoute('transactions.sales.index')],
                ['label' => 'Detail Penjualan', 'url' => null],
            ],
            'transactions.sales.invoices.index' => [
                ['label' => 'Transaksi', 'url' => null],
                ['label' => 'Invoice Piutang', 'url' => self::safeRoute('transactions.sales.invoices.index')],
            ],
            'transactions.sales.invoices.create' => [
                ['label' => 'Transaksi', 'url' => null],
                ['label' => 'Invoice Piutang', 'url' => self::safeRoute('transactions.sales.invoices.index')],
                ['label' => 'Buat Invoice', 'url' => null],
            ],
            'transactions.sales.invoices.show' => [
                ['label' => 'Transaksi', 'url' => null],
                ['label' => 'Invoice Piutang', 'url' => self::safeRoute('transactions.sales.invoices.index')],
                ['label' => 'Detail Invoice', 'url' => null],
            ],
            'transactions.shifts.index' => [
                ['label' => 'Transaksi', 'url' => null],
                ['label' => 'Shift Kasir', 'url' => self::safeRoute('transactions.shifts.index')],
            ],
            'transactions.shifts.show' => [
                ['label' => 'Transaksi', 'url' => null],
                ['label' => 'Shift Kasir', 'url' => self::safeRoute('transactions.shifts.index')],
                ['label' => 'Detail Shift', 'url' => null],
            ],

            // Customer Module
            'customers.index' => [
                ['label' => 'Pelanggan', 'url' => self::safeRoute('customers.index')],
            ],
            'customers.show' => [
                ['label' => 'Pelanggan', 'url' => self::safeRoute('customers.index')],
                ['label' => 'Detail Pelanggan', 'url' => null],
            ],

            // Promotions Module
            'promotions.index' => [
                ['label' => 'Promosi & Diskon', 'url' => self::safeRoute('promotions.index')],
            ],
            'promotions.show' => [
                ['label' => 'Promosi & Diskon', 'url' => self::safeRoute('promotions.index')],
                ['label' => 'Detail Promosi', 'url' => null],
            ],

            // Reports Module
            'reports.sales.index' => [
                ['label' => 'Laporan', 'url' => null],
                ['label' => 'Laporan Penjualan', 'url' => self::safeRoute('reports.sales.index')],
            ],
            'reports.products.index' => [
                ['label' => 'Laporan', 'url' => null],
                ['label' => 'Laporan Produk', 'url' => self::safeRoute('reports.products.index')],
            ],
            'reports.stocks.index' => [
                ['label' => 'Laporan', 'url' => null],
                ['label' => 'Laporan Stok & Aset', 'url' => self::safeRoute('reports.stocks.index')],
            ],
            'reports.cashiers.index' => [
                ['label' => 'Laporan', 'url' => null],
                ['label' => 'Laporan Kasir (Shift)', 'url' => self::safeRoute('reports.cashiers.index')],
            ],
            'reports.promotions.index' => [
                ['label' => 'Laporan', 'url' => null],
                ['label' => 'Laporan Promosi', 'url' => self::safeRoute('reports.promotions.index')],
            ],
            'reports.customers.index' => [
                ['label' => 'Laporan', 'url' => null],
                ['label' => 'Laporan Pelanggan', 'url' => self::safeRoute('reports.customers.index')],
            ],

            // Settings Module
            'settings.account.profile' => [
                ['label' => 'Pengaturan', 'url' => null],
                ['label' => 'Pusat Akun', 'url' => self::safeRoute('settings.account.profile')],
            ],
            'settings.business.detail' => [
                ['label' => 'Pengaturan', 'url' => null],
                ['label' => 'Detail Usaha', 'url' => self::safeRoute('settings.business.detail')],
            ],
            'settings.business.features' => [
                ['label' => 'Pengaturan', 'url' => null],
                ['label' => 'Fitur Modul', 'url' => self::safeRoute('settings.business.features')],
            ],
            'settings.outlets.index' => [
                ['label' => 'Pengaturan', 'url' => null],
                ['label' => 'Outlet', 'url' => self::safeRoute('settings.outlets.index')],
            ],
            'settings.outlets.show' => [
                ['label' => 'Pengaturan', 'url' => null],
                ['label' => 'Outlet', 'url' => self::safeRoute('settings.outlets.index')],
                ['label' => 'Detail Outlet', 'url' => null],
            ],
            'settings.roles.index' => [
                ['label' => 'Pengaturan', 'url' => null],
                ['label' => 'Hak Akses & Peran', 'url' => self::safeRoute('settings.roles.index')],
            ],
            'settings.roles.show' => [
                ['label' => 'Pengaturan', 'url' => null],
                ['label' => 'Hak Akses & Peran', 'url' => self::safeRoute('settings.roles.index')],
                ['label' => 'Detail Peran', 'url' => null],
            ],
            'settings.billing.index' => [
                ['label' => 'Pengaturan', 'url' => null],
                ['label' => 'Langganan & Tagihan', 'url' => self::safeRoute('settings.billing.index')],
            ],
            'settings.billing.plans' => [
                ['label' => 'Pengaturan', 'url' => null],
                ['label' => 'Langganan & Tagihan', 'url' => self::safeRoute('settings.billing.index')],
                ['label' => 'Pilih Paket', 'url' => self::safeRoute('settings.billing.plans')],
            ],
            'settings.billing.checkout' => [
                ['label' => 'Pengaturan', 'url' => null],
                ['label' => 'Langganan & Tagihan', 'url' => self::safeRoute('settings.billing.index')],
                ['label' => 'Checkout', 'url' => null],
            ],
            'settings.billing.invoices.show' => [
                ['label' => 'Pengaturan', 'url' => null],
                ['label' => 'Langganan & Tagihan', 'url' => self::safeRoute('settings.billing.index')],
                ['label' => 'Detail Tagihan', 'url' => null],
            ],
            'settings.payment-methods.index' => [
                ['label' => 'Pengaturan', 'url' => null],
                ['label' => 'Metode Pembayaran', 'url' => self::safeRoute('settings.payment-methods.index')],
            ],
            'settings.receipt.index' => [
                ['label' => 'Pengaturan', 'url' => null],
                ['label' => 'Struk Pembayaran', 'url' => self::safeRoute('settings.receipt.index')],
            ],
            'settings.devices.index' => [
                ['label' => 'Pengaturan', 'url' => null],
                ['label' => 'Perangkat POS', 'url' => self::safeRoute('settings.devices.index')],
            ],
            'settings.taxes.index' => [
                ['label' => 'Pengaturan', 'url' => null],
                ['label' => 'Pajak & Biaya Layanan', 'url' => self::safeRoute('settings.taxes.index')],
            ],
            'settings.operational.index' => [
                ['label' => 'Pengaturan', 'url' => null],
                ['label' => 'Jam Operasional', 'url' => self::safeRoute('settings.operational.index')],
            ],

            // Template Dev Routes
            'template.form' => [
                ['label' => 'Template', 'url' => null],
                ['label' => 'Form Components', 'url' => null],
            ],
            'template.cards' => [
                ['label' => 'Template', 'url' => null],
                ['label' => 'Cards & Layout', 'url' => null],
            ],
            'template.navigation' => [
                ['label' => 'Template', 'url' => null],
                ['label' => 'Navigasi', 'url' => null],
            ],
            'template.buttons' => [
                ['label' => 'Template', 'url' => null],
                ['label' => 'Buttons & Badges', 'url' => null],
            ],
            'template.charts' => [
                ['label' => 'Template', 'url' => null],
                ['label' => 'Grafik & Metrik', 'url' => null],
            ],
            'template.notifications' => [
                ['label' => 'Template', 'url' => null],
                ['label' => 'Notifikasi & Modal', 'url' => null],
            ],
            'template.widgets' => [
                ['label' => 'Template', 'url' => null],
                ['label' => 'Widgets', 'url' => null],
            ],
        ];
    }

    /**
     * Registry for Cockpit routes.
     *
     * @return array<string, array<int, array{label: string, url: ?string}>|callable>
     */
    protected function getCockpitRegistry(): array
    {
        return [
            // Dashboard
            'cockpit.dashboard' => [
                ['label' => 'Dashboard', 'url' => self::safeRoute('cockpit.dashboard')],
            ],

            // Merchants / Businesses
            'cockpit.merchants.index' => [
                ['label' => 'Bisnis & Merchant', 'url' => self::safeRoute('cockpit.merchants.index')],
            ],
            'cockpit.merchants.show' => [
                ['label' => 'Bisnis & Merchant', 'url' => self::safeRoute('cockpit.merchants.index')],
                ['label' => 'Detail Bisnis', 'url' => null],
            ],

            // Invoices
            'cockpit.invoices.index' => [
                ['label' => 'Tagihan & Pembayaran', 'url' => null],
                ['label' => 'Invoice Langganan', 'url' => self::safeRoute('cockpit.invoices.index')],
            ],
            'cockpit.invoices.show' => [
                ['label' => 'Tagihan & Pembayaran', 'url' => null],
                ['label' => 'Invoice Langganan', 'url' => self::safeRoute('cockpit.invoices.index')],
                ['label' => 'Detail Invoice', 'url' => null],
            ],

            // Subscription Plans
            'cockpit.subscription-plans.index' => [
                ['label' => 'Paket & Fitur', 'url' => null],
                ['label' => 'Paket Langganan', 'url' => self::safeRoute('cockpit.subscription-plans.index')],
            ],
            'cockpit.subscription-plans.show' => [
                ['label' => 'Paket & Fitur', 'url' => null],
                ['label' => 'Paket Langganan', 'url' => self::safeRoute('cockpit.subscription-plans.index')],
                ['label' => 'Detail Paket', 'url' => null],
            ],

            // Business Types
            'cockpit.business-types.index' => [
                ['label' => 'Paket & Fitur', 'url' => null],
                ['label' => 'Tipe Bisnis', 'url' => self::safeRoute('cockpit.business-types.index')],
            ],
            'cockpit.business-types.show' => [
                ['label' => 'Paket & Fitur', 'url' => null],
                ['label' => 'Tipe Bisnis', 'url' => self::safeRoute('cockpit.business-types.index')],
                ['label' => 'Detail Tipe Bisnis', 'url' => null],
            ],

            // Payment Methods
            'cockpit.payment-methods.index' => [
                ['label' => 'Pengaturan Billing', 'url' => null],
                ['label' => 'Metode Pembayaran', 'url' => self::safeRoute('cockpit.payment-methods.index')],
            ],

            // UOM Master
            'cockpit.uoms.index' => [
                ['label' => 'Master Data', 'url' => null],
                ['label' => 'Satuan Unit (UOM)', 'url' => self::safeRoute('cockpit.uoms.index')],
            ],

            // Config & Settings
            'cockpit.config.index' => [
                ['label' => 'Pengaturan Sistem', 'url' => null],
                ['label' => 'Konfigurasi Global', 'url' => self::safeRoute('cockpit.config.index')],
            ],

            // Audit
            'cockpit.audit.index' => [
                ['label' => 'Sistem & Log', 'url' => null],
                ['label' => 'Audit Trail', 'url' => self::safeRoute('cockpit.audit.index')],
            ],
        ];
    }

    /**
     * Auto-generate hierarchical breadcrumbs from unmapped route names.
     *
     * @return array<int, array{label: string, url: ?string}>
     */
    protected function autoGenerate(string $routeName, string $scope): array
    {
        $segments = explode('.', $routeName);

        // Strip scope prefix if present (e.g., 'cockpit.')
        if ($segments[0] === 'cockpit') {
            array_shift($segments);
        }

        if (empty($segments)) {
            return $this->getDefaultBreadcrumbs($scope);
        }

        $crumbs = [];
        $accumulatedRoute = $scope === 'cockpit' ? 'cockpit' : '';

        // Known segment translations
        $translations = [
            'master' => 'Master Data',
            'products' => 'Produk',
            'categories' => 'Kategori',
            'modifiers' => 'Opsi Tambahan',
            'inventories' => 'Inventori',
            'inventory' => 'Inventori',
            'stocks' => 'Stok',
            'movements' => 'Pergerakan Stok',
            'raw-materials' => 'Bahan Baku',
            'suppliers' => 'Pemasok / Supplier',
            'purchases' => 'Pembelian (PO)',
            'opnames' => 'Stok Opname',
            'adjustments' => 'Penyesuaian Stok',
            'transfers' => 'Mutasi Stok',
            'transactions' => 'Transaksi',
            'sales' => 'Penjualan',
            'invoices' => 'Invoice Piutang',
            'shifts' => 'Shift Kasir',
            'customers' => 'Pelanggan',
            'promotions' => 'Promosi & Diskon',
            'reports' => 'Laporan',
            'settings' => 'Pengaturan',
            'account' => 'Pusat Akun',
            'business' => 'Detail Usaha',
            'outlets' => 'Outlet',
            'roles' => 'Hak Akses & Peran',
            'billing' => 'Langganan & Tagihan',
            'payment-methods' => 'Metode Pembayaran',
            'receipt' => 'Struk Pembayaran',
            'devices' => 'Perangkat POS',
            'taxes' => 'Pajak & Biaya Layanan',
            'operational' => 'Jam Operasional',
            'merchants' => 'Bisnis & Merchant',
            'subscription-plans' => 'Paket Langganan',
            'business-types' => 'Tipe Bisnis',
            'uoms' => 'Satuan Unit (UOM)',
            'config' => 'Konfigurasi Sistem',
            'audit' => 'Audit Trail',
            'dashboard' => 'Dashboard',
            'overview' => 'Overview',
            'create' => 'Tambah Baru',
            'edit' => 'Edit',
            'show' => 'Detail',
        ];

        $total = count($segments);

        foreach ($segments as $i => $segment) {
            if ($segment === 'index' && $total > 1) {
                continue;
            }

            $accumulatedRoute = $accumulatedRoute === '' ? $segment : "{$accumulatedRoute}.{$segment}";
            $label = $translations[$segment] ?? Str::headline($segment);

            $url = null;
            // Only add clickable link if it's not the last item and the route actually exists
            if ($i < $total - 1) {
                $candidateRoute = $accumulatedRoute;
                if (! Route::has($candidateRoute) && Route::has("{$accumulatedRoute}.index")) {
                    $candidateRoute = "{$accumulatedRoute}.index";
                }
                $url = self::safeRoute($candidateRoute);
            }

            $crumbs[] = [
                'label' => $label,
                'url' => $url,
            ];
        }

        return ! empty($crumbs) ? $crumbs : $this->getDefaultBreadcrumbs($scope);
    }

    /**
     * Format and sanitize breadcrumb array items.
     *
     * @param  array<int, mixed>  $items
     * @return array<int, array{label: string, url: ?string}>
     */
    protected function formatItems(array $items): array
    {
        $formatted = [];

        foreach ($items as $item) {
            if (! is_array($item) || ! isset($item['label'])) {
                continue;
            }

            $url = $item['url'] ?? null;
            if ($url === '#' || empty($url)) {
                $url = null;
            }

            $formatted[] = [
                'label' => (string) $item['label'],
                'url' => $url,
            ];
        }

        return $formatted;
    }
}
