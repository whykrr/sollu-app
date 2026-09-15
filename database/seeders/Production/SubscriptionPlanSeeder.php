<?php

namespace Database\Seeders\Production;

use App\Models\Feature;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $featuresByCode = Feature::all()->keyBy('code');

        $plans = [
            'micro' => [
                'name' => 'Paket Mikro',
                'price_per_outlet' => 59000,
                'max_outlet' => 1,
                'yearly_discount_percent' => 20,
                'is_active' => true,
                'is_public' => true,
                'is_custom' => false,
                'ui_features' => [
                    ['title' => 'Aplikasi Kasir (POS) Dasar', 'detail' => 'Penjualan cepat, shift, dan laci kas'],
                    ['title' => 'Katalog Produk', 'detail' => 'Kategori dan data produk standar'],
                    ['title' => 'Single Outlet & Single User', 'detail' => '1 cabang dan 1 pengguna kasir'],
                    ['title' => 'Promosi Dasar & Laporan', 'detail' => 'Diskon standar dan ringkasan omzet harian'],
                ],
                'system_features' => [
                    'pos_cashier',
                    'shift_management',
                    'cash_drawer',
                    'product_catalog',
                    'product_categories',
                    'promo_management',
                    'basic_reports',
                ],
            ],
            'basic' => [
                'name' => 'Paket Basic',
                'price_per_outlet' => 129000,
                'max_outlet' => 5,
                'yearly_discount_percent' => 20,
                'is_active' => true,
                'is_public' => true,
                'is_custom' => false,
                'ui_features' => [
                    ['title' => 'Semua Fitur Mikro', 'detail' => 'Termasuk POS dasar dan laporan'],
                    ['title' => 'Varian Produk', 'detail' => 'Ukuran, warna, rasa, dan multi SKU'],
                    ['title' => 'Multi Outlet & Multi User', 'detail' => 'Kelola cabang dan banyak staf per outlet'],
                    ['title' => 'Integrasi Inventori Dasar', 'detail' => 'Stok real-time, opname, mutasi, supplier & PO'],
                    ['title' => 'Advance Promo & CRM Basic', 'detail' => 'Voucher diskon dan database pelanggan'],
                    ['title' => 'Laporan Penjualan & Produk Lanjutan', 'detail' => 'Analisis tren omzet dan produk terlaris'],
                ],
                'system_features' => [
                    // Micro features
                    'pos_cashier',
                    'shift_management',
                    'cash_drawer',
                    'product_catalog',
                    'product_categories',
                    'promo_management',
                    'basic_reports',
                    // Basic features
                    'product_variants',
                    'multi_outlet',
                    'employee_management',
                    'custom_role',
                    'role_permissions',
                    'inventory_management',
                    'stock_movements',
                    'stock_adjustments',
                    'stock_opname',
                    'supplier_management',
                    'purchase_orders',
                    'discount_vouchers',
                    'customer_management',
                    'advanced_reports',
                    'sales_reports',
                    'product_reports',
                ],
            ],
            'pro' => [
                'name' => 'Paket Pro',
                'price_per_outlet' => 299000,
                'max_outlet' => 99,
                'yearly_discount_percent' => 20,
                'is_active' => true,
                'is_public' => true,
                'is_custom' => false,
                'ui_features' => [
                    ['title' => 'Semua Fitur Basic', 'detail' => 'Termasuk varian, inventori, dan multi outlet'],
                    ['title' => 'Multi Perangkat POS (Paralel)', 'detail' => 'Hubungkan banyak tablet waiter dan kasir'],
                    ['title' => 'Resep & HPP Otomatis', 'detail' => 'Bahan baku dapur dan kalkulasi HPP otomatis'],
                    ['title' => 'Transfer & Pembekuan Stok', 'detail' => 'Distribusi stok antar cabang dan freeze audit'],
                    ['title' => 'Program Loyalitas & Poin', 'detail' => 'Membership tier dan reward pelanggan'],
                    ['title' => 'Kasir Lanjutan (Split Bill, Void, Piutang)', 'detail' => 'Fleksibilitas transaksi komprehensif'],
                    ['title' => 'Laporan Keuangan, Valuasi & Kasir Lengkap', 'detail' => 'Audit shift, margin, dan FIFO asset'],
                ],
                'system_features' => [
                    // Basic features
                    'pos_cashier',
                    'shift_management',
                    'cash_drawer',
                    'product_catalog',
                    'product_categories',
                    'promo_management',
                    'basic_reports',
                    'product_variants',
                    'multi_outlet',
                    'employee_management',
                    'custom_role',
                    'role_permissions',
                    'inventory_management',
                    'stock_movements',
                    'stock_adjustments',
                    'stock_opname',
                    'supplier_management',
                    'purchase_orders',
                    'discount_vouchers',
                    'customer_management',
                    'advanced_reports',
                    'sales_reports',
                    'product_reports',
                    // Pro features
                    'multi_device',
                    'device_management',
                    'operational_hours',
                    'receipt_customization',
                    'tax_and_service_charge',
                    'custom_payment_methods',
                    'split_payment',
                    'invoice_debt',
                    'void_refund',
                    'product_modifiers',
                    'product_bundles',
                    'raw_materials',
                    'recipe_management',
                    'stock_transfers',
                    'stock_freeze',
                    'customer_loyalty',
                    'stock_reports',
                    'cashier_reports',
                    'promo_reports',
                    'customer_reports',
                    'unlimited_users',
                    'audit_logs',
                    'payment_gateway',
                    'pos_device_sync',
                    'developer_api',
                ],
            ],
        ];

        foreach ($plans as $code => $data) {
            $plan = SubscriptionPlan::updateOrCreate(
                ['code' => $code],
                [
                    'name' => $data['name'],
                    'price_per_outlet' => $data['price_per_outlet'],
                    'max_outlet' => $data['max_outlet'],
                    'yearly_discount_percent' => $data['yearly_discount_percent'],
                    'features' => $data['ui_features'],
                    'is_active' => $data['is_active'],
                    'is_public' => $data['is_public'],
                    'is_custom' => $data['is_custom'],
                ]
            );

            // Sync features to plan_features
            $featureIds = [];
            foreach ($data['system_features'] as $featureCode) {
                if (isset($featuresByCode[$featureCode])) {
                    $featureIds[] = $featuresByCode[$featureCode]->id;
                }
            }

            $plan->systemFeatures()->sync($featureIds);
            $plan->clearFeatureCache();
        }

        // Deactivate ultimate plan if it exists
        $ultimatePlan = SubscriptionPlan::where('code', 'ultimate')->first();
        if ($ultimatePlan) {
            $ultimatePlan->update([
                'is_active' => false,
                'is_public' => false,
            ]);
            // Give it pro features for legacy compatibility
            if (isset($plans['pro'])) {
                $proFeatureIds = [];
                foreach ($plans['pro']['system_features'] as $fCode) {
                    if (isset($featuresByCode[$fCode])) {
                        $proFeatureIds[] = $featuresByCode[$fCode]->id;
                    }
                }
                $ultimatePlan->systemFeatures()->sync($proFeatureIds);
                $ultimatePlan->clearFeatureCache();
            }
        }
    }
}
