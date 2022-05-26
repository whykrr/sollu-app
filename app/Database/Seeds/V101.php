<?php

namespace App\Database\Seeds;

use App\Models\SettingModel;
use CodeIgniter\Database\Seeder;

class V101 extends Seeder
{
    public function run()
    {
        $model = new SettingModel();
        $addSetting = [
            [
                'id' => 'cashier_with_check_stock',
                'name' => 'Cek Stok',
                'category' => 'cashier',
                'type' => 'bool',
                'value' => '0',
                'desc' => '',
            ],
        ];

        // insert batch
        $model->insertBatch($addSetting);
    }
}
