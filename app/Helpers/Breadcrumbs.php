<?php


if (! function_exists('generateBreadcrumbs')) {
    function generateBreadcrumbs($routeName)
    {
        $breadcrumbs = [
            'overview' => [
                ['label' => 'Overview', 'url' => route('overview')],
            ],

            'products.units.index' => [
                ['label' => 'Produk', 'url' => '#'],
                ['label' => 'Satuan', 'url' => route('products.units.index')],
            ],
            'products.units.create' => [
                ['label' => 'Produk', 'url' => '#'],
                ['label' => 'Satuan', 'url' => route('products.units.index')],
                ['label' => 'Tambah Satuan', 'url' => '#'],
            ],
            'products.units.show' => [
                ['label' => 'Produk', 'url' => '#'],
                ['label' => 'Satuan', 'url' => route('products.units.index')],
                ['label' => 'Detail Satuan', 'url' => '#'],
            ],

            'employees.index' => [
                ['label' => 'Pegawai', 'url' => route('employees.index')],
            ],
            'employees.create' => [
                ['label' => 'Pegawai', 'url' => route('employees.index')],
                ['label' => 'Tambah Pegawai', 'url' => '#'],
            ],
            'employees.show' => [
                ['label' => 'Pegawai', 'url' => route('employees.index')],
                ['label' => 'Detail Pegawai', 'url' => '#'],
            ],

            'business.info.detail' => [
                ['label' => 'Usaha', 'url' => '#'],
                ['label' => 'Detail Usaha', 'url' => route('business.info.detail')],
            ],

            'business.billing.index' => [
                ['label' => 'Langganan & Tagihan', 'url' => '#'],
                ['label' => 'Langganan', 'url' => route('business.billing.index')],
            ],

            'business.billing.plans' => [
                ['label' => 'Langganan & Tagihan', 'url' => '#'],
                ['label' => 'Langganan', 'url' => route('business.billing.index')],
                ['label' => 'Pilih Langganan', 'url' => '#'],
            ],

            'business.billing.subscribe' => [
                ['label' => 'Langganan & Tagihan', 'url' => '#'],
                ['label' => 'Langganan', 'url' => route('business.billing.index')],
                ['label' => 'Pilih Langganan', 'url' => route('business.billing.plans')],
                ['label' => 'Detail Langganan', 'url' => '#'],
            ],

            'business.invoices.index' => [
                ['label' => 'Langganan & Tagihan', 'url' => '#'],
                ['label' => 'Pembayaran', 'url' => '#'],
            ],
            'business.invoices.show' => [
                ['label' => 'Langganan & Tagihan', 'url' => '#'],
                ['label' => 'Pembayaran', 'url' => route('business.invoices.index')],
                ['label' => 'Tagihan', 'url' => '#'],
            ],


            // 'users.index' => [
            //     ['label' => 'Users', 'url' => '#'],
            // ],
            // 'users.create' => [
            //     ['label' => 'Users', 'url' => route('admin.users.index')],
            //     ['label' => 'Add User', 'url' => '#'],
            // ],
            // 'users.edit' => [
            //     ['label' => 'Users', 'url' => route('admin.users.index')],
            //     ['label' => 'Detail User', 'url' => '#'],
            // ],

            // 'languages.index' => [
            //     ['label' => 'Languages', 'url' => '#'],
            // ],
            // 'languages.create' => [
            //     ['label' => 'Languages', 'url' => route('admin.languages.index')],
            //     ['label' => 'Add Language', 'url' => '#'],
            // ],
            // 'languages.edit' => [
            //     ['label' => 'Languages', 'url' => route('admin.languages.index')],
            //     ['label' => 'Add Language', 'url' => '#'],
            // ],

            // 'content-types.index' => [
            //     ['label' => 'Content Type', 'url' => '#'],
            // ],
            // 'content-types.create' => [
            //     ['label' => 'Content Type', 'url' => route('admin.content-types.index')],
            //     ['label' => 'Add Content Type', 'url' => '#'],
            // ],
            // 'content-types.edit' => [
            //     ['label' => 'Content Type', 'url' => route('admin.content-types.index')],
            //     ['label' => 'Detail Content Type', 'url' => '#'],
            // ],

            // 'contents.index' => [
            //     ['label' => 'Content', 'url' => '#'],
            // ],

            // 'contents.listed' => [
            //     ['label' => 'Content', 'url' => '#'],
            // ],

            // 'contents.create' => [
            //     ['label' => 'Content', 'url' => '#'],
            //     ['label' => 'Create Content', 'url' => '#'],
            // ],

            // 'contents.edit' => [
            //     ['label' => 'Content', 'url' => '#'],
            //     ['label' => 'Edit Content', 'url' => '#'],
            // ],

            // 'message.index' => [
            //     ['label' => 'Inbox', 'url' => '#'],
            // ],


            // 'message.show' => [
            //     ['label' => 'Inbox', 'url' => route('admin.message.index')],
            //     ['label' => 'Detail Message', 'url' => '#'],
            // ],

            // 'settings.index' => [
            //     ['label' => 'Settings', 'url' => '#'],
            // ],
        ];

        $routeName = str_replace('', '', $routeName);

        return $breadcrumbs[$routeName] ?? $breadcrumbs['overview'];
    }
}
