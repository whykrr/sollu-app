<?php

namespace Database\Seeders\V1_0;

use App\Models\MerchantType;
use Illuminate\Database\Seeder;

class MerchantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $type = MerchantType::all();

        $mart = $type->where('code', 'minimarket')->first();
        $mart->merchants()->create([
            'name'               => 'Sollu Mart',
            'owner_name'         => 'Wahyu Kristiawan',
            'email'              => 'sollu.mart@email.com',
            'phone'              => '082132538886',
            'already_free_trial' => true,
            'settings'           => $mart->default_settings,
        ]);

        $merch = $type->where('code', 'fashion_store')->first();
        $merch->merchants()->create([
            'name'               => 'Sollu Merch Store',
            'owner_name'         => 'Wahyu Kristiawan',
            'email'              => 'sollu.merch@email.com',
            'phone'              => '082132538887',
            'already_free_trial' => true,
            'settings'           => $merch->default_settings,
        ]);

        $pets = $type->where('code', 'petshop')->first();
        $pets->merchants()->create([
            'name'               => 'Sollu Petshop',
            'owner_name'         => 'Wahyu Kristiawan',
            'email'              => 'sollu.pershop@email.com',
            'phone'              => '082132538888',
            'already_free_trial' => true,
            'settings'           => $pets->default_settings,
        ]);
    }
}
