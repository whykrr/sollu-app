<?php

namespace App\Database\Seeds\App;

use CodeIgniter\Database\Seeder;

class FinancialAccount extends Seeder
{
    public function run()
    {
        // data init financial_accounts
        $data = [
            [
                'id' => '1-1',
                'name' => 'Pembelian',
                'description' => '-',
            ],
            [
                'id' => '1-2',
                'name' => 'Penjualan',
                'description' => '-',
            ],
            [
                'id' => '1-3',
                'name' => 'Retur Pembelian',
                'description' => '-',
            ],
            [
                'id' => '1-4',
                'name' => 'Retur Penjualan',
                'description' => '-',
            ],
            [
                'id' => '2-1',
                'name' => 'Persediaan',
                'description' => '-',
            ],
            [
                'id' => '2-2',
                'name' => 'Kelebihan Stok',
                'description' => '-',
            ],
            [
                'id' => '2-3',
                'name' => 'Stok Terbuang',
                'description' => '-',
            ],
            [
                'id' => '9-1',
                'name' => 'Beban Operasional',
                'description' => '-',
            ],
            [
                'id' => '9-2',
                'name' => 'Beban Lainnya',
                'description' => '-',
            ],
            [
                'id' => '9-3',
                'name' => 'Potongan Penjualan',
                'description' => '-',
            ],
            [
                'id' => '11-1',
                'name' => 'Piutang',
                'description' => '-',
            ],
            [
                'id' => '11-2',
                'name' => 'Pembayaran Piutang',
                'description' => '-',
            ],
        ];
        // instance model financial_accounts
        $model = new \App\Models\FinancialAccountModel();

        //truncate table
        $model->truncate();

        // insert data
        $model->insertBatch($data);
    }
}
