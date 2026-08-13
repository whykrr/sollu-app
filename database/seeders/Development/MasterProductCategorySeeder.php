<?php

namespace Database\Seeders\Development;

use App\Models\Business;
use App\Models\Master\ProductCategory;
use Illuminate\Database\Seeder;

class MasterProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $business = Business::where('email', 'sollu.mart@email.com')->first();
        if (! $business) {
            return;
        }

        $categories = [
            ['name' => 'Makanan Ringan'],
            ['name' => 'Minuman Dingin'],
            ['name' => 'Kebutuhan Dapur'],
            ['name' => 'Perawatan Diri'],
            ['name' => 'Point Coffee'],
            ['name' => 'Ready to Eat'],
            ['name' => 'Jasa & PPOB'],
        ];

        foreach ($categories as $idx => $cat) {
            ProductCategory::updateOrCreate(
                [
                    'business_id' => $business->id,
                    'name' => $cat['name'],
                ],
                [
                    'sort_order' => $idx + 1,
                ]
            );
        }
    }
}
