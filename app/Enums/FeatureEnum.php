<?php

namespace App\Enums;

enum FeatureEnum: string
{
    /*
    |--------------------------------------------------------------------------
    | Penjualan & Kasir (Point of Sale & Transactions)
    |--------------------------------------------------------------------------
    */

    case POS_CASHIER = 'pos_cashier';
    case SHIFT_MANAGEMENT = 'shift_management';
    case CASH_DRAWER = 'cash_drawer';
    case SPLIT_PAYMENT = 'split_payment';
    case INVOICE_DEBT = 'invoice_debt';
    case VOID_REFUND = 'void_refund';

    /*
    |--------------------------------------------------------------------------
    | Produk & Menu (Products & Catalog)
    |--------------------------------------------------------------------------
    */

    case PRODUCT_CATALOG = 'product_catalog';
    case PRODUCT_CATEGORIES = 'product_categories';
    case PRODUCT_VARIANTS = 'product_variants';
    case PRODUCT_MODIFIERS = 'product_modifiers';
    case PRODUCT_BUNDLES = 'product_bundles';
    case RECIPE_MANAGEMENT = 'recipe_management';

    /*
    |--------------------------------------------------------------------------
    | Inventori & Rantai Pasok (Inventory & Supply Chain)
    |--------------------------------------------------------------------------
    */

    case INVENTORY_MANAGEMENT = 'inventory_management';
    case RAW_MATERIALS = 'raw_materials';
    case STOCK_MOVEMENTS = 'stock_movements';
    case STOCK_ADJUSTMENTS = 'stock_adjustments';
    case STOCK_FREEZE = 'stock_freeze';
    case STOCK_OPNAME = 'stock_opname';
    case STOCK_TRANSFERS = 'stock_transfers';
    case SUPPLIER_MANAGEMENT = 'supplier_management';
    case PURCHASE_ORDERS = 'purchase_orders';

    /*
    |--------------------------------------------------------------------------
    | Promosi & Pemasaran (Promotions & Marketing)
    |--------------------------------------------------------------------------
    */

    case PROMO_MANAGEMENT = 'promo_management';

    /*
    |--------------------------------------------------------------------------
    | Pelanggan & CRM (Customers & Loyalty)
    |--------------------------------------------------------------------------
    */

    case CUSTOMER_MANAGEMENT = 'customer_management';

    /*
    |--------------------------------------------------------------------------
    | Laporan & Analitik (Reports & Analytics)
    |--------------------------------------------------------------------------
    */

    case BASIC_REPORTS = 'basic_reports';
    case ADVANCED_REPORTS = 'advanced_reports';
    case SALES_REPORTS = 'sales_reports';
    case PRODUCT_REPORTS = 'product_reports';
    case STOCK_REPORTS = 'stock_reports';
    case CASHIER_REPORTS = 'cashier_reports';
    case PROMO_REPORTS = 'promo_reports';
    case CUSTOMER_REPORTS = 'customer_reports';

    /*
    |--------------------------------------------------------------------------
    | Pengaturan Outlet & Operasional (Outlets & Operations)
    |--------------------------------------------------------------------------
    */

    case MULTI_OUTLET = 'multi_outlet';
    case OUTLET_MANAGEMENT = 'outlet_management';
    case MULTI_DEVICE = 'multi_device';

    /*
    |--------------------------------------------------------------------------
    | Karyawan & Keamanan (Employees & Security)
    |--------------------------------------------------------------------------
    */

    case EMPLOYEE_MANAGEMENT = 'employee_management';
    case CUSTOM_ROLE = 'custom_role';
    case AUDIT_LOGS = 'audit_logs';
}
