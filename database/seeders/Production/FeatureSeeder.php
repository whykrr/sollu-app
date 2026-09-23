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
                'code' => 'pos_cashier',
                'name' => 'Aplikasi Kasir (POS)',
                'description' => 'Akses penuh ke antarmuka transaksi penjualan kasir (POS) berbasis web dan perangkat tablet.',
                'module' => 'pos',
                'group' => 'pos_and_transactions',
                'group_label' => 'Penjualan & Kasir',
                'sort_order' => 10,
            ],
            [
                'code' => 'shift_management',
                'name' => 'Manajemen Shift Kasir',
                'description' => 'Buka dan tutup shift kasir, tracking selisih kas, dan pergantian jam kerja staf kasir.',
                'module' => 'pos',
                'group' => 'pos_and_transactions',
                'group_label' => 'Penjualan & Kasir',
                'sort_order' => 20,
            ],
            [
                'code' => 'cash_drawer',
                'name' => 'Manajemen Kas Laci (Cash Drawer)',
                'description' => 'Pencatatan kas masuk, kas keluar, modal awal, dan rekonsiliasi uang fisik di laci kasir.',
                'module' => 'pos',
                'group' => 'pos_and_transactions',
                'group_label' => 'Penjualan & Kasir',
                'sort_order' => 30,
            ],
            [
                'code' => 'split_payment',
                'name' => 'Split & Multi Pembayaran',
                'description' => 'Membagi satu tagihan transaksi menggunakan kombinasi berbagai metode pembayaran berbeda.',
                'module' => 'pos',
                'group' => 'pos_and_transactions',
                'group_label' => 'Penjualan & Kasir',
                'sort_order' => 40,
            ],
            [
                'code' => 'invoice_debt',
                'name' => 'Invoice & Piutang Pelanggan',
                'description' => 'Penerbitan invoice tagihan tempo, penagihan, serta pencatatan cicilan dan pelunasan piutang pelanggan.',
                'module' => 'pos',
                'group' => 'pos_and_transactions',
                'group_label' => 'Penjualan & Kasir',
                'sort_order' => 50,
            ],
            [
                'code' => 'void_refund',
                'name' => 'Void & Refund Transaksi',
                'description' => 'Otorisasi pembatalan transaksi tersimpan, void struk kasir, dan proses pengembalian dana transaksi.',
                'module' => 'pos',
                'group' => 'pos_and_transactions',
                'group_label' => 'Penjualan & Kasir',
                'sort_order' => 60,
            ],

            // Produk & Menu
            [
                'code' => 'product_catalog',
                'name' => 'Katalog Produk',
                'description' => 'Manajemen data katalog produk, foto, barcode/SKU, satuan unit (UOM), dan visibilitas penjualan.',
                'module' => 'product',
                'group' => 'products_and_menu',
                'group_label' => 'Produk & Menu',
                'sort_order' => 70,
            ],
            [
                'code' => 'product_categories',
                'name' => 'Kategori Produk',
                'description' => 'Pengelompokan hierarki kategori produk dan pengaturan urutan display kategori menu kasir.',
                'module' => 'product',
                'group' => 'products_and_menu',
                'group_label' => 'Produk & Menu',
                'sort_order' => 80,
            ],
            [
                'code' => 'product_variants',
                'name' => 'Varian Produk',
                'description' => 'Matriks variasi produk multi-dimensi (ukuran, warna, rasa) dengan SKU dan harga khusus.',
                'module' => 'product',
                'group' => 'products_and_menu',
                'group_label' => 'Produk & Menu',
                'sort_order' => 90,
            ],
            [
                'code' => 'product_modifiers',
                'name' => 'Opsi Tambahan (Modifier / Add-on)',
                'description' => 'Pengaturan opsi tambahan produk seperti topping, level gula, ekstra shot dengan tambahan harga.',
                'module' => 'product',
                'group' => 'products_and_menu',
                'group_label' => 'Produk & Menu',
                'sort_order' => 100,
            ],
            [
                'code' => 'product_bundles',
                'name' => 'Paket Produk (Bundling / Combo)',
                'description' => 'Penjualan paket bundling / combo beberapa produk dengan harga paket promo terintegrasi.',
                'module' => 'product',
                'group' => 'products_and_menu',
                'group_label' => 'Produk & Menu',
                'sort_order' => 110,
            ],
            [
                'code' => 'recipe_management',
                'name' => 'Manajemen Resep & HPP',
                'description' => 'Perhitungan otomatis Harga Pokok Penjualan (HPP) berbasis komposisi bahan baku dan versi resep.',
                'module' => 'product',
                'group' => 'products_and_menu',
                'group_label' => 'Produk & Menu',
                'sort_order' => 120,
            ],

            // Inventori & Rantai Pasok
            [
                'code' => 'inventory_management',
                'name' => 'Manajemen Inventori & Stok',
                'description' => 'Pelacakan stok real-time, pengingat batas minimum stok, dan alokasi stok per outlet.',
                'module' => 'inventory',
                'group' => 'inventory_and_supply_chain',
                'group_label' => 'Inventori & Rantai Pasok',
                'sort_order' => 130,
            ],
            [
                'code' => 'raw_materials',
                'name' => 'Bahan Baku (Raw Materials)',
                'description' => 'Pengelolaan inventori khusus bahan baku dapur dan bahan setengah jadi yang tidak dijual langsung.',
                'module' => 'inventory',
                'group' => 'inventory_and_supply_chain',
                'group_label' => 'Inventori & Rantai Pasok',
                'sort_order' => 140,
            ],
            [
                'code' => 'stock_movements',
                'name' => 'Kartu Stok & Riwayat Mutasi',
                'description' => 'Audit jejak mutasi masuk/keluar stok, FIFO cost layers, dan riwayat pergerakan inventori terperinci.',
                'module' => 'inventory',
                'group' => 'inventory_and_supply_chain',
                'group_label' => 'Inventori & Rantai Pasok',
                'sort_order' => 150,
            ],
            [
                'code' => 'stock_adjustments',
                'name' => 'Penyesuaian Stok (Stock Adjustment)',
                'description' => 'Pencatatan selisih barang rusak, hilang, kedaluwarsa, dan penyesuaian stok manual.',
                'module' => 'inventory',
                'group' => 'inventory_and_supply_chain',
                'group_label' => 'Inventori & Rantai Pasok',
                'sort_order' => 160,
            ],
            [
                'code' => 'stock_freeze',
                'name' => 'Pembekuan Stok (Stock Freeze)',
                'description' => 'Membekukan pergerakan stok outlet sementara selama proses audit atau stock opname berlangsung.',
                'module' => 'inventory',
                'group' => 'inventory_and_supply_chain',
                'group_label' => 'Inventori & Rantai Pasok',
                'sort_order' => 170,
            ],
            [
                'code' => 'stock_opname',
                'name' => 'Stock Opname',
                'description' => 'Audit fisik stok berkala dengan alur draf penghitungan, approval manajer, dan rekonsiliasi otomatis.',
                'module' => 'inventory',
                'group' => 'inventory_and_supply_chain',
                'group_label' => 'Inventori & Rantai Pasok',
                'sort_order' => 180,
            ],
            [
                'code' => 'stock_transfers',
                'name' => 'Transfer Stok Antar Outlet',
                'description' => 'Distribusi dan transfer mutasi stok antar cabang dengan alur pengiriman (ship) dan penerimaan (receive).',
                'module' => 'inventory',
                'group' => 'inventory_and_supply_chain',
                'group_label' => 'Inventori & Rantai Pasok',
                'sort_order' => 190,
            ],
            [
                'code' => 'supplier_management',
                'name' => 'Manajemen Pemasok (Supplier)',
                'description' => 'Pencatatan kontak database vendor/supplier, katalog item supply, dan riwayat pesanan.',
                'module' => 'inventory',
                'group' => 'inventory_and_supply_chain',
                'group_label' => 'Inventori & Rantai Pasok',
                'sort_order' => 200,
            ],
            [
                'code' => 'purchase_orders',
                'name' => 'Pesanan Pembelian (Purchase Order)',
                'description' => 'Pembuatan purchase order (PO), alur persetujuan pembelian barang, dan pencatatan barang datang.',
                'module' => 'inventory',
                'group' => 'inventory_and_supply_chain',
                'group_label' => 'Inventori & Rantai Pasok',
                'sort_order' => 210,
            ],

            // Promosi & Pemasaran
            [
                'code' => 'promo_management',
                'name' => 'Manajemen Promosi & Diskon',
                'description' => 'Pembuatan diskon persentase, nominal dengan jadwal dan kuota penggunaan.',
                'module' => 'promo',
                'group' => 'promotions_and_marketing',
                'group_label' => 'Promosi & Pemasaran',
                'sort_order' => 220,
            ],
            // [
            //     'code' => 'discount_vouchers',
            //     'name' => 'Kupon & Voucher Diskon',
            //     'description' => 'Penerbitan kode kupon dan voucher diskon khusus untuk program kampanye pemasaran.',
            //     'module' => 'promo',
            //     'group' => 'promotions_and_marketing',
            //     'group_label' => 'Promosi & Pemasaran',
            //     'sort_order' => 230,
            // ],

            // Pelanggan & CRM
            [
                'code' => 'customer_management',
                'name' => 'Database Pelanggan',
                'description' => 'Pengelolaan direktori profil pelanggan, kontak, preferensi, dan histori transaksi belanja.',
                'module' => 'crm',
                'group' => 'customers_and_crm',
                'group_label' => 'Pelanggan & CRM',
                'sort_order' => 230,
            ],
            // [
            //     'code' => 'customer_loyalty',
            //     'name' => 'Program Loyalitas & Poin',
            //     'description' => 'Sistem akumulasi poin belanja, tingkatan membership pelanggan, dan penukaran reward loyalitas.',
            //     'module' => 'crm',
            //     'group' => 'customers_and_crm',
            //     'group_label' => 'Pelanggan & CRM',
            //     'sort_order' => 250,
            // ],

            // Laporan & Analitik
            [
                'code' => 'basic_reports',
                'name' => 'Laporan Penjualan Dasar',
                'description' => 'Ringkasan ringkas omzet harian, jumlah transaksi, dan rekapitulasi metode pembayaran dasar.',
                'module' => 'report',
                'group' => 'reports_and_analytics',
                'group_label' => 'Laporan & Analitik',
                'sort_order' => 240,
            ],
            [
                'code' => 'advanced_reports',
                'name' => 'Laporan & Analitik Lanjutan',
                'description' => 'Analisis mendalam performa bisnis, profitabilitas margin, tren penjualan, dan estimasi laba kotor.',
                'module' => 'report',
                'group' => 'reports_and_analytics',
                'group_label' => 'Laporan & Analitik',
                'sort_order' => 250,
            ],
            [
                'code' => 'sales_reports',
                'name' => 'Laporan Penjualan Detail',
                'description' => 'Rincian transaksi per periode, filter multi-cabang, grafik jam sibuk, dan performa kasir.',
                'module' => 'report',
                'group' => 'reports_and_analytics',
                'group_label' => 'Laporan & Analitik',
                'sort_order' => 260,
            ],
            [
                'code' => 'product_reports',
                'name' => 'Laporan Performa Produk',
                'description' => 'Laporan produk terlaris (top selling), kontribusi margin keuntungan produk, dan produk lambat terjual.',
                'module' => 'report',
                'group' => 'reports_and_analytics',
                'group_label' => 'Laporan & Analitik',
                'sort_order' => 270,
            ],
            [
                'code' => 'stock_reports',
                'name' => 'Laporan Stok & Valuasi Aset',
                'description' => 'Laporan sisa stok, valuasi nilai aset inventori menggunakan FIFO, dan peringatan stok menipis.',
                'module' => 'report',
                'group' => 'reports_and_analytics',
                'group_label' => 'Laporan & Analitik',
                'sort_order' => 280,
            ],
            [
                'code' => 'cashier_reports',
                'name' => 'Laporan Kasir & Arus Kas',
                'description' => 'Rekonsiliasi kas per kasir, tracking selisih fisik uang kas (over/short), dan rekap shift operasional.',
                'module' => 'report',
                'group' => 'reports_and_analytics',
                'group_label' => 'Laporan & Analitik',
                'sort_order' => 290,
            ],
            [
                'code' => 'promo_reports',
                'name' => 'Laporan Efektivitas Promo',
                'description' => 'Evaluasi efektivitas promosi, total diskon yang terserap, dan dampak program promo terhadap omzet.',
                'module' => 'report',
                'group' => 'reports_and_analytics',
                'group_label' => 'Laporan & Analitik',
                'sort_order' => 300,
            ],
            [
                'code' => 'customer_reports',
                'name' => 'Laporan Pelanggan',
                'description' => 'Analisis pertumbuhan pelanggan baru vs lama, frekuensi belanja, dan daftar pelanggan bernilai tertinggi.',
                'module' => 'report',
                'group' => 'reports_and_analytics',
                'group_label' => 'Laporan & Analitik',
                'sort_order' => 310,
            ],

            // Outlet & Operasional
            [
                'code' => 'multi_outlet',
                'name' => 'Multi Outlet & Cabang',
                'description' => 'Kemampuan mengelola banyak cabang usaha dalam satu akun terpusat dengan pergantian outlet cepat.',
                'module' => 'outlet',
                'group' => 'outlets_and_operations',
                'group_label' => 'Outlet & Operasional',
                'sort_order' => 320,
            ],
            [
                'code' => 'outlet_management',
                'name' => 'Manajemen Pengaturan Outlet',
                'description' => 'Konfigurasi alamat, profil cabang, printer lokal, dan parameter operasional outlet.',
                'module' => 'outlet',
                'group' => 'outlets_and_operations',
                'group_label' => 'Outlet & Operasional',
                'sort_order' => 330,
            ],
            [
                'code' => 'multi_device',
                'name' => 'Multi Perangkat POS',
                'description' => 'Menghubungkan banyak perangkat tablet dan kasir dalam satu outlet secara bersamaan.',
                'module' => 'outlet',
                'group' => 'outlets_and_operations',
                'group_label' => 'Outlet & Operasional',
                'sort_order' => 340,
            ],

            // Karyawan & Keamanan
            [
                'code' => 'employee_management',
                'name' => 'Manajemen Pegawai & Staf',
                'description' => 'Manajemen data staf/karyawan, kode PIN kasir cepat, dan penugasan karyawan ke cabang tertentu.',
                'module' => 'employee',
                'group' => 'employees_and_security',
                'group_label' => 'Karyawan & Hak Akses',
                'sort_order' => 350,
            ],
            [
                'code' => 'custom_role',
                'name' => 'Peran Kustom Karyawan',
                'description' => 'Pembuatan peran/role khusus sesuai kebutuhan jabatan internal bisnis.',
                'module' => 'employee',
                'group' => 'employees_and_security',
                'group_label' => 'Karyawan & Hak Akses',
                'sort_order' => 360,
            ],
            [
                'code' => 'audit_logs',
                'name' => 'Log Audit & Jejak Aktivitas',
                'description' => 'Pencatatan riwayat audit aktivitas user saat mengubah harga, menghapus data, dan tindakan sensitif.',
                'module' => 'employee',
                'group' => 'employees_and_security',
                'group_label' => 'Karyawan & Hak Akses',
                'sort_order' => 370,
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

        Feature::clearCache();
    }
}
