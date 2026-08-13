<?php

namespace Database\Seeders\Development;

use App\Models\BusinessType;
use Illuminate\Database\Seeder;

class DummyMinimarketSeeder extends Seeder
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
        $business = $type->businesses()->updateOrCreate(
            ['email' => 'sollu.mart@email.com'],
            [
                'business_type_id' => '1',
                'name' => 'Sollu Mart',
                'owner_name' => 'Wahyu Kristiawan',
                'email' => 'sollu.mart@email.com',
                'phone' => '082132538886',
                'status' => 'active',
                'trial_end_at' => now()->addDays(15),
            ]
        );

        /**
         * @var \App\Models\Outlet
         */
        $outlet = $business->outlets()->updateOrCreate(
            ['slug' => 'sm-sentral'],
            [
                'name' => 'SM Sentral',
                'address' => 'Jl. Ijen Boulevard No.17, Malang',
                'is_main_outlet' => true,
            ]
        );
        $outlet = $business->outlets()->updateOrCreate(
            ['slug' => 'sm-soekarno-hatta'],
            [
                'name' => 'SM Soekarno Hatta',
                'address' => 'Jl. Soekarno Hatta 20, Malang',
                'is_main_outlet' => false,
            ]
        );

        /**
         * @var \App\Models\User
         */
        $user = $business->users()->updateOrCreate(
            ['email' => 'sollu.mart@email.com'],
            [
                'name' => $business->owner_name,
                'email' => $business->email,
                'password' => 'password',
                'phone' => $business->phone,
                'pin' => '123456',
                'photo' => null,
                'is_root_user' => true,
            ]
        );
        $user->assignRole('owner');
        $user->outlets()->sync($business->outlets()->pluck('id')->toArray());
    }
}
