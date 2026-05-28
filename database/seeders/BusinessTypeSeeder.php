<?php

namespace Database\Seeders;

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
        $businessTypes->create(['code' => 'minimarket', 'name' => 'Minimarket', 'is_visible' => true]);
        $businessTypes->create(['code' => 'grocery', 'name' => 'Grocery / Sembako', 'is_visible' => true]);
        $businessTypes->create(['code' => 'convenience_store', 'name' => 'Toserba', 'is_visible' => true]);
        $businessTypes->create(['code' => 'fashion_store', 'name' => 'Toko Fesyen', 'is_visible' => true]);
        $businessTypes->create(['code' => 'electronic_store', 'name' => 'Toko Elektronik', 'is_visible' => true]);
        $businessTypes->create(['code' => 'phone_store', 'name' => 'Toko HP & Gadget', 'is_visible' => true]);
        $businessTypes->create(['code' => 'computer_store', 'name' => 'Toko Komputer', 'is_visible' => true]);
        $businessTypes->create(['code' => 'book_store', 'name' => 'Toko Buku', 'is_visible' => true]);
        $businessTypes->create(['code' => 'pharmacy', 'name' => 'Apotek', 'is_visible' => true]);
        $businessTypes->create(['code' => 'cosmetic_store', 'name' => 'Toko Kosmetik', 'is_visible' => true]);
        $businessTypes->create(['code' => 'toy_store', 'name' => 'Toko Mainan', 'is_visible' => true]);
        $businessTypes->create(['code' => 'hardware_store', 'name' => 'Toko Bangunan / Hardware', 'is_visible' => true]);
        $businessTypes->create(['code' => 'furniture_store', 'name' => 'Furniture & Home Living', 'is_visible' => true]);
        $businessTypes->create(['code' => 'jewelry_store', 'name' => 'Toko Perhiasan', 'is_visible' => true]);
        $businessTypes->create(['code' => 'sports_store', 'name' => 'Toko Olahraga', 'is_visible' => true]);
        $businessTypes->create(['code' => 'vape_store', 'name' => 'Vape Store', 'is_visible' => true]);
        $businessTypes->create(['code' => 'automotive_store', 'name' => 'Toko Otomotif', 'is_visible' => true]);
        $businessTypes->create(['code' => 'florist', 'name' => 'Toko Bunga', 'is_visible' => true]);
        $businessTypes->create(['code' => 'souvenir_store', 'name' => 'Toko Souvenir', 'is_visible' => true]);
        $businessTypes->create(['code' => 'optical_store', 'name' => 'Optik', 'is_visible' => true]);
        $businessTypes->create(['code' => 'baby_store', 'name' => 'Toko Bayi', 'is_visible' => true]);
        $businessTypes->create(['code' => 'thrift_store', 'name' => 'Toko Thrift', 'is_visible' => true]);

        $businessTypes->create(['code' => 'coffee_shop', 'name' => 'Coffee Shop', 'is_visible' => true]);
        $businessTypes->create(['code' => 'restaurant', 'name' => 'Restoran', 'is_visible' => true]);
        $businessTypes->create(['code' => 'food_stall', 'name' => 'Kedai Makanan', 'is_visible' => true]);
        $businessTypes->create(['code' => 'bakery', 'name' => 'Bakery', 'is_visible' => true]);

        $businessTypes->create(['code' => 'barbershop', 'name' => 'Barbershop', 'is_visible' => true]);
        $businessTypes->create(['code' => 'salon', 'name' => 'Salon, Spa & Beauty', 'is_visible' => true]);
        $businessTypes->create(['code' => 'laundry', 'name' => 'Laundry', 'is_visible' => true]);
        $businessTypes->create(['code' => 'car_wash', 'name' => 'Car Wash', 'is_visible' => true]);
        $businessTypes->create(['code' => 'repair_shop', 'name' => 'Toko Reparasi', 'is_visible' => true]);
        $businessTypes->create(['code' => 'photo_studio', 'name' => 'Studio Foto', 'is_visible' => true]);
    }
}
