<?php

namespace Database\Seeders\User;

use App\Models\Merchant;
use Illuminate\Database\Seeder;

class UserDataSeeder extends Seeder
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

        $fnb->users()->create([
            'name'              => $fnb->owner_name,
            'email'             => $fnb->email,
            'password'          => 'password',
            'pin'               => '123456',
            'photo'             => null,
            'email_verified_at' => now(),
            'is_root_user'      => true,
        ])->assignRole('owner');

        $retail->users()->create([
            'name'              => $retail->owner_name,
            'email'             => $retail->email,
            'password'          => 'password',
            'pin'               => '123456',
            'photo'             => null,
            'email_verified_at' => now(),
            'is_root_user'      => true,
        ])->assignRole('owner');

        $barber->users()->create([
            'name'              => $barber->owner_name,
            'email'             => $barber->email,
            'password'          => 'password',
            'pin'               => '123456',
            'photo'             => null,
            'email_verified_at' => now(),
            'is_root_user'      => true,
        ])->assignRole('owner');

    }
}
