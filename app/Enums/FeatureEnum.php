<?php

namespace App\Enums;

enum FeatureEnum: string
{
    /*
    |--------------------------------------------------------------------------
    | Penjualan & Kasir (Point of Sale & Transactions)
    |--------------------------------------------------------------------------
    */

    case POS = 'pos';
    case SHIFT_CASHIER = 'shift_cashier';
    case CASH_DRAWER = 'cash_drawer';
    case SPLIT_PAYMENT = 'split_payment';
    case TRANSACTION_INVOICE = 'transaction_invoice';

    /*
    |--------------------------------------------------------------------------
    | Produk & Menu (Products & Catalog)
    |--------------------------------------------------------------------------
    */

    case PRODUCT_CATALOG = 'product_catalog';
    case SERVICE_CATALOG = 'service_catalog';
    case PRODUCT_VARIANT = 'product_variant';
    case PRODUCT_PRICE_MATRIX = 'product_price_matrix';
    case PRODUCT_MODIFIER = 'product_modifier';
    case PRODUCT_BOM = 'product_bom';

    /*
    |--------------------------------------------------------------------------
    | Inventori & Rantai Pasok (Inventory & Supply Chain)
    |--------------------------------------------------------------------------
    */

    case INVENTORY_MANAGEMENT = 'inventory_management';
    case RAW_MATERIALS = 'raw_materials';
    case STOCK_MOVEMENTS = 'stock_movements';
    case STOCK_ADJUSTMENTS = 'stock_adjustments';
    case STOCK_OPNAME = 'stock_opname';
    case STOCK_TRANSFERS = 'stock_transfers';
    case PURCHASE_SUPPLIER = 'purchase_supplier';
    case PURCHASE_ORDER = 'purchase_order';

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

    case CRM = 'crm';
    case CRM_LOYALTY = 'crm_loyalty';

    /*
    |--------------------------------------------------------------------------
    | Laporan & Analitik (Reports & Analytics)
    |--------------------------------------------------------------------------
    */

    case ADVANCED_REPORTS = 'advanced_reports';
    case REPORT_SALES = 'report_sales';
    case REPORT_PRODUCT = 'report_product';
    case REPORT_INVENTORY = 'report_inventory';
    case REPORT_SHIFT = 'report_shift';
    case PROMO_REPORTS = 'promo_reports';

    /*
    |--------------------------------------------------------------------------
    | Pengaturan Outlet & Operasional (Outlets & Operations)
    |--------------------------------------------------------------------------
    */

    case MULTI_OUTLET = 'multi_outlet';
    case MULTI_DEVICE = 'multi_device';

    /*
    |--------------------------------------------------------------------------
    | Karyawan & Keamanan (Employees & Security)
    |--------------------------------------------------------------------------
    */

    case EMPLOYEE_MANAGEMENT = 'employee_management';
    case CUSTOM_ROLE = 'custom_role';
    case AUDIT_LOG = 'audit_log';
    case DOUBLE_AUTHORIZATION = 'double_authorization';

    /*
    |--------------------------------------------------------------------------
    | Personalisasi & Konfigurasi (Customization & Personalization)
    |--------------------------------------------------------------------------
    */

    case FEATURE_PERSONALIZATION = 'feature_personalization';
}
