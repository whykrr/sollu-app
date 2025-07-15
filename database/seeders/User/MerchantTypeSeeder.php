<?php

namespace Database\Seeders\User;

use App\Models\MerchantType;
use App\Settings\Settings;
use Illuminate\Database\Seeder;

class MerchantTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $merchantTypes = new MerchantType();
        $merchantTypes->create([
            'name' => 'Minimarket / Toko Konvensional',
            'code' => 'minimarket',
            'default_settings' => new Settings([
                'product' => ['variant' => false],
                'stock' => [
                    'mode' => 'LIFO',
                    'strict' => false,
                    'batch_tracking' => true,
                    'allow_batch_override' => false,
                    'conversion' => true,
                    'conversion_validate' => true,
                ],
            ])
        ]);

        $merchantTypes->create([
            'name' => 'Toko Pakaian',
            'code' => 'fashion_store',
            'default_settings' => new Settings([
                'product' => ['variant' => false],
                'stock' => [
                    'mode' => 'LIFO',
                    'strict' => false,
                    'batch_tracking' => true,
                    'allow_batch_override' => false,
                ],
            ])
        ]);

        $merchantTypes->create([
            'name' => 'Petshop',
            'code' => 'petshop',
            'default_settings' => new Settings([
                'product' => ['variant' => false],
                'stock' => [
                    'mode' => 'LIFO',
                    'strict' => false,
                    'batch_tracking' => true,
                    'allow_batch_override' => false,
                    'conversion' => true,
                    'conversion_validate' => true,
                ],
            ])
        ]);

    }
}
