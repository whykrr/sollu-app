<?php

namespace App\Database\Seeds\App;

use CodeIgniter\Database\Seeder;
use App\Models\SettingModel;

class Setting extends Seeder
{
    public function run()
    {
        // INFO - Instance of SettingModel
        $model = new SettingModel();

        // TODO - Truncate table
        $model->truncate();

        // TODO - Set Data
        $data = [
            [
                'id' => 'outlet',
                'name' => 'Nama Toko',
                'category' => 'basic',
                'type' => 'text',
                'value' => '-',
                'desc' => '',
            ],
            [
                'id' => 'outlet_address',
                'name' => 'Alamat Toko',
                'category' => 'basic',
                'type' => 'text',
                'value' => '-',
                'desc' => '',
            ],
            [
                'id' => 'outlet_phone',
                'name' => 'Telepon Toko',
                'category' => 'basic',
                'type' => 'text',
                'value' => '-',
                'desc' => '',
            ],
            [
                'id' => 'cashier_with_log',
                'name' => 'Log Kasir',
                'category' => 'cashier',
                'type' => 'bool',
                'value' => '0',
                'desc' => '',
            ],
            [
                'id' => 'printer_name',
                'name' => 'Nama Printer',
                'category' => 'printer',
                'type' => 'text',
                'value' => '-',
                'desc' => '',
            ],
            [
                'id' => 'printer_max_length',
                'name' => 'Maksimal Panjang Baris',
                'category' => 'printer',
                'type' => 'text',
                'value' => '32',
                'desc' => '',
            ],
            [
                'id' => 'printer_cash_drawer',
                'name' => 'Cash Drawer',
                'category' => 'printer',
                'type' => 'boolean',
                'value' => '1',
                'desc' => '',
            ],
            [
                'id' => 'printer_cutter',
                'name' => 'Pemotong Kertas',
                'category' => 'printer',
                'type' => 'boolean',
                'value' => '1',
                'desc' => '',
            ],
            [
                'id' => 'receipt_note',
                'name' => 'Catatan Pembayaran',
                'category' => 'printer',
                'type' => 'text',
                'value' => '-',
                'desc' => '',
            ]
        ];

        // TODO - Insert data
        foreach ($data as $key => $value) {
            $model->insert($value);
        }
    }
}
