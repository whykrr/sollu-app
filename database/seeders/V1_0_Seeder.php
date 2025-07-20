<?php

namespace Database\Seeders;

use Database\Seeders\V1_0\AttachSubscriptionSeeder;
use Database\Seeders\V1_0\MerchantSeeder;
use Database\Seeders\V1_0\MerchantTypeSeeder;
use Database\Seeders\V1_0\OutletSeeder;
use Database\Seeders\V1_0\RetailCategorySeeder;
use Database\Seeders\V1_0\RolePermissionSeeder;
use Database\Seeders\V1_0\SubscriptionPlanSeeder;
use Database\Seeders\V1_0\UserDataSeeder;
use Illuminate\Database\Seeder;

class V1_0_Seeder extends Seeder
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
            RetailCategorySeeder::class,
        ]);
    }
}
