<?php

namespace Database\Seeders\User;

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

        $fnb    = $merchant->where('email', 'sollu.resto@email.com')->first();
        $retail = $merchant->where('email', 'sollu.store@email.com')->first();
        $barber = $merchant->where('email', 'sollu.barber@email.com')->first();

        $fnb->outlets()->create([
            'name'           => 'Restoran Sollu',
            'address'        => '',
            'status'         => 'active',
            'expired_at'     => '2025-07-15',
            'is_main_outlet' => true,
        ]);

        $retail->outlets()->createMany([
            [
                'name'           => 'Sollu Store HQ',
                'address'        => '',
                'status'         => 'active',
                'expired_at'     => '2025-07-15',
                'is_main_outlet' => true,
            ],
            [
                'name'           => 'Sollu Store Soehat',
                'address'        => '',
                'status'         => 'active',
                'expired_at'     => '2025-07-15',
                'is_main_outlet' => false,
            ],
        ]);

        $barber->outlets()->create([
            'name'           => 'Sollu Barbershop',
            'address'        => '',
            'status'         => 'active',
            'expired_at'     => '2025-07-15',
            'is_main_outlet' => true,
        ]);
    }
}
