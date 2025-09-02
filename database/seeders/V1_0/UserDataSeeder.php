<?php

namespace Database\Seeders\V1_0;

use App\Models\Merchant;
use App\Models\User;
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
        $cloth = $merchant->where('email', 'sollu.cloth@email.com')->first();
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

        $cloth->users()->create([
            'name'              => $cloth->owner_name,
            'email'             => $cloth->email,
            'phone'             => $cloth->phone,
            'password'          => 'password',
            'pin'               => '123456',
            'photo'             => null,
            'email_verified_at' => now(),
            'is_root_user'      => true,
        ])->assignRole('owner');
        $cloth_man = User::factory(1)->create([
            'merchant_id' => $cloth->id,
        ]);
        $cloth_man->each(fn ($cloth_man) => $cloth_man->assignRole('manager'));
        $cloth_users = User::factory(100)->create([
            'merchant_id' => $cloth->id,
        ]);
        $cloth_users->each(fn ($cloth_users) => $cloth_users->assignRole('cashier'));

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
