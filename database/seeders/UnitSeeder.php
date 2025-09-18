<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            // General
            ['name' => 'Buah', 'symbol' => 'pcs', 'description' => 'Satuan untuk item tunggal'],
            ['name' => 'Bungkus', 'symbol' => 'bks', 'description' => 'Satuan kemasan dalam bungkus'],
            ['name' => 'Kotak', 'symbol' => 'box', 'description' => 'Satuan kemasan dalam kotak'],
            ['name' => 'Lusin', 'symbol' => 'lusin', 'description' => '1 lusin berisi 12 item'],
            ['name' => 'Karton', 'symbol' => 'ktn', 'description' => 'Satuan kemasan besar berupa karton'],
            ['name' => 'Botol', 'symbol' => 'btl', 'description' => 'Satuan kemasan cair dalam botol'],
            ['name' => 'Kaleng', 'symbol' => 'klg', 'description' => 'Satuan kemasan dalam kaleng'],
            ['name' => 'Sachet', 'symbol' => 'sach', 'description' => 'Satuan kemasan kecil (sachet)'],
            ['name' => 'Set', 'symbol' => 'set', 'description' => 'Sekumpulan item dijual sebagai satu set'],
            ['name' => 'Pasang', 'symbol' => 'psg', 'description' => 'Satuan untuk barang berpasangan (contoh: sepatu)'],

            // Weight
            ['name' => 'Gram', 'symbol' => 'g', 'description' => 'Satuan berat kecil'],
            ['name' => 'Kilogram', 'symbol' => 'kg', 'description' => 'Satuan berat umum (1000 gram)'],
            ['name' => 'Ons', 'symbol' => 'ons', 'description' => 'Satuan berat 100 gram (umum di Indonesia)'],

            // Volume
            ['name' => 'Mililiter', 'symbol' => 'ml', 'description' => 'Satuan volume kecil untuk cairan'],
            ['name' => 'Liter', 'symbol' => 'l', 'description' => 'Satuan volume umum untuk cairan'],
            ['name' => 'Gelas', 'symbol' => 'gelas', 'description' => 'Satuan saji minuman (gelas)'],
            ['name' => 'Cangkir', 'symbol' => 'cang', 'description' => 'Satuan saji minuman (cangkir)'],
            ['name' => 'Teko', 'symbol' => 'teko', 'description' => 'Satuan saji minuman dalam teko'],

            // Width / Size
            ['name' => 'Sentimeter', 'symbol' => 'cm', 'description' => 'Satuan panjang kecil'],
            ['name' => 'Meter', 'symbol' => 'm', 'description' => 'Satuan panjang umum'],
            ['name' => 'Inci', 'symbol' => 'in', 'description' => 'Satuan panjang (inch)'],

            // Time / Services
            ['name' => 'Detik', 'symbol' => 'dtk', 'description' => 'Satuan waktu detik'],
            ['name' => 'Menit', 'symbol' => 'mnt', 'description' => 'Satuan waktu menit'],
            ['name' => 'Jam', 'symbol' => 'jam', 'description' => 'Satuan waktu jam'],
            ['name' => 'Hari', 'symbol' => 'hari', 'description' => 'Satuan waktu harian'],
            ['name' => 'Bulan', 'symbol' => 'bln', 'description' => 'Satuan waktu bulanan'],
            ['name' => 'Sesi', 'symbol' => 'sesi', 'description' => 'Satuan layanan per sesi'],
            ['name' => 'Perjalanan', 'symbol' => 'trip',  'description' => 'Satuan untuk jasa perjalanan/trip'],
        ];

        foreach ($units as $unit) {
            Unit::create($unit);
        }

    }
}
