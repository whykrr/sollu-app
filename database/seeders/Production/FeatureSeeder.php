<?php

namespace Database\Seeders\Production;

use App\Models\Feature;
use Illuminate\Database\Seeder;

class FeatureSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
            // Penjualan & Kasir
            [
                'code' => 'pos',
                'name' => 'Aplikasi Kasir (POS)',
                'description' => 'Akses penuh ke antarmuka transaksi penjualan kasir (POS) berbasis web dan perangkat tablet.',
                'module' => 'pos',
                'group' => 'pos_and_transactions',
                'group_label' => 'Penjualan & Kasir',
                'sort_order' => 10,
            ],
            [
                'code' => 'shift_cashier',
                'name' => 'Manajemen Shift & Kas Kasir',
                'description' => 'Buka dan tutup shift kasir, tracking selisih kas fisik di laci (cash drawer), dan rekapitulasi shift kasir.',
                'module' => 'pos',
                'group' => 'pos_and_transactions',
                'group_label' => 'Penjualan & Kasir',
                'sort_order' => 20,
            ],
            [
                'code' => 'split_payment',
                'name' => 'Split & Multi Pembayaran',
                'description' => 'Membagi satu tagihan transaksi menggunakan kombinasi berbagai metode pembayaran berbeda.',
                'module' => 'pos',
                'group' => 'pos_and_transactions',
                'group_label' => 'Penjualan & Kasir',
                'sort_order' => 30,
            ],
            [
                'code' => 'transaction_invoice',
                'name' => 'Invoice, Piutang, Void & Refund',
                'description' => 'Penerbitan invoice tagihan tempo, penagihan piutang, otorisasi void transaksi, dan refund pengembalian dana.',
                'module' => 'pos',
                'group' => 'pos_and_transactions',
                'group_label' => 'Penjualan & Kasir',
                'sort_order' => 40,
            ],

            // Produk & Menu
            [
                'code' => 'product_catalog',
                'name' => 'Katalog Produk Barang',
                'description' => 'Manajemen data katalog produk barang fisik, foto, barcode/SKU, satuan unit (UOM), dan visibilitas penjualan.',
                'module' => 'product',
                'group' => 'products_and_menu',
                'group_label' => 'Produk & Menu',
                'sort_order' => 50,
            ],
            [
                'code' => 'service_catalog',
                'name' => 'Katalog Produk Layanan',
                'description' => 'Manajemen data produk jasa dan layanan, tarif dasar, kustomisasi tarif per outlet, dan penugasan staf.',
                'module' => 'product',
                'group' => 'products_and_menu',
                'group_label' => 'Produk & Menu',
                'sort_order' => 60,
            ],
            [
                'code' => 'product_variant',
                'name' => 'Varian Produk',
                'description' => 'Matriks variasi produk multi-dimensi (ukuran, warna, rasa) dengan SKU dan harga khusus.',
                'module' => 'product',
                'group' => 'products_and_menu',
                'group_label' => 'Produk & Menu',
                'sort_order' => 70,
            ],
            [
                'code' => 'product_price_matrix',
                'name' => 'Matriks Harga Produk',
                'description' => 'Kustomisasi penetapan harga khusus bertingkat, per cabang, atau per tipe pelanggan.',
                'module' => 'product',
                'group' => 'products_and_menu',
                'group_label' => 'Produk & Menu',
                'sort_order' => 80,
            ],
            [
                'code' => 'product_modifier',
                'name' => 'Opsi Tambahan (Modifier / Add-on)',
                'description' => 'Pengaturan opsi tambahan produk seperti topping, level gula, ekstra shot dengan tambahan harga.',
                'module' => 'product',
                'group' => 'products_and_menu',
                'group_label' => 'Produk & Menu',
                'sort_order' => 90,
            ],
            [
                'code' => 'product_bom',
                'name' => 'Manajemen Resep & BOM (Bill of Materials)',
                'description' => 'Perhitungan otomatis Harga Pokok Penjualan (HPP) berbasis komposisi bahan baku dan versi resep.',
                'module' => 'product',
                'group' => 'products_and_menu',
                'group_label' => 'Produk & Menu',
                'sort_order' => 100,
            ],

            // Inventori & Rantai Pasok
            [
                'code' => 'inventory_management',
                'name' => 'Manajemen Inventori & Stok',
                'description' => 'Pelacakan stok real-time, pengingat batas minimum stok, dan alokasi stok per outlet.',
                'module' => 'inventory',
                'group' => 'inventory_and_supply_chain',
                'group_label' => 'Inventori & Rantai Pasok',
                'sort_order' => 110,
            ],
            [
                'code' => 'raw_materials',
                'name' => 'Bahan Baku (Raw Materials)',
                'description' => 'Pengelolaan inventori khusus bahan baku dapur dan bahan setengah jadi yang tidak dijual langsung.',
                'module' => 'inventory',
                'group' => 'inventory_and_supply_chain',
                'group_label' => 'Inventori & Rantai Pasok',
                'sort_order' => 120,
            ],
            [
                'code' => 'stock_movements',
                'name' => 'Kartu Stok & Riwayat Mutasi',
                'description' => 'Audit jejak mutasi masuk/keluar stok, FIFO cost layers, dan riwayat pergerakan inventori terperinci.',
                'module' => 'inventory',
                'group' => 'inventory_and_supply_chain',
                'group_label' => 'Inventori & Rantai Pasok',
                'sort_order' => 130,
            ],
            [
                'code' => 'stock_adjustments',
                'name' => 'Penyesuaian Stok (Stock Adjustment)',
                'description' => 'Pencatatan selisih barang rusak, hilang, kedaluwarsa, dan penyesuaian stok manual.',
                'module' => 'inventory',
                'group' => 'inventory_and_supply_chain',
                'group_label' => 'Inventori & Rantai Pasok',
                'sort_order' => 140,
            ],
            [
                'code' => 'stock_opname',
                'name' => 'Stock Opname',
                'description' => 'Audit fisik stok berkala dengan alur draf penghitungan, approval manajer, dan rekonsiliasi otomatis.',
                'module' => 'inventory',
                'group' => 'inventory_and_supply_chain',
                'group_label' => 'Inventori & Rantai Pasok',
                'sort_order' => 150,
            ],
            [
                'code' => 'stock_transfers',
                'name' => 'Transfer Stok Antar Outlet',
                'description' => 'Distribusi dan transfer mutasi stok antar cabang dengan alur pengiriman (ship) dan penerimaan (receive).',
                'module' => 'inventory',
                'group' => 'inventory_and_supply_chain',
                'group_label' => 'Inventori & Rantai Pasok',
                'sort_order' => 160,
            ],
            [
                'code' => 'purchase_supplier',
                'name' => 'Manajemen Pemasok (Supplier)',
                'description' => 'Pencatatan kontak database vendor/supplier, katalog item supply, dan riwayat pesanan.',
                'module' => 'inventory',
                'group' => 'inventory_and_supply_chain',
                'group_label' => 'Inventori & Rantai Pasok',
                'sort_order' => 170,
            ],
            [
                'code' => 'purchase_order',
                'name' => 'Pesanan Pembelian (Purchase Order)',
                'description' => 'Pembuatan purchase order (PO), alur persetujuan pembelian barang, dan pencatatan barang datang.',
                'module' => 'inventory',
                'group' => 'inventory_and_supply_chain',
                'group_label' => 'Inventori & Rantai Pasok',
                'sort_order' => 180,
            ],

            // Promosi & Pemasaran
            [
                'code' => 'promo_management',
                'name' => 'Manajemen Promosi & Diskon',
                'description' => 'Pembuatan diskon persentase, nominal dengan jadwal dan kuota penggunaan.',
                'module' => 'promo',
                'group' => 'promotions_and_marketing',
                'group_label' => 'Promosi & Pemasaran',
                'sort_order' => 190,
            ],

            // Pelanggan & CRM
            [
                'code' => 'crm',
                'name' => 'Database Pelanggan & CRM',
                'description' => 'Pengelolaan direktori profil pelanggan, kontak, preferensi, dan histori transaksi belanja.',
                'module' => 'crm',
                'group' => 'customers_and_crm',
                'group_label' => 'Pelanggan & CRM',
                'sort_order' => 200,
            ],
            [
                'code' => 'crm_loyalty',
                'name' => 'Program Loyalitas & Poin Pelanggan',
                'description' => 'Sistem akumulasi poin belanja, tingkatan membership pelanggan, dan penukaran reward loyalitas.',
                'module' => 'crm',
                'group' => 'customers_and_crm',
                'group_label' => 'Pelanggan & CRM',
                'sort_order' => 210,
            ],

            // Laporan & Analitik
            [
                'code' => 'advanced_reports',
                'name' => 'Laporan & Analitik Lanjutan',
                'description' => 'Analisis mendalam performa bisnis, profitabilitas margin, tren penjualan, dan estimasi laba kotor.',
                'module' => 'report',
                'group' => 'reports_and_analytics',
                'group_label' => 'Laporan & Analitik',
                'sort_order' => 220,
            ],
            [
                'code' => 'report_sales',
                'name' => 'Laporan Penjualan Detail',
                'description' => 'Rincian transaksi per periode, filter multi-cabang, grafik jam sibuk, dan performa kasir.',
                'module' => 'report',
                'group' => 'reports_and_analytics',
                'group_label' => 'Laporan & Analitik',
                'sort_order' => 230,
            ],
            [
                'code' => 'report_product',
                'name' => 'Laporan Performa Produk',
                'description' => 'Laporan produk terlaris (top selling), kontribusi margin keuntungan produk, dan produk lambat terjual.',
                'module' => 'report',
                'group' => 'reports_and_analytics',
                'group_label' => 'Laporan & Analitik',
                'sort_order' => 240,
            ],
            [
                'code' => 'report_inventory',
                'name' => 'Laporan Stok & Valuasi Aset',
                'description' => 'Laporan sisa stok, valuasi nilai aset inventori menggunakan FIFO, dan peringatan stok menipis.',
                'module' => 'report',
                'group' => 'reports_and_analytics',
                'group_label' => 'Laporan & Analitik',
                'sort_order' => 250,
            ],
            [
                'code' => 'report_shift',
                'name' => 'Laporan Kasir & Shift',
                'description' => 'Rekonsiliasi kas per kasir, tracking selisih fisik uang kas (over/short), dan rekap shift operasional.',
                'module' => 'report',
                'group' => 'reports_and_analytics',
                'group_label' => 'Laporan & Analitik',
                'sort_order' => 260,
            ],
            [
                'code' => 'promo_reports',
                'name' => 'Laporan Efektivitas Promo',
                'description' => 'Evaluasi efektivitas promosi, total diskon yang terserap, dan dampak program promo terhadap omzet.',
                'module' => 'report',
                'group' => 'reports_and_analytics',
                'group_label' => 'Laporan & Analitik',
                'sort_order' => 270,
            ],

            // Outlet & Operasional
            [
                'code' => 'multi_outlet',
                'name' => 'Multi Outlet & Konfigurasi Cabang',
                'description' => 'Kemampuan mengelola banyak cabang usaha dalam satu akun terpusat beserta seluruh pengaturannya.',
                'module' => 'outlet',
                'group' => 'outlets_and_operations',
                'group_label' => 'Outlet & Operasional',
                'sort_order' => 280,
            ],
            [
                'code' => 'multi_device',
                'name' => 'Multi Perangkat POS',
                'description' => 'Menghubungkan banyak perangkat tablet dan kasir dalam satu outlet secara bersamaan.',
                'module' => 'outlet',
                'group' => 'outlets_and_operations',
                'group_label' => 'Outlet & Operasional',
                'sort_order' => 290,
            ],

            // Karyawan & Keamanan
            [
                'code' => 'employee_management',
                'name' => 'Manajemen Pegawai & Staf',
                'description' => 'Manajemen data staf/karyawan, kode PIN kasir cepat, dan penugasan karyawan ke cabang tertentu.',
                'module' => 'employee',
                'group' => 'employees_and_security',
                'group_label' => 'Karyawan & Hak Akses',
                'sort_order' => 300,
            ],
            [
                'code' => 'custom_role',
                'name' => 'Peran Kustom Karyawan',
                'description' => 'Pembuatan peran/role khusus sesuai kebutuhan jabatan internal bisnis.',
                'module' => 'employee',
                'group' => 'employees_and_security',
                'group_label' => 'Karyawan & Hak Akses',
                'sort_order' => 310,
            ],
            [
                'code' => 'audit_log',
                'name' => 'Log Audit & Jejak Aktivitas',
                'description' => 'Pencatatan riwayat audit aktivitas user saat mengubah harga, menghapus data, dan tindakan sensitif.',
                'module' => 'employee',
                'group' => 'employees_and_security',
                'group_label' => 'Karyawan & Hak Akses',
                'sort_order' => 320,
            ],
            [
                'code' => 'double_authorization',
                'name' => 'Validasi Supervisor & Double Authorization',
                'description' => 'Otorisasi ganda oleh manajer/supervisor untuk persetujuan transaksi sensitif dan penyesuaian khusus.',
                'module' => 'employee',
                'group' => 'employees_and_security',
                'group_label' => 'Karyawan & Hak Akses',
                'sort_order' => 330,
            ],

            // Pengaturan & Personalisasi
            [
                'code' => 'feature_personalization',
                'name' => 'Personalisasi Fitur',
                'description' => 'Kemampuan untuk menyesuaikan, mengaktifkan, atau menyembunyikan modul sesuai alur kerja tokomu.',
                'module' => 'settings',
                'group' => 'outlets_and_operations',
                'group_label' => 'Outlet & Operasional',
                'sort_order' => 340,
            ],
        ];

        $now = now();
        $records = array_map(function ($item) use ($now) {
            $item['is_active'] = true;
            $item['created_at'] = $now;
            $item['updated_at'] = $now;

            return $item;
        }, $features);

        // set active feature for production is false
        Feature::query()->update(['is_active' => false]);

        Feature::upsert(
            $records,
            ['code'],
            ['name', 'description', 'module', 'group', 'group_label', 'sort_order', 'is_active', 'updated_at']
        );

        $validCodes = array_column($features, 'code');
        Feature::query()->whereNotIn('code', $validCodes)->delete();

        Feature::clearCache();
    }
}
