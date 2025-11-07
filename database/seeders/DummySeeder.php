<?php

namespace Database\Seeders;

use Database\Seeders\Dummy\MiniMarketSeeder;
use Database\Seeders\Dummy\OnlineShopSeeder;
use Database\Seeders\Dummy\RetailCategorySeeder;
use Illuminate\Database\Seeder;

class DummySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            MasterCategorySeeder::class,
        ]);

        // \App\Models\User::factory(10)->create();
    }
}
