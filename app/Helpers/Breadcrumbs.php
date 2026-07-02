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
                ['label' => 'Produk Jual', 'url' => $masterProductsUrl],
            ],
            'master.products.create' => [
                ['label' => 'Master Produk', 'url' => '#'],
                ['label' => 'Produk Jual', 'url' => $masterProductsUrl],
                ['label' => 'Buat Produk Baru', 'url' => '#'],
            ],
            'master.products.edit' => [
                ['label' => 'Master Produk', 'url' => '#'],
                ['label' => 'Produk Jual', 'url' => $masterProductsUrl],
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
        ];

        return $breadcrumbs[$routeName] ?? $breadcrumbs['overview'];
    }
}
