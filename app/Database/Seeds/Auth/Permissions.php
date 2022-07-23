<?php

namespace App\Database\Seeds\Auth;

use CodeIgniter\Database\Seeder;

class Permissions extends Seeder
{
    public function run()
    {
        // instance db
        $db = \Config\Database::connect();

        // truncate table
        $db->table('auth_permissions')->emptyTable();

        // reset auto increment
        $db->query('ALTER TABLE auth_permissions AUTO_INCREMENT = 1');


        $data = [
            [
                'name' => 'master-data',
                'description' => 'Master Data',
            ],
            [
                'name' => 'master-data-unit',
                'description' => 'Master Data > Satuan',
            ],
            [
                'name' => 'master-data-prodcategory',
                'description' => 'Master Data > Produk Kategori',
            ],
            [
                'name' => 'master-data-product',
                'description' => 'Master Data > Produk',
            ],
            [
                'name' => 'user',
                'description' => 'User',
            ],
            [
                'name' => 'inventory',
                'description' => 'Inventori',
            ],
            [
                'name' => 'inventory-stock',
                'description' => 'Inventori > Stok',
            ],
            [
                'name' => 'inventory-stock-purchase',
                'description' => 'Inventori > Pembelian Stok',
            ],
            [
                'name' => 'inventory-stock-opname',
                'description' => 'Inventori > Stok Opname',
            ],
            [
                'name' => 'inventory-stock-spoil',
                'description' => 'Inventori > Stok Spoil',
            ],
            [
                'name' => 'inventory-stock-out',
                'description' => 'Inventori > Stok Keluar',
            ],
            [
                'name' => 'cashier',
                'description' => 'Kasir',
            ],
            [
                'name' => 'sales',
                'description' => 'Penjualan',
            ],
            [
                'name' => 'finance',
                'description' => 'Keuangan',
            ],
            [
                'name' => 'finance-income',
                'description' => 'Inventori > Penerimaan',
            ],
            [
                'name' => 'finance-expense',
                'description' => 'Inventori > Pengeluaran',
            ],
            [
                'name' => 'debtcredit',
                'description' => 'Hutang Piutang',
            ],
            [
                'name' => 'debtcredit-debt',
                'description' => 'Hutang Piutang > Hutang',
            ],
            [
                'name' => 'debtcredit-credit',
                'description' => 'Hutang Piutang > Piutang',
            ],
            [
                'name' => 'report',
                'description' => 'Laporan',
            ],
            [
                'name' => 'report-product',
                'description' => 'Laporan > Produk',
            ],
            [
                'name' => 'report-receipt',
                'description' => 'Laporan > Penjualan',
            ],
            [
                'name' => 'setting',
                'description' => 'Pengaturan',
            ],
        ];

        // insert
        $db->table('auth_permissions')->insertBatch($data);
    }
}
