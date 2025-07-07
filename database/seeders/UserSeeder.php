<?php

namespace Database\Seeders;

use Database\Seeders\User\AttachSubscriptionSeeder;
use Database\Seeders\User\MerchantSeeder;
use Database\Seeders\User\MerchantTypeSeeder;
use Database\Seeders\User\OutletSeeder;
use Database\Seeders\User\RolePermissionSeeder;
use Database\Seeders\User\SubscriptionPlanSeeder;
use Database\Seeders\User\UserDataSeeder;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            MerchantTypeSeeder::class,
            MerchantSeeder::class,
            OutletSeeder::class,
            RolePermissionSeeder::class,
            UserDataSeeder::class,
            SubscriptionPlanSeeder::class,
            AttachSubscriptionSeeder::class,
        ]);
    }
}
