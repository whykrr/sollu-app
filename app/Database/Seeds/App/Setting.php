<?php

namespace App\Database\Seeds\App;

use CodeIgniter\Database\Seeder;
use App\Models\SettingModel;

class Setting extends Seeder
{
    public function run()
    {
        // instace model
        $model = new SettingModel();

        //truncate table
        $model->truncate();

        $data = [
            [
                'id' => 'outlet',
                'name' => 'Nama Toko',
                'category' => 'basic',
                'value' => '-',
                'desc' => '',
            ],
            [
                'id' => 'outlet_address',
                'name' => 'Alamat Toko',
                'category' => 'basic',
                'value' => '-',
                'desc' => '',
            ],
            [
                'id' => 'outlet_phone',
                'name' => 'Telepon Toko',
                'category' => 'basic',
                'value' => '-',
                'desc' => '',
            ],
            [
                'id' => 'printer_name',
                'name' => 'Nama Printer',
                'category' => 'printer',
                'value' => '-',
                'desc' => '',
            ],
            [
                'id' => 'printer_max_length',
                'name' => 'Maksimal Panjang Baris',
                'category' => 'printer',
                'value' => '32',
                'desc' => '',
            ],
            [
                'id' => 'receipt_note',
                'name' => 'Catatan Pembayaran',
                'category' => 'printer',
                'value' => '-',
                'desc' => '',
            ]
        ];

        // insert data
        foreach ($data as $key => $value) {
            $model->insert($value);
        }
    }
}
