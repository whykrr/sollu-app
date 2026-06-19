<?php

namespace Database\Seeders\Production;

use App\Models\BusinessType;
use Illuminate\Database\Seeder;

class BusinessTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $businessTypes = new BusinessType();

        BusinessType::upsert(
            [
                ['code' => 'minimarket', 'name' => 'Minimarket', 'is_visible' => true],
                ['code' => 'grocery', 'name' => 'Grocery / Sembako', 'is_visible' => true],
                ['code' => 'convenience_store', 'name' => 'Toserba', 'is_visible' => true],
                ['code' => 'fashion_store', 'name' => 'Toko Fesyen', 'is_visible' => true],
                ['code' => 'coffee_shop', 'name' => 'Coffee Shop', 'is_visible' => true],
                ['code' => 'restaurant', 'name' => 'Restoran', 'is_visible' => true],
                ['code' => 'food_stall', 'name' => 'Kedai Makanan', 'is_visible' => true],
                ['code' => 'bakery', 'name' => 'Bakery', 'is_visible' => true],
                ['code' => 'laundry', 'name' => 'Laundry', 'is_visible' => false],
                ['code' => 'barbershop', 'name' => 'Barbershop', 'is_visible' => false],
                ['code' => 'salon', 'name' => 'Salon, Spa & Beauty', 'is_visible' => false],
                ['code' => 'repair_shop', 'name' => 'Bengkel', 'is_visible' => false],
                ['code' => 'pharmacy', 'name' => 'Apotek', 'is_visible' => false],
                ['code' => 'vape_store', 'name' => 'Vape Store', 'is_visible' => false],
                ['code' => 'thrift_store', 'name' => 'Toko Thrift', 'is_visible' => false],
            ],
            ['code'],
            ['name', 'is_visible']
        );

        /*
        $businessTypes->updateOrCreate(['code' => 'car_wash', 'name' => 'Car Wash', 'is_visible' => true]);
        $businessTypes->updateOrCreate(['code' => 'repair_shop', 'name' => 'Toko Reparasi', 'is_visible' => true]);
        $businessTypes->updateOrCreate(['code' => 'photo_studio', 'name' => 'Studio Foto', 'is_visible' => true]);
        $businessTypes->updateOrCreate(['code' => 'electronic_store', 'name' => 'Toko Elektronik', 'is_visible' => true]);
        $businessTypes->updateOrCreate(['code' => 'phone_store', 'name' => 'Toko HP & Gadget', 'is_visible' => true]);
        $businessTypes->updateOrCreate(['code' => 'computer_store', 'name' => 'Toko Komputer', 'is_visible' => true]);
        $businessTypes->updateOrCreate(['code' => 'book_store', 'name' => 'Toko Buku', 'is_visible' => true]);
        $businessTypes->updateOrCreate(['code' => 'toy_store', 'name' => 'Toko Mainan', 'is_visible' => true]);
        $businessTypes->updateOrCreate(['code' => 'cosmetic_store', 'name' => 'Toko Kosmetik', 'is_visible' => true]);
        $businessTypes->updateOrCreate(['code' => 'hardware_store', 'name' => 'Toko Bangunan / Hardware', 'is_visible' => true]);
        $businessTypes->updateOrCreate(['code' => 'furniture_store', 'name' => 'Furniture & Home Living', 'is_visible' => true]);
        $businessTypes->updateOrCreate(['code' => 'jewelry_store', 'name' => 'Toko Perhiasan', 'is_visible' => true]);
        $businessTypes->updateOrCreate(['code' => 'sports_store', 'name' => 'Toko Olahraga', 'is_visible' => true]);
        $businessTypes->updateOrCreate(['code' => 'automotive_store', 'name' => 'Toko Otomotif', 'is_visible' => true]);
        $businessTypes->updateOrCreate(['code' => 'florist', 'name' => 'Toko Bunga', 'is_visible' => true]);
        $businessTypes->updateOrCreate(['code' => 'souvenir_store', 'name' => 'Toko Souvenir', 'is_visible' => true]);
        $businessTypes->updateOrCreate(['code' => 'optical_store', 'name' => 'Optik', 'is_visible' => true]);
        $businessTypes->updateOrCreate(['code' => 'baby_store', 'name' => 'Toko Bayi', 'is_visible' => true]);
         */
    }
}
