<?php

namespace Database\Seeders;

use App\Models\BusinessType;
use Illuminate\Database\Seeder;

class RegistrationMinimarketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /**
         * @var BusinessType
         */
        $type = BusinessType::where('code', 'minimarket')->first();

        /**
         * @var \App\Models\Business
         */
        $business = $type->businesses()->create([
            'business_type_id' => '1',
            'name'             => 'Sollu Mart',
            'owner_name'       => 'Wahyu Kristiawan',
            'email'            => 'sollu.mart@email.com',
            'phone'            => '082132538886',
            'status'           => 'trial',
            'trial_end_at'     => now()->addDays(15),
        ]);

        /**
         * @var \App\Models\Outlet
         */
        $outlet = $business->outlets()->create([
            'name'           => 'Sollu Mart Sentral',
            'address'        => '',
            'is_main_outlet' => true,
        ]);

        /**
         * @var \App\Models\User
         */
        $user = $business->users()->create([
            'name'     => $business->owner_name,
            'email'    => $business->email,
            'password' => 'password',
            'phone'    => $business->phone,
            'pin'      => '123456',
            'photo'    => null,
        ]);

        $user->assignRole('owner');
        $user->outlets()->attach($outlet);
    }
}
