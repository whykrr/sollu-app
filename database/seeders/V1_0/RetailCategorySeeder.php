<?php

namespace Database\Seeders\V1_0;

use App\Models\Product\ProductCategory;
use Illuminate\Database\Seeder;

class RetailCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Makanan' => [
                'Snack'          => ['Keripik', 'Kue Kering'],
                'Makanan Instan' => ['Mie Instan', 'Nasi Instan'],
                'Makanan Segar'  => ['Sayur', 'Daging'],
            ],
            'Minuman' => [
                'Air Mineral'    => [],
                'Minuman Ringan' => ['Soda', 'Teh Botol'],
                'Susu & Yogurt'  => ['Susu UHT', 'Yogurt'],
            ],
            'Kebutuhan Rumah Tangga' => [
                'Kebersihan'         => ['Sabun Cuci', 'Pembersih Lantai'],
                'Alat Masak'         => [],
                'Perlengkapan Mandi' => [],
            ],
            'Kesehatan & Perawatan' => [
                'Obat Bebas'              => [],
                'Masker & Alat Kesehatan' => [],
                'Perawatan Tubuh'         => ['Sabun Mandi', 'Shampoo'],
            ],
            'Bayi & Anak' => [
                'Makanan Bayi'       => [],
                'Popok'              => [],
                'Perlengkapan Mandi' => [],
            ],
            'Peralatan Elektronik' => [
                'Baterai & Charger' => [],
                'Lampu & Bohlam'    => [],
                'Peralatan Dapur'   => [],
            ],
            'Alat Tulis & Kantor' => [
                'Kertas & Buku'       => [],
                'Alat Tulis'          => [],
                'Perlengkapan Kantor' => [],
            ],
        ];

        foreach ($categories as $rootName => $level2) {
            $root = ProductCategory::create(['name' => $rootName]);

            foreach ($level2 as $subName => $subSubs) {
                $children = $root->children()->create(['name' => $subName]);

                foreach ($subSubs as $childName) {
                    $children->children()->create(['name' => $childName]);
                }
            }
        }
    }
}
