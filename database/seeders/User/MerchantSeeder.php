<?php

namespace Database\Seeders\User;

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

        $fnb = $type->where('code', 'fnb')->first();
        $fnb->merchants()->create([
            'name'               => 'Restoran Sollu',
            'owner_name'         => 'Wahyu Kristiawan',
            'email'              => 'sollu.resto@email.com',
            'phone'              => '082132538886',
            'already_free_trial' => true,
            'settings'           => [
                'product' => true,
            ],
        ]);

        $retail = $type->where('code', 'retail')->first();
        $retail->merchants()->create([
            'name'               => 'Sollu Store',
            'owner_name'         => 'Wahyu Kristiawan',
            'email'              => 'sollu.store@email.com',
            'phone'              => '082132538887',
            'already_free_trial' => true,
            'settings'           => [
                'product' => true,
            ],
        ]);

        $retail = $type->where('code', 'service')->first();
        $retail->merchants()->create([
            'name'               => 'Sollu Barbershop',
            'owner_name'         => 'Wahyu Kristiawan',
            'email'              => 'sollu.barber@email.com',
            'phone'              => '082132538888',
            'already_free_trial' => true,
            'settings'           => [
                'product' => true,
            ],
        ]);
    }
}
