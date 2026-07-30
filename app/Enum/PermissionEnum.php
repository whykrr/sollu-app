<?php

namespace App\Enum;

enum PermissionEnum: string
{
    /*
    |--------------------------------------------------------------------------
    | Business
    |--------------------------------------------------------------------------
    */

    case BUSINESS_ALL          = 'business.*';
    case BUSINESS_VIEW         = 'business.view';
    case BUSINESS_UPDATE       = 'business.update';
    case BUSINESS_BILLING      = 'business.billing';
    case BUSINESS_SUBSCRIPTION = 'business.subscription';
    case BUSINESS_SETTING      = 'business.setting';

    /*
    |--------------------------------------------------------------------------
    | Outlet
    |--------------------------------------------------------------------------
    */

    case OUTLET_ALL    = 'outlet.*';
    case OUTLET_VIEW   = 'outlet.view';
    case OUTLET_CREATE = 'outlet.create';
    case OUTLET_UPDATE = 'outlet.update';
    case OUTLET_DELETE = 'outlet.delete';
    case OUTLET_SWITCH = 'outlet.switch';

    /*
    |--------------------------------------------------------------------------
    | User & Role
    |--------------------------------------------------------------------------
    */

    case USER_ALL    = 'user.*';
    case USER_VIEW   = 'user.view';
    case USER_CREATE = 'user.create';
    case USER_UPDATE = 'user.update';
    case USER_DELETE = 'user.delete';
    case USER_INVITE = 'user.invite';

    case ROLE_ALL    = 'role.*';
    case ROLE_VIEW   = 'role.view';
    case ROLE_CREATE = 'role.create';
    case ROLE_UPDATE = 'role.update';
    case ROLE_DELETE = 'role.delete';

    /*
    |--------------------------------------------------------------------------
    | Transaction / POS
    |--------------------------------------------------------------------------
    */

    case TRANSACTION_ALL         = 'transaction.*';
    case TRANSACTION_VIEW        = 'transaction.view';
    case TRANSACTION_CREATE      = 'transaction.create';
    case TRANSACTION_UPDATE      = 'transaction.update';
    case TRANSACTION_CANCEL      = 'transaction.cancel';
    case TRANSACTION_REFUND      = 'transaction.refund';
    case TRANSACTION_DISCOUNT    = 'transaction.discount';
    case TRANSACTION_HOLD        = 'transaction.hold';
    case TRANSACTION_VOID        = 'transaction.void';
    case TRANSACTION_REPRINT     = 'transaction.reprint';
    case TRANSACTION_OPEN_SHIFT  = 'transaction.open_shift';
    case TRANSACTION_CLOSE_SHIFT = 'transaction.close_shift';

    /*
    |--------------------------------------------------------------------------
    | Product
    |--------------------------------------------------------------------------
    */

    case PRODUCT_ALL      = 'product.*';
    case PRODUCT_VIEW     = 'product.view';
    case PRODUCT_CREATE   = 'product.create';
    case PRODUCT_UPDATE   = 'product.update';
    case PRODUCT_DELETE   = 'product.delete';
    case PRODUCT_IMPORT   = 'product.import';
    case PRODUCT_EXPORT   = 'product.export';
    case PRODUCT_VARIANT  = 'product.variant';
    case PRODUCT_MODIFIER = 'product.modifier';
    case PRODUCT_RECIPE   = 'product.recipe';

    /*
    |--------------------------------------------------------------------------
    | Category
    |--------------------------------------------------------------------------
    */

    case CATEGORY_ALL    = 'category.*';
    case CATEGORY_VIEW   = 'category.view';
    case CATEGORY_CREATE = 'category.create';
    case CATEGORY_UPDATE = 'category.update';
    case CATEGORY_DELETE = 'category.delete';

    /*
    |--------------------------------------------------------------------------
    | Inventory
    |--------------------------------------------------------------------------
    */

    case INVENTORY_ALL          = 'inventory.*';
    case INVENTORY_VIEW         = 'inventory.view';
    case INVENTORY_ADJUST       = 'inventory.adjust'; // existing, we will keep it for compatibility if any
    case INVENTORY_ADJUSTMENT_READ   = 'inventory.adjustment.read';
    case INVENTORY_ADJUSTMENT_CREATE = 'inventory.adjustment.create';
    case INVENTORY_ADJUSTMENT_APPROVE= 'inventory.adjustment.approve';
    case INVENTORY_ADJUSTMENT_VOID   = 'inventory.adjustment.void';
    case INVENTORY_ADJUSTMENT_EXPORT = 'inventory.adjustment.export';
    case INVENTORY_ADJUSTMENT_FREEZE = 'inventory.adjustment.freeze';
    case INVENTORY_TRANSFER     = 'inventory.transfer';
    case INVENTORY_STOCK_OPNAME = 'inventory.stock_opname';
    case INVENTORY_MOVEMENT     = 'inventory.movement';
    case INVENTORY_PURCHASE     = 'inventory.purchase';
    case INVENTORY_WASTE        = 'inventory.waste';
    case INVENTORY_RECEIVE      = 'inventory.receive';

    /*
    |--------------------------------------------------------------------------
    | Supplier
    |--------------------------------------------------------------------------
    */

    case SUPPLIER_ALL    = 'supplier.*';
    case SUPPLIER_VIEW   = 'supplier.view';
    case SUPPLIER_CREATE = 'supplier.create';
    case SUPPLIER_UPDATE = 'supplier.update';
    case SUPPLIER_DELETE = 'supplier.delete';

    /*
    |--------------------------------------------------------------------------
    | Purchase Order
    |--------------------------------------------------------------------------
    */

    case PURCHASE_ORDER_ALL     = 'purchase_order.*';
    case PURCHASE_ORDER_VIEW    = 'purchase_order.view';
    case PURCHASE_ORDER_CREATE  = 'purchase_order.create';
    case PURCHASE_ORDER_UPDATE  = 'purchase_order.update';
    case PURCHASE_ORDER_APPROVE = 'purchase_order.approve';
    case PURCHASE_ORDER_CANCEL  = 'purchase_order.cancel';
    case PURCHASE_ORDER_RECEIVE = 'purchase_order.receive';

    /*
    |--------------------------------------------------------------------------
    | Promo
    |--------------------------------------------------------------------------
    */

    case PROMO_ALL     = 'promo.*';
    case PROMO_VIEW    = 'promo.view';
    case PROMO_CREATE  = 'promo.create';
    case PROMO_UPDATE  = 'promo.update';
    case PROMO_DELETE  = 'promo.delete';
    case PROMO_PUBLISH = 'promo.publish';

    /*
    |--------------------------------------------------------------------------
    | Customer
    |--------------------------------------------------------------------------
    */

    case CUSTOMER_ALL     = 'customer.*';
    case CUSTOMER_VIEW    = 'customer.view';
    case CUSTOMER_CREATE  = 'customer.create';
    case CUSTOMER_UPDATE  = 'customer.update';
    case CUSTOMER_DELETE  = 'customer.delete';
    case CUSTOMER_LOYALTY = 'customer.loyalty';

    /*
    |--------------------------------------------------------------------------
    | Report & Analytics
    |--------------------------------------------------------------------------
    */

    case REPORT_ALL       = 'report.*';
    case REPORT_SALES     = 'report.sales';
    case REPORT_INVENTORY = 'report.inventory';
    case REPORT_CASHFLOW  = 'report.cashflow';
    case REPORT_SHIFT     = 'report.shift';
    case REPORT_PRODUCT   = 'report.product';
    case REPORT_CUSTOMER  = 'report.customer';
    case REPORT_EXPORT    = 'report.export';

    /*
    |--------------------------------------------------------------------------
    | Settings
    |--------------------------------------------------------------------------
    */

    case SETTING_ALL     = 'setting.*';
    case SETTING_TAX     = 'setting.tax';
    case SETTING_RECEIPT = 'setting.receipt';
    case SETTING_PAYMENT = 'setting.payment';
    case SETTING_DEVICE  = 'setting.device';
    case SETTING_PRINTER = 'setting.printer';

    public function label(): string
    {
        return match ($this) {

            // Business
            self::BUSINESS_ALL          => 'Semua Akses Bisnis',
            self::BUSINESS_VIEW         => 'Akses Melihat Bisnis',
            self::BUSINESS_UPDATE       => 'Akses Memperbarui Bisnis',
            self::BUSINESS_BILLING      => 'Akses Penagihan Bisnis',
            self::BUSINESS_SUBSCRIPTION => 'Akses Langganan Bisnis',
            self::BUSINESS_SETTING      => 'Akses Pengaturan Bisnis',

            // Outlet
            self::OUTLET_ALL    => 'Semua Akses Outlet',
            self::OUTLET_VIEW   => 'Akses Melihat Outlet',
            self::OUTLET_CREATE => 'Akses Membuat Outlet',
            self::OUTLET_UPDATE => 'Akses Memperbarui Outlet',
            self::OUTLET_DELETE => 'Akses Menghapus Outlet',
            self::OUTLET_SWITCH => 'Akses Pindah Outlet',

            // User
            self::USER_ALL    => 'Semua Akses Pegawai',
            self::USER_VIEW   => 'Akses Melihat Pegawai',
            self::USER_CREATE => 'Akses Membuat Pegawai',
            self::USER_UPDATE => 'Akses Memperbarui Pegawai',
            self::USER_DELETE => 'Akses Menghapus Pegawai',
            self::USER_INVITE => 'Akses Mengundang Pegawai',

            // Role
            self::ROLE_ALL    => 'Semua Akses Role',
            self::ROLE_VIEW   => 'Akses Melihat Role',
            self::ROLE_CREATE => 'Akses Membuat Role',
            self::ROLE_UPDATE => 'Akses Memperbarui Role',
            self::ROLE_DELETE => 'Akses Menghapus Role',

            // Transaction
            self::TRANSACTION_ALL         => 'Semua Akses Transaksi',
            self::TRANSACTION_VIEW        => 'Akses Melihat Transaksi',
            self::TRANSACTION_CREATE      => 'Akses Membuat Transaksi',
            self::TRANSACTION_UPDATE      => 'Akses Memperbarui Transaksi',
            self::TRANSACTION_CANCEL      => 'Akses Membatalkan Transaksi',
            self::TRANSACTION_REFUND      => 'Akses Refund Transaksi',
            self::TRANSACTION_DISCOUNT    => 'Akses Diskon Transaksi',
            self::TRANSACTION_HOLD        => 'Akses Hold Transaksi',
            self::TRANSACTION_VOID        => 'Akses Void Transaksi',
            self::TRANSACTION_REPRINT     => 'Akses Cetak Ulang Transaksi',
            self::TRANSACTION_OPEN_SHIFT  => 'Akses Buka Shift',
            self::TRANSACTION_CLOSE_SHIFT => 'Akses Tutup Shift',

            // Product
            self::PRODUCT_ALL      => 'Semua Akses Produk',
            self::PRODUCT_VIEW     => 'Akses Melihat Produk',
            self::PRODUCT_CREATE   => 'Akses Membuat Produk',
            self::PRODUCT_UPDATE   => 'Akses Memperbarui Produk',
            self::PRODUCT_DELETE   => 'Akses Menghapus Produk',
            self::PRODUCT_IMPORT   => 'Akses Import Produk',
            self::PRODUCT_EXPORT   => 'Akses Export Produk',
            self::PRODUCT_VARIANT  => 'Akses Varian Produk',
            self::PRODUCT_MODIFIER => 'Akses Modifier Produk',
            self::PRODUCT_RECIPE   => 'Akses Resep Produk',

            // Category
            self::CATEGORY_ALL    => 'Semua Akses Kategori',
            self::CATEGORY_VIEW   => 'Akses Melihat Kategori',
            self::CATEGORY_CREATE => 'Akses Membuat Kategori',
            self::CATEGORY_UPDATE => 'Akses Memperbarui Kategori',
            self::CATEGORY_DELETE => 'Akses Menghapus Kategori',

            // Inventory
            self::INVENTORY_ALL          => 'Semua Akses Inventori',
            self::INVENTORY_VIEW         => 'Akses Melihat Inventori',
            self::INVENTORY_ADJUST       => 'Akses Penyesuaian Inventori (Lama)',
            self::INVENTORY_ADJUSTMENT_READ   => 'Akses Melihat Penyesuaian',
            self::INVENTORY_ADJUSTMENT_CREATE => 'Akses Membuat Penyesuaian',
            self::INVENTORY_ADJUSTMENT_APPROVE=> 'Akses Approval Penyesuaian',
            self::INVENTORY_ADJUSTMENT_VOID   => 'Akses Void Penyesuaian',
            self::INVENTORY_ADJUSTMENT_EXPORT => 'Akses Export Penyesuaian',
            self::INVENTORY_ADJUSTMENT_FREEZE => 'Akses Bekukan Stok Penyesuaian',
            self::INVENTORY_TRANSFER     => 'Akses Transfer Inventori',
            self::INVENTORY_STOCK_OPNAME => 'Akses Stock Opname',
            self::INVENTORY_MOVEMENT     => 'Akses Pergerakan Inventori',
            self::INVENTORY_PURCHASE     => 'Akses Pembelian Inventori',
            self::INVENTORY_WASTE        => 'Akses Waste Inventori',
            self::INVENTORY_RECEIVE      => 'Akses Penerimaan Inventori',

            // Supplier
            self::SUPPLIER_ALL    => 'Semua Akses Supplier',
            self::SUPPLIER_VIEW   => 'Akses Melihat Supplier',
            self::SUPPLIER_CREATE => 'Akses Membuat Supplier',
            self::SUPPLIER_UPDATE => 'Akses Memperbarui Supplier',
            self::SUPPLIER_DELETE => 'Akses Menghapus Supplier',

            // Purchase Order
            self::PURCHASE_ORDER_ALL     => 'Semua Akses Purchase Order',
            self::PURCHASE_ORDER_VIEW    => 'Akses Melihat Purchase Order',
            self::PURCHASE_ORDER_CREATE  => 'Akses Membuat Purchase Order',
            self::PURCHASE_ORDER_UPDATE  => 'Akses Memperbarui Purchase Order',
            self::PURCHASE_ORDER_APPROVE => 'Akses Approval Purchase Order',
            self::PURCHASE_ORDER_CANCEL  => 'Akses Membatalkan Purchase Order',
            self::PURCHASE_ORDER_RECEIVE => 'Akses Penerimaan Purchase Order',

            // Promo
            self::PROMO_ALL     => 'Semua Akses Promo',
            self::PROMO_VIEW    => 'Akses Melihat Promo',
            self::PROMO_CREATE  => 'Akses Membuat Promo',
            self::PROMO_UPDATE  => 'Akses Memperbarui Promo',
            self::PROMO_DELETE  => 'Akses Menghapus Promo',
            self::PROMO_PUBLISH => 'Akses Publish Promo',

            // Customer
            self::CUSTOMER_ALL     => 'Semua Akses Customer',
            self::CUSTOMER_VIEW    => 'Akses Melihat Customer',
            self::CUSTOMER_CREATE  => 'Akses Membuat Customer',
            self::CUSTOMER_UPDATE  => 'Akses Memperbarui Customer',
            self::CUSTOMER_DELETE  => 'Akses Menghapus Customer',
            self::CUSTOMER_LOYALTY => 'Akses Loyalty Customer',

            // Report
            self::REPORT_ALL       => 'Semua Akses Laporan',
            self::REPORT_SALES     => 'Akses Laporan Penjualan',
            self::REPORT_INVENTORY => 'Akses Laporan Inventori',
            self::REPORT_CASHFLOW  => 'Akses Laporan Cashflow',
            self::REPORT_SHIFT     => 'Akses Laporan Shift',
            self::REPORT_PRODUCT   => 'Akses Laporan Produk',
            self::REPORT_CUSTOMER  => 'Akses Laporan Customer',
            self::REPORT_EXPORT    => 'Akses Export Laporan',

            // Setting
            self::SETTING_ALL     => 'Semua Akses Pengaturan',
            self::SETTING_TAX     => 'Akses Pengaturan Pajak',
            self::SETTING_RECEIPT => 'Akses Pengaturan Struk',
            self::SETTING_PAYMENT => 'Akses Pengaturan Pembayaran',
            self::SETTING_DEVICE  => 'Akses Pengaturan Device',
            self::SETTING_PRINTER => 'Akses Pengaturan Printer',
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
}
