<?php

namespace Database\Seeders;

use Database\Seeders\Production\CockpitUserSeeder;
use Database\Seeders\Production\FeatureSeeder;
use Database\Seeders\Production\RolePermissionSeeder;
use Database\Seeders\Production\SubscriptionPlanSeeder;
use Database\Seeders\Production\UomSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            FeatureSeeder::class,
            SubscriptionPlanSeeder::class,
            UomSeeder::class,
            CockpitUserSeeder::class,
        ]);
    }
}
