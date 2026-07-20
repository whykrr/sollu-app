<?php


if (! function_exists('generateBreadcrumbs')) {
    function generateBreadcrumbs($routeName)
    {
        // Define base URLs for reusability
        $overviewUrl               = route('overview');
        $employeesIndexUrl         = route('employees.index');
        $settingsOutletsUrl        = route('settings.outlets.index');
        $settingsBillingUrl        = route('settings.billing.index');
        $settingsAccountProfileUrl = route('settings.account.profile');
        $settingsBusinessDetailUrl = route('settings.business.detail');
        $masterCategoriesUrl       = route('master.categories.index');
        $masterModifiersUrl        = route('master.modifiers.index');
        $masterProductsUrl         = route('master.products.index');

        $breadcrumbs = [
            'overview' => [
                ['label' => 'Overview', 'url' => $overviewUrl],
            ],

            // Employees Module
            'employees.index' => [
                ['label' => 'Pegawai', 'url' => $employeesIndexUrl],
            ],

            // Master Product Module
            'master.categories.index' => [
                ['label' => 'Master Produk', 'url' => '#'],
                ['label' => 'Kategori Produk', 'url' => $masterCategoriesUrl],
            ],
            'master.modifiers.index' => [
                ['label' => 'Master Produk', 'url' => '#'],
                ['label' => 'Opsi Tambahan', 'url' => $masterModifiersUrl],
            ],
            'master.products.index' => [
                ['label' => 'Master Produk', 'url' => '#'],
                ['label' => 'Produk', 'url' => $masterProductsUrl],
            ],
            'master.products.create' => [
                ['label' => 'Master Produk', 'url' => '#'],
                ['label' => 'Produk', 'url' => $masterProductsUrl],
                ['label' => 'Buat Produk Baru', 'url' => '#'],
            ],
            'master.products.edit' => [
                ['label' => 'Master Produk', 'url' => '#'],
                ['label' => 'Produk', 'url' => $masterProductsUrl],
                ['label' => 'Edit Produk', 'url' => '#'],
            ],

            'employees.create' => [
                ['label' => 'Pegawai', 'url' => $employeesIndexUrl],
                ['label' => 'Tambah Pegawai', 'url' => '#'],
            ],
            'employees.show' => [
                ['label' => 'Pegawai', 'url' => $employeesIndexUrl],
                ['label' => 'Detail Pegawai', 'url' => '#'],
            ],

            // Settings Module - Account
            'settings.account.profile' => [
                ['label' => 'Pengaturan', 'url' => '#'],
                ['label' => 'Pusat Akun', 'url' => $settingsAccountProfileUrl],
            ],

            // Settings Module - Business
            'settings.business.detail' => [
                ['label' => 'Pengaturan', 'url' => '#'],
                ['label' => 'Detail Usaha', 'url' => $settingsBusinessDetailUrl],
            ],

            // Settings Module - Outlets
            'settings.outlets.index' => [
                ['label' => 'Pengaturan', 'url' => '#'],
                ['label' => 'Outlet', 'url' => $settingsOutletsUrl],
            ],
            'settings.outlets.show' => [
                ['label' => 'Pengaturan', 'url' => '#'],
                ['label' => 'Outlet', 'url' => $settingsOutletsUrl],
                ['label' => 'Detail Outlet', 'url' => '#'],
            ],

            // Settings Module - Billing
            'settings.billing.index' => [
                ['label' => 'Langganan & Tagihan', 'url' => '#'],
                ['label' => 'Langganan', 'url' => $settingsBillingUrl],
            ],
            'settings.billing.plans' => [
                ['label' => 'Langganan & Tagihan', 'url' => '#'],
                ['label' => 'Langganan', 'url' => $settingsBillingUrl],
                ['label' => 'Pilih Langganan', 'url' => '#'],
            ],
            'settings.billing.subscribe' => [
                ['label' => 'Langganan & Tagihan', 'url' => '#'],
                ['label' => 'Langganan', 'url' => $settingsBillingUrl],
                ['label' => 'Detail Langganan', 'url' => '#'],
            ],
            'settings.billing.invoices.show' => [
                ['label' => 'Langganan & Tagihan', 'url' => '#'],
                ['label' => 'Pembayaran', 'url' => $settingsBillingUrl],
                ['label' => 'Tagihan', 'url' => '#'],
            ],

            // Inventory Module
            'inventories.stocks.index' => [
                ['label' => 'Inventori', 'url' => '#'],
                ['label' => 'Stok', 'url' => route('inventories.stocks.index')],
            ],
            'inventories.movements.index' => [
                ['label' => 'Inventori', 'url' => '#'],
                ['label' => 'Pergerakan Stok', 'url' => route('inventories.movements.index')],
            ],
            'inventory.raw-materials.index' => [
                ['label' => 'Inventori', 'url' => '#'],
                ['label' => 'Bahan Baku', 'url' => route('inventory.raw-materials.index')],
            ],
            'inventory.suppliers.index' => [
                ['label' => 'Inventori', 'url' => '#'],
                ['label' => 'Pemasok / Supplier', 'url' => route('inventory.suppliers.index')],
            ],
            'inventory.purchases.index' => [
                ['label' => 'Inventori', 'url' => '#'],
                ['label' => 'Pembelian (PO)', 'url' => route('inventory.purchases.index')],
            ],
            'inventory.stocktaking.index' => [
                ['label' => 'Inventori', 'url' => '#'],
                ['label' => 'Stok Opname', 'url' => route('inventory.stocktaking.index')],
            ],
            'inventory.adjustments.index' => [
                ['label' => 'Inventori', 'url' => '#'],
                ['label' => 'Penyesuaian Stok', 'url' => route('inventory.adjustments.index')],
            ],
            'inventory.transfers.index' => [
                ['label' => 'Inventori', 'url' => '#'],
                ['label' => 'Mutasi Stok', 'url' => route('inventory.transfers.index')],
            ],
        ];

        return $breadcrumbs[$routeName] ?? $breadcrumbs['overview'];
    }
}
