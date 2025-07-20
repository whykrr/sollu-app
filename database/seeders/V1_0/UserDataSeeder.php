<?php

namespace Database\Seeders\V1_0;

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

        $mart  = $merchant->where('email', 'sollu.mart@email.com')->first();
        $merch = $merchant->where('email', 'sollu.merch@email.com')->first();
        $pets  = $merchant->where('email', 'sollu.pershop@email.com')->first();

        $mart->users()->create([
            'name'              => $mart->owner_name,
            'email'             => $mart->email,
            'password'          => 'password',
            'phone'             => $mart->phone,
            'pin'               => '123456',
            'photo'             => null,
            'email_verified_at' => now(),
            'is_root_user'      => true,
        ])->assignRole('owner');

        $mart->users()->create([
            'name'              => 'Manager Outlet',
            'email'             => "manager.{$mart->email}",
            'phone'             => $mart->phone.'9',
            'password'          => 'password',
            'pin'               => '123456',
            'photo'             => null,
            'email_verified_at' => now(),
            'is_root_user'      => false,
        ])->assignRole('manager');

        $merch->users()->create([
            'name'              => $merch->owner_name,
            'email'             => $merch->email,
            'phone'             => $merch->phone,
            'password'          => 'password',
            'pin'               => '123456',
            'photo'             => null,
            'email_verified_at' => now(),
            'is_root_user'      => true,
        ])->assignRole('owner');

        $pets->users()->create([
            'name'              => $pets->owner_name,
            'email'             => $pets->email,
            'phone'             => $pets->phone,
            'password'          => 'password',
            'pin'               => '123456',
            'photo'             => null,
            'email_verified_at' => now(),
            'is_root_user'      => true,
        ])->assignRole('owner');

    }
}
