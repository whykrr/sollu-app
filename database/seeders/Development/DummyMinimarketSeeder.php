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
            ['slug' => 'sollu-mart-sentral'],
            [
                'name' => 'Sollu Mart Sentral',
                'address' => '',
                'is_main_outlet' => true,
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
