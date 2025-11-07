<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class MasterCategorySeeder extends Seeder
{
    public function run(): void
    {
        Category::create([
            'name' => 'Makanan',
            'slug' => Str::slug('Makanan-master'),
            'merchant_id' => null, // Master data
            'parent_id' => null,
            'is_active' => true,
        ]);

        Category::create([
            'name' => 'Minuman',
            'slug' => Str::slug('Minuman-master'),
            'merchant_id' => null, // Master data
            'parent_id' => null,
            'is_active' => true,
        ]);
    }
}