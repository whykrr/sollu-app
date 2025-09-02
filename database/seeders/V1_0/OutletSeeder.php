<?php

namespace Database\Seeders\V1_0;

use App\Models\Merchant;
use Illuminate\Database\Seeder;

class OutletSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $merchant = Merchant::all();

        $mart  = $merchant->where('email', 'sollu.mart@email.com')->first();
        $cloth = $merchant->where('email', 'sollu.cloth@email.com')->first();
        $pets  = $merchant->where('email', 'sollu.pershop@email.com')->first();

        $mart->outlets()->create([
            'name'           => 'Sollu Mart Pusat',
            'address'        => '',
            'is_main_outlet' => true,
        ]);

        $cloth->outlets()->createMany([
            [
                'name'           => 'Store Ijen',
                'address'        => '',
                'is_main_outlet' => true,
            ],
            [
                'name'           => 'Store Soehat',
                'address'        => '',
                'is_main_outlet' => false,
            ],
        ]);

        $pets->outlets()->create([
            'name'           => 'Sollu Petshop',
            'address'        => '',
            'is_main_outlet' => true,
        ]);
    }
}
