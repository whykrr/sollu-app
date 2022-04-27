<?php

namespace App\Database\Seeds\App;

use CodeIgniter\Database\Seeder;
use App\Models\SettingModel;

class SettingSimple extends Seeder
{
    public function run()
    {
        // instace model
        $model = new SettingModel();

        //truncate table
        $model->truncate();

        $data = [
            [
                'key' => 'type_app',
                'category' => 'hidden',
                'value' => 'complex'
            ],
            [
                'key' => 'outlet',
                'category' => 'basic',
                'value' => 'Gudang',
                'desc' => '',
            ],
            [
                'key' => 'outlet_address',
                'category' => 'basic',
                'value' => '-',
                'desc' => '',
            ],
            [
                'key' => 'outlet_phone',
                'category' => 'basic',
                'value' => '-',
                'desc' => '',
            ],
            [
                'key' => 'printer_name',
                'category' => 'basic',
                'value' => '-',
                'desc' => '',
            ],
            [
                'key' => 'printer_paper_size',
                'category' => 'basic',
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
