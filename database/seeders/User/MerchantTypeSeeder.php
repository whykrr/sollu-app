<?php

namespace Database\Seeders\User;

use App\Models\MerchantType;
use Illuminate\Database\Seeder;

class MerchantTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        MerchantType::create([
            'name'     => 'Restoran',
            'code'     => 'fnb',
            'settings' => [
                'product' => true,
                'service' => false,
            ],
        ]);
        MerchantType::create([
            'name'     => 'Toko Retail',
            'code'     => 'retail',
            'settings' => [
                'product' => true,
                'service' => false,
            ],
        ]);
        MerchantType::create([
            'name'     => 'Barbershop',
            'code'     => 'service',
            'settings' => [
                'product' => true,
                'service' => false,
            ],
        ]);
    }
}
