<?php

namespace App\Enums;

enum PermissionEnum: string
{
    /*
    |--------------------------------------------------------------------------
    | Business
    |--------------------------------------------------------------------------
    */

    case BUSINESS_VIEW = 'business.view';
    case BUSINESS_BILLING = 'business.billing';
    case BUSINESS_SUBSCRIPTION = 'business.subscription';

    /*
    |--------------------------------------------------------------------------
    | Outlet
    |--------------------------------------------------------------------------
    */

    case OUTLET_VIEW = 'outlet.view';
    case OUTLET_CREATE = 'outlet.create';
    case OUTLET_UPDATE = 'outlet.update';
    case OUTLET_DELETE = 'outlet.delete';

    /*
    |--------------------------------------------------------------------------
    | User & Role
    |--------------------------------------------------------------------------
    */

    case USER_VIEW = 'user.view';
    case USER_CREATE = 'user.create';
    case USER_UPDATE = 'user.update';
    case USER_DELETE = 'user.delete';

    case ROLE_VIEW = 'role.view';
    case ROLE_CREATE = 'role.create';
    case ROLE_UPDATE = 'role.update';
    case ROLE_DELETE = 'role.delete';

    /*
    |--------------------------------------------------------------------------
    | Transaction / POS
    |--------------------------------------------------------------------------
    */

    case TRANSACTION_VIEW = 'transaction.view';
    case TRANSACTION_CREATE = 'transaction.create';
    case TRANSACTION_HOLD = 'transaction.hold';
    case TRANSACTION_REPRINT = 'transaction.reprint';
    case TRANSACTION_OPEN_SHIFT = 'transaction.open_shift';
    case TRANSACTION_CLOSE_SHIFT = 'transaction.close_shift';
    case TRANSACTION_ISSUE_INVOICE = 'transaction.issue_invoice';
    case TRANSACTION_RECORD_PAYMENT = 'transaction.record_payment';
    case TRANSACTION_EDIT_DUE_DATE = 'transaction.edit_due_date';
    case TRANSACTION_OVERRIDE_PRICE = 'transaction.override_price';
    case TRANSACTION_VALIDATION_SUPERVISION = 'transaction.validation_supervision';
    case TRANSACTION_OPEN_DRAWER = 'transaction.open_drawer';
    case TRANSACTION_VOID = 'transaction.void';
    case TRANSACTION_REFUND = 'transaction.refund';

    /*
    |--------------------------------------------------------------------------
    | Product & Menu
    |--------------------------------------------------------------------------
    */

    case PRODUCT_VIEW = 'product.view';
    case PRODUCT_CREATE = 'product.create';
    case PRODUCT_UPDATE = 'product.update';
    case PRODUCT_DELETE = 'product.delete';

    case PRODUCT_CATEGORY_VIEW = 'product.category.view';
    case PRODUCT_CATEGORY_CREATE = 'product.category.create';
    case PRODUCT_CATEGORY_UPDATE = 'product.category.update';
    case PRODUCT_CATEGORY_DELETE = 'product.category.delete';

    case PRODUCT_MODIFIER_VIEW = 'product.modifier.view';
    case PRODUCT_MODIFIER_CREATE = 'product.modifier.create';
    case PRODUCT_MODIFIER_UPDATE = 'product.modifier.update';
    case PRODUCT_MODIFIER_DELETE = 'product.modifier.delete';

    case PRODUCT_BOM_VIEW = 'product.bom.view';
    case PRODUCT_BOM_CREATE = 'product.bom.create';
    case PRODUCT_BOM_UPDATE = 'product.bom.update';
    case PRODUCT_BOM_DELETE = 'product.bom.delete';

    /*
    |--------------------------------------------------------------------------
    | Inventory, Material & Warehouse
    |--------------------------------------------------------------------------
    */

    case INVENTORY_VIEW = 'inventory.view';

    case MATERIAL_VIEW = 'material.view';
    case MATERIAL_CREATE = 'material.create';
    case MATERIAL_UPDATE = 'material.update';
    case MATERIAL_DELETE = 'material.delete';

    case INVENTORY_WAREHOUSE_VIEW = 'inventory.warehouse.view';
    case INVENTORY_WAREHOUSE_CREATE = 'inventory.warehouse.create';
    case INVENTORY_WAREHOUSE_UPDATE = 'inventory.warehouse.update';
    case INVENTORY_WAREHOUSE_DELETE = 'inventory.warehouse.delete';

    case INVENTORY_ADJUST_VIEW = 'inventory.adjust.view';
    case INVENTORY_ADJUST_CREATE = 'inventory.adjust.create';
    case INVENTORY_ADJUST_UPDATE = 'inventory.adjust.update';
    case INVENTORY_ADJUST_VALIDATE = 'inventory.adjust.validate';
    case INVENTORY_ADJUST_DELETE = 'inventory.adjust.delete';

    case INVENTORY_OPNAME_VIEW = 'inventory.opname.view';
    case INVENTORY_OPNAME_CREATE = 'inventory.opname.create';
    case INVENTORY_OPNAME_UPDATE = 'inventory.opname.update';
    case INVENTORY_OPNAME_VALIDATE = 'inventory.opname.validate';
    case INVENTORY_OPNAME_DELETE = 'inventory.opname.delete';

    case INVENTORY_TRANSFER_VIEW = 'inventory.transfer.view';
    case INVENTORY_TRANSFER_CREATE = 'inventory.transfer.create';
    case INVENTORY_TRANSFER_UPDATE = 'inventory.transfer.update';
    case INVENTORY_TRANSFER_VALIDATE = 'inventory.transfer.validate';
    case INVENTORY_TRANSFER_DELETE = 'inventory.transfer.delete';
    case INVENTORY_TRANSFER_RECEIVE = 'inventory.transfer.receive';

    /*
    |--------------------------------------------------------------------------
    | Supplier & Purchase
    |--------------------------------------------------------------------------
    */

    case PURCHASE_SUPPLIER_VIEW = 'purchase.supplier.view';
    case PURCHASE_SUPPLIER_CREATE = 'purchase.supplier.create';
    case PURCHASE_SUPPLIER_UPDATE = 'purchase.supplier.update';
    case PURCHASE_SUPPLIER_DELETE = 'purchase.supplier.delete';

    case PURCHASE_VIEW = 'purchase.view';
    case PURCHASE_CREATE = 'purchase.create';
    case PURCHASE_UPDATE = 'purchase.update';
    case PURCHASE_VALIDATE = 'purchase.validate';
    case PURCHASE_DELETE = 'purchase.delete';
    case PURCHASE_RECEIVE = 'purchase.receive';
    case PURCHASE_RETURN = 'purchase.return';
    case PURCHASE_RETURN_VALIDATE = 'purchase.return.validate';

    /*
    |--------------------------------------------------------------------------
    | Promo
    |--------------------------------------------------------------------------
    */

    case PROMO_VIEW = 'promo.view';
    case PROMO_CREATE = 'promo.create';
    case PROMO_UPDATE = 'promo.update';
    case PROMO_DELETE = 'promo.delete';
    case PROMO_PUBLISH = 'promo.publish';

    /*
    |--------------------------------------------------------------------------
    | Customer
    |--------------------------------------------------------------------------
    */

    case CUSTOMER_VIEW = 'customer.view';
    case CUSTOMER_CREATE = 'customer.create';
    case CUSTOMER_UPDATE = 'customer.update';
    case CUSTOMER_DELETE = 'customer.delete';
    case CUSTOMER_LOYALTY = 'customer.loyalty';

    /*
    |--------------------------------------------------------------------------
    | Report & Analytics
    |--------------------------------------------------------------------------
    */

    case REPORT_SALES = 'report.sales';
    case REPORT_INVENTORY = 'report.inventory';
    case REPORT_SHIFT = 'report.shift';
    case REPORT_PRODUCT = 'report.product';
    case REPORT_EXPORT = 'report.export';

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    */

    case SETTING_SALES = 'setting.sales';
    case SETTING_TAX = 'setting.tax';
    case SETTING_RECEIPT = 'setting.receipt';
    case SETTING_PAYMENT = 'setting.payment';
    case SETTING_DEVICE = 'setting.device';
    case SETTING_PRINTER = 'setting.printer';
    case SETTING_AUDIT = 'setting.audit';

    public function label(): string
    {
        return match ($this) {
            // Business
            self::BUSINESS_VIEW => 'Akses Melihat Bisnis',
            self::BUSINESS_BILLING => 'Akses Penagihan Bisnis',
            self::BUSINESS_SUBSCRIPTION => 'Akses Langganan Bisnis',

            // Outlet
            self::OUTLET_VIEW => 'Akses Melihat Outlet',
            self::OUTLET_CREATE => 'Akses Membuat Outlet',
            self::OUTLET_UPDATE => 'Akses Memperbarui Outlet',
            self::OUTLET_DELETE => 'Akses Menghapus Outlet',

            // User
            self::USER_VIEW => 'Akses Melihat Pegawai',
            self::USER_CREATE => 'Akses Membuat Pegawai',
            self::USER_UPDATE => 'Akses Memperbarui Pegawai',
            self::USER_DELETE => 'Akses Menghapus Pegawai',

            // Role
            self::ROLE_VIEW => 'Akses Melihat Role',
            self::ROLE_CREATE => 'Akses Membuat Role',
            self::ROLE_UPDATE => 'Akses Memperbarui Role',
            self::ROLE_DELETE => 'Akses Menghapus Role',

            // Transaction
            self::TRANSACTION_VIEW => 'Akses Melihat Transaksi',
            self::TRANSACTION_CREATE => 'Akses Membuat Transaksi',
            self::TRANSACTION_HOLD => 'Akses Hold Transaksi',
            self::TRANSACTION_REPRINT => 'Akses Cetak Ulang Transaksi',
            self::TRANSACTION_OPEN_SHIFT => 'Akses Buka Shift',
            self::TRANSACTION_CLOSE_SHIFT => 'Akses Tutup Shift',
            self::TRANSACTION_ISSUE_INVOICE => 'Akses Menerbitkan Invoice',
            self::TRANSACTION_RECORD_PAYMENT => 'Akses Mencatat Pelunasan',
            self::TRANSACTION_EDIT_DUE_DATE => 'Akses Mengubah Jatuh Tempo',
            self::TRANSACTION_OVERRIDE_PRICE => 'Akses Override Harga/Diskon',
            self::TRANSACTION_VALIDATION_SUPERVISION => 'Akses Otorisasi Supervisor Kasir',
            self::TRANSACTION_OPEN_DRAWER => 'Akses Buka Laci Kasir Manual',
            self::TRANSACTION_VOID => 'Akses Void Transaksi',
            self::TRANSACTION_REFUND => 'Akses Refund Transaksi',

            // Product
            self::PRODUCT_VIEW => 'Akses Melihat Produk',
            self::PRODUCT_CREATE => 'Akses Membuat Produk',
            self::PRODUCT_UPDATE => 'Akses Memperbarui Produk',
            self::PRODUCT_DELETE => 'Akses Menghapus Produk',

            // Product Category
            self::PRODUCT_CATEGORY_VIEW => 'Akses Melihat Kategori',
            self::PRODUCT_CATEGORY_CREATE => 'Akses Membuat Kategori',
            self::PRODUCT_CATEGORY_UPDATE => 'Akses Memperbarui Kategori',
            self::PRODUCT_CATEGORY_DELETE => 'Akses Menghapus Kategori',

            // Product Modifier
            self::PRODUCT_MODIFIER_VIEW => 'Akses Melihat Modifier',
            self::PRODUCT_MODIFIER_CREATE => 'Akses Membuat Modifier',
            self::PRODUCT_MODIFIER_UPDATE => 'Akses Memperbarui Modifier',
            self::PRODUCT_MODIFIER_DELETE => 'Akses Menghapus Modifier',

            // Product BOM
            self::PRODUCT_BOM_VIEW => 'Akses Melihat Resep BOM',
            self::PRODUCT_BOM_CREATE => 'Akses Membuat Resep BOM',
            self::PRODUCT_BOM_UPDATE => 'Akses Memperbarui Resep BOM',
            self::PRODUCT_BOM_DELETE => 'Akses Menghapus Resep BOM',

            // Inventory
            self::INVENTORY_VIEW => 'Akses Melihat Inventori',

            // Material
            self::MATERIAL_VIEW => 'Akses Melihat Bahan Baku',
            self::MATERIAL_CREATE => 'Akses Membuat Bahan Baku',
            self::MATERIAL_UPDATE => 'Akses Memperbarui Bahan Baku',
            self::MATERIAL_DELETE => 'Akses Menghapus Bahan Baku',

            // Warehouse
            self::INVENTORY_WAREHOUSE_VIEW => 'Akses Melihat Gudang',
            self::INVENTORY_WAREHOUSE_CREATE => 'Akses Membuat Gudang',
            self::INVENTORY_WAREHOUSE_UPDATE => 'Akses Memperbarui Gudang',
            self::INVENTORY_WAREHOUSE_DELETE => 'Akses Menghapus Gudang',

            // Inventory Adjustment
            self::INVENTORY_ADJUST_VIEW => 'Akses Melihat Penyesuaian',
            self::INVENTORY_ADJUST_CREATE => 'Akses Membuat Penyesuaian',
            self::INVENTORY_ADJUST_UPDATE => 'Akses Memperbarui Penyesuaian',
            self::INVENTORY_ADJUST_VALIDATE => 'Akses Validasi Penyesuaian',
            self::INVENTORY_ADJUST_DELETE => 'Akses Menghapus Penyesuaian',

            // Inventory Stock Opname
            self::INVENTORY_OPNAME_VIEW => 'Akses Melihat Stock Opname',
            self::INVENTORY_OPNAME_CREATE => 'Akses Membuat Stock Opname',
            self::INVENTORY_OPNAME_UPDATE => 'Akses Memperbarui Stock Opname',
            self::INVENTORY_OPNAME_VALIDATE => 'Akses Validasi Stock Opname',
            self::INVENTORY_OPNAME_DELETE => 'Akses Menghapus Stock Opname',

            // Inventory Transfer
            self::INVENTORY_TRANSFER_VIEW => 'Akses Melihat Transfer Inventori',
            self::INVENTORY_TRANSFER_CREATE => 'Akses Membuat Transfer Inventori',
            self::INVENTORY_TRANSFER_UPDATE => 'Akses Memperbarui Transfer Inventori',
            self::INVENTORY_TRANSFER_VALIDATE => 'Akses Validasi Transfer Inventori',
            self::INVENTORY_TRANSFER_DELETE => 'Akses Menghapus Transfer Inventori',
            self::INVENTORY_TRANSFER_RECEIVE => 'Akses Terima Transfer Inventori',

            // Supplier
            self::PURCHASE_SUPPLIER_VIEW => 'Akses Melihat Supplier',
            self::PURCHASE_SUPPLIER_CREATE => 'Akses Membuat Supplier',
            self::PURCHASE_SUPPLIER_UPDATE => 'Akses Memperbarui Supplier',
            self::PURCHASE_SUPPLIER_DELETE => 'Akses Menghapus Supplier',

            // Purchase Order
            self::PURCHASE_VIEW => 'Akses Melihat Pembelian',
            self::PURCHASE_CREATE => 'Akses Membuat Pembelian',
            self::PURCHASE_UPDATE => 'Akses Memperbarui Pembelian',
            self::PURCHASE_VALIDATE => 'Akses Validasi Pembelian',
            self::PURCHASE_DELETE => 'Akses Menghapus/Membatalkan Pembelian',
            self::PURCHASE_RECEIVE => 'Akses Penerimaan Pembelian',
            self::PURCHASE_RETURN => 'Akses Retur Pembelian',
            self::PURCHASE_RETURN_VALIDATE => 'Akses Validasi Retur Pembelian',

            // Promo
            self::PROMO_VIEW => 'Akses Melihat Promo',
            self::PROMO_CREATE => 'Akses Membuat Promo',
            self::PROMO_UPDATE => 'Akses Memperbarui Promo',
            self::PROMO_DELETE => 'Akses Menghapus Promo',
            self::PROMO_PUBLISH => 'Akses Publish Promo',

            // Customer
            self::CUSTOMER_VIEW => 'Akses Melihat Customer',
            self::CUSTOMER_CREATE => 'Akses Membuat Customer',
            self::CUSTOMER_UPDATE => 'Akses Memperbarui Customer',
            self::CUSTOMER_DELETE => 'Akses Menghapus Customer',
            self::CUSTOMER_LOYALTY => 'Akses Loyalty Customer',

            // Report
            self::REPORT_SALES => 'Akses Laporan Penjualan',
            self::REPORT_INVENTORY => 'Akses Laporan Inventori',
            self::REPORT_SHIFT => 'Akses Laporan Shift & Kas',
            self::REPORT_PRODUCT => 'Akses Laporan Produk',
            self::REPORT_EXPORT => 'Akses Export Laporan',

            // Setting
            self::SETTING_SALES => 'Akses Pengaturan Penjualan',
            self::SETTING_TAX => 'Akses Pengaturan Pajak',
            self::SETTING_RECEIPT => 'Akses Pengaturan Struk',
            self::SETTING_PAYMENT => 'Akses Pengaturan Pembayaran',
            self::SETTING_DEVICE => 'Akses Pengaturan Device',
            self::SETTING_PRINTER => 'Akses Pengaturan Printer',
            self::SETTING_AUDIT => 'Akses Melihat Log Aktivitas',
        };
    }

    public function group(): string
    {
        return match ($this) {
            self::TRANSACTION_VIEW,
            self::TRANSACTION_CREATE,
            self::TRANSACTION_HOLD,
            self::TRANSACTION_REPRINT,
            self::TRANSACTION_OPEN_SHIFT,
            self::TRANSACTION_CLOSE_SHIFT,
            self::TRANSACTION_ISSUE_INVOICE,
            self::TRANSACTION_RECORD_PAYMENT,
            self::TRANSACTION_EDIT_DUE_DATE,
            self::TRANSACTION_OVERRIDE_PRICE,
            self::TRANSACTION_VALIDATION_SUPERVISION,
            self::TRANSACTION_OPEN_DRAWER,
            self::TRANSACTION_VOID,
            self::TRANSACTION_REFUND => 'pos_and_transactions',

            self::PRODUCT_VIEW,
            self::PRODUCT_CREATE,
            self::PRODUCT_UPDATE,
            self::PRODUCT_DELETE,
            self::PRODUCT_CATEGORY_VIEW,
            self::PRODUCT_CATEGORY_CREATE,
            self::PRODUCT_CATEGORY_UPDATE,
            self::PRODUCT_CATEGORY_DELETE,
            self::PRODUCT_MODIFIER_VIEW,
            self::PRODUCT_MODIFIER_CREATE,
            self::PRODUCT_MODIFIER_UPDATE,
            self::PRODUCT_MODIFIER_DELETE,
            self::PRODUCT_BOM_VIEW,
            self::PRODUCT_BOM_CREATE,
            self::PRODUCT_BOM_UPDATE,
            self::PRODUCT_BOM_DELETE => 'products_and_menu',

            self::INVENTORY_VIEW,
            self::MATERIAL_VIEW,
            self::MATERIAL_CREATE,
            self::MATERIAL_UPDATE,
            self::MATERIAL_DELETE,
            self::INVENTORY_WAREHOUSE_VIEW,
            self::INVENTORY_WAREHOUSE_CREATE,
            self::INVENTORY_WAREHOUSE_UPDATE,
            self::INVENTORY_WAREHOUSE_DELETE,
            self::INVENTORY_ADJUST_VIEW,
            self::INVENTORY_ADJUST_CREATE,
            self::INVENTORY_ADJUST_UPDATE,
            self::INVENTORY_ADJUST_VALIDATE,
            self::INVENTORY_ADJUST_DELETE,
            self::INVENTORY_OPNAME_VIEW,
            self::INVENTORY_OPNAME_CREATE,
            self::INVENTORY_OPNAME_UPDATE,
            self::INVENTORY_OPNAME_VALIDATE,
            self::INVENTORY_OPNAME_DELETE,
            self::INVENTORY_TRANSFER_VIEW,
            self::INVENTORY_TRANSFER_CREATE,
            self::INVENTORY_TRANSFER_UPDATE,
            self::INVENTORY_TRANSFER_VALIDATE,
            self::INVENTORY_TRANSFER_DELETE,
            self::INVENTORY_TRANSFER_RECEIVE,
            self::PURCHASE_SUPPLIER_VIEW,
            self::PURCHASE_SUPPLIER_CREATE,
            self::PURCHASE_SUPPLIER_UPDATE,
            self::PURCHASE_SUPPLIER_DELETE,
            self::PURCHASE_VIEW,
            self::PURCHASE_CREATE,
            self::PURCHASE_UPDATE,
            self::PURCHASE_VALIDATE,
            self::PURCHASE_DELETE,
            self::PURCHASE_RECEIVE,
            self::PURCHASE_RETURN,
            self::PURCHASE_RETURN_VALIDATE => 'inventory_and_supply',

            self::PROMO_VIEW,
            self::PROMO_CREATE,
            self::PROMO_UPDATE,
            self::PROMO_DELETE,
            self::PROMO_PUBLISH,
            self::CUSTOMER_VIEW,
            self::CUSTOMER_CREATE,
            self::CUSTOMER_UPDATE,
            self::CUSTOMER_DELETE,
            self::CUSTOMER_LOYALTY => 'promotions_and_crm',

            self::REPORT_SALES,
            self::REPORT_INVENTORY,
            self::REPORT_SHIFT,
            self::REPORT_PRODUCT,
            self::REPORT_EXPORT => 'reports_and_analytics',

            self::BUSINESS_VIEW,
            self::BUSINESS_BILLING,
            self::BUSINESS_SUBSCRIPTION,
            self::OUTLET_VIEW,
            self::OUTLET_CREATE,
            self::OUTLET_UPDATE,
            self::OUTLET_DELETE,
            self::SETTING_SALES,
            self::SETTING_TAX,
            self::SETTING_RECEIPT,
            self::SETTING_PAYMENT,
            self::SETTING_DEVICE,
            self::SETTING_PRINTER,
            self::SETTING_AUDIT => 'settings_and_business',

            self::USER_VIEW,
            self::USER_CREATE,
            self::USER_UPDATE,
            self::USER_DELETE,
            self::ROLE_VIEW,
            self::ROLE_CREATE,
            self::ROLE_UPDATE,
            self::ROLE_DELETE => 'employees_and_roles',
        };
    }

    public function groupLabel(): string
    {
        return match ($this->group()) {
            'pos_and_transactions' => 'Penjualan & Kasir',
            'products_and_menu' => 'Produk & Menu',
            'inventory_and_supply' => 'Inventori & Rantai Pasok',
            'promotions_and_crm' => 'Promosi & Pelanggan',
            'reports_and_analytics' => 'Laporan & Analitik',
            'settings_and_business' => 'Pengaturan & Bisnis',
            'employees_and_roles' => 'Pegawai & Hak Akses',
            default => 'Lainnya',
        };
    }

    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public static function options(): array
    {
        return collect(self::cases())
            ->mapWithKeys(fn (self $permission) => [
                $permission->value => $permission->label(),
            ])
            ->toArray();
    }

    /**
     * Daftar izin terkelompok berdasarkan grup kategori.
     *
     * @return array<string, array{key: string, label: string, permissions: array<int, array{value: string, label: string}>}>
     */
    public static function grouped(): array
    {
        $order = [
            'pos_and_transactions',
            'products_and_menu',
            'inventory_and_supply',
            'promotions_and_crm',
            'reports_and_analytics',
            'settings_and_business',
            'employees_and_roles',
        ];

        $groups = [];

        foreach ($order as $groupKey) {
            $groups[$groupKey] = [
                'key' => $groupKey,
                'label' => '',
                'permissions' => [],
            ];
        }

        foreach (self::cases() as $case) {
            $groupKey = $case->group();
            if (! isset($groups[$groupKey])) {
                $groups[$groupKey] = [
                    'key' => $groupKey,
                    'label' => $case->groupLabel(),
                    'permissions' => [],
                ];
            } else {
                $groups[$groupKey]['label'] = $case->groupLabel();
            }

            $groups[$groupKey]['permissions'][] = [
                'value' => $case->value,
                'label' => $case->label(),
            ];
        }

        return array_values(array_filter($groups, fn ($g) => ! empty($g['permissions'])));
    }
}
