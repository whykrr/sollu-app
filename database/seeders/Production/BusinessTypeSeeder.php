<?php

namespace Database\Seeders\Production;

use App\Enums\FeatureEnum;
use App\Models\BusinessType;
use Illuminate\Database\Seeder;

class BusinessTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $commonFeatures = [
            // Penjualan & Kasir
            FeatureEnum::POS_CASHIER->value,
            FeatureEnum::SHIFT_MANAGEMENT->value,
            FeatureEnum::CASH_DRAWER->value,
            FeatureEnum::SPLIT_PAYMENT->value,
            FeatureEnum::INVOICE_DEBT->value,
            FeatureEnum::VOID_REFUND->value,

            // Produk & Katalog
            FeatureEnum::PRODUCT_CATALOG->value,
            FeatureEnum::PRODUCT_CATEGORIES->value,
            FeatureEnum::PRODUCT_VARIANTS->value,
            FeatureEnum::PRODUCT_BUNDLES->value,

            // Inventori & Rantai Pasok
            FeatureEnum::INVENTORY_MANAGEMENT->value,
            FeatureEnum::STOCK_MOVEMENTS->value,
            FeatureEnum::STOCK_ADJUSTMENTS->value,
            FeatureEnum::STOCK_FREEZE->value,
            FeatureEnum::STOCK_OPNAME->value,
            FeatureEnum::STOCK_TRANSFERS->value,
            FeatureEnum::SUPPLIER_MANAGEMENT->value,
            FeatureEnum::PURCHASE_ORDERS->value,

            // Promosi & Pemasaran
            FeatureEnum::PROMO_MANAGEMENT->value,

            // Pelanggan & CRM
            FeatureEnum::CUSTOMER_MANAGEMENT->value,

            // Laporan & Analitik
            FeatureEnum::BASIC_REPORTS->value,
            FeatureEnum::ADVANCED_REPORTS->value,
            FeatureEnum::SALES_REPORTS->value,
            FeatureEnum::PRODUCT_REPORTS->value,
            FeatureEnum::STOCK_REPORTS->value,
            FeatureEnum::CASHIER_REPORTS->value,
            FeatureEnum::PROMO_REPORTS->value,
            FeatureEnum::CUSTOMER_REPORTS->value,

            // Outlet & Operasional
            FeatureEnum::MULTI_OUTLET->value,
            FeatureEnum::OUTLET_MANAGEMENT->value,
            FeatureEnum::MULTI_DEVICE->value,

            // Karyawan & Keamanan
            FeatureEnum::EMPLOYEE_MANAGEMENT->value,
            FeatureEnum::CUSTOM_ROLE->value,
            FeatureEnum::AUDIT_LOGS->value,
        ];

        $fnbFeatures = array_merge($commonFeatures, [
            FeatureEnum::PRODUCT_MODIFIERS->value,
            FeatureEnum::RECIPE_MANAGEMENT->value,
            FeatureEnum::RAW_MATERIALS->value,
        ]);

        $types = [
            // Ritel & Toko (Retail)
            [
                'code' => 'minimarket',
                'name' => 'Minimarket',
                'category' => 'retail',
                'category_label' => 'Ritel & Toko',
                'is_visible' => true,
                'sort_order' => 1,
                'features' => $commonFeatures,
            ],
            [
                'code' => 'grocery',
                'name' => 'Grocery / Sembako',
                'category' => 'retail',
                'category_label' => 'Ritel & Toko',
                'is_visible' => true,
                'sort_order' => 2,
                'features' => $commonFeatures,
            ],
            [
                'code' => 'convenience_store',
                'name' => 'Toserba',
                'category' => 'retail',
                'category_label' => 'Ritel & Toko',
                'is_visible' => true,
                'sort_order' => 3,
                'features' => $commonFeatures,
            ],
            [
                'code' => 'fashion_store',
                'name' => 'Toko Fesyen',
                'category' => 'retail',
                'category_label' => 'Ritel & Toko',
                'is_visible' => true,
                'sort_order' => 4,
                'features' => $commonFeatures,
            ],
            [
                'code' => 'pharmacy',
                'name' => 'Apotek',
                'category' => 'retail',
                'category_label' => 'Ritel & Toko',
                'is_visible' => false,
                'sort_order' => 5,
                'features' => $commonFeatures,
            ],
            [
                'code' => 'vape_store',
                'name' => 'Vape Store',
                'category' => 'retail',
                'category_label' => 'Ritel & Toko',
                'is_visible' => false,
                'sort_order' => 6,
                'features' => $commonFeatures,
            ],
            [
                'code' => 'thrift_store',
                'name' => 'Toko Thrift',
                'category' => 'retail',
                'category_label' => 'Ritel & Toko',
                'is_visible' => false,
                'sort_order' => 7,
                'features' => $commonFeatures,
            ],

            // Makanan & Minuman (F&B)
            [
                'code' => 'coffee_shop',
                'name' => 'Coffee Shop',
                'category' => 'fnb',
                'category_label' => 'Makanan & Minuman (F&B)',
                'is_visible' => true,
                'sort_order' => 8,
                'features' => $fnbFeatures,
            ],
            [
                'code' => 'restaurant',
                'name' => 'Restoran',
                'category' => 'fnb',
                'category_label' => 'Makanan & Minuman (F&B)',
                'is_visible' => true,
                'sort_order' => 9,
                'features' => $fnbFeatures,
            ],
            [
                'code' => 'food_stall',
                'name' => 'Kedai Makanan',
                'category' => 'fnb',
                'category_label' => 'Makanan & Minuman (F&B)',
                'is_visible' => true,
                'sort_order' => 10,
                'features' => $fnbFeatures,
            ],
            [
                'code' => 'bakery',
                'name' => 'Bakery',
                'category' => 'fnb',
                'category_label' => 'Makanan & Minuman (F&B)',
                'is_visible' => true,
                'sort_order' => 11,
                'features' => $fnbFeatures,
            ],

            // Jasa & Layanan (Service)
            [
                'code' => 'laundry',
                'name' => 'Laundry',
                'category' => 'service',
                'category_label' => 'Jasa & Layanan',
                'is_visible' => false,
                'sort_order' => 12,
                'features' => $commonFeatures,
            ],
            [
                'code' => 'barbershop',
                'name' => 'Barbershop',
                'category' => 'service',
                'category_label' => 'Jasa & Layanan',
                'is_visible' => false,
                'sort_order' => 13,
                'features' => $commonFeatures,
            ],
            [
                'code' => 'salon',
                'name' => 'Salon, Spa & Beauty',
                'category' => 'service',
                'category_label' => 'Jasa & Layanan',
                'is_visible' => false,
                'sort_order' => 14,
                'features' => $commonFeatures,
            ],
            [
                'code' => 'repair_shop',
                'name' => 'Bengkel',
                'category' => 'service',
                'category_label' => 'Jasa & Layanan',
                'is_visible' => false,
                'sort_order' => 15,
                'features' => $commonFeatures,
            ],
        ];

        foreach ($types as $typeData) {
            BusinessType::updateOrCreate(
                ['code' => $typeData['code']],
                $typeData
            );
        }
    }
}
