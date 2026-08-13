<?php

namespace Database\Seeders;

use Database\Seeders\Development\DummyMinimarketSeeder;
use Database\Seeders\Production\BusinessTypeSeeder;
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
            BusinessTypeSeeder::class,
            RolePermissionSeeder::class,
            SubscriptionPlanSeeder::class,
            UomSeeder::class,
        ]);
        if (! app()->environment('production')) {
            $this->call([
                DummyMinimarketSeeder::class,
                // \Database\Seeders\Development\MasterProductCategorySeeder::class,
                // \Database\Seeders\Development\MasterModifierSeeder::class,
                // \Database\Seeders\Development\InventoryDatabaseSeeder::class,
            ]);
        }
    }
}
