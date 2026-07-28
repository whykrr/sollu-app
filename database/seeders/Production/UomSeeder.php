<?php

namespace Database\Seeders\Production;

use App\Models\Uom;
use Illuminate\Database\Seeder;

class UomSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $units = [
            // Quantity
            ['code' => 'PCS', 'name' => 'Pieces', 'category' => 'quantity'],
            ['code' => 'BOX', 'name' => 'Box', 'category' => 'quantity'],
            ['code' => 'PACK', 'name' => 'Pack', 'category' => 'quantity'],
            ['code' => 'DZ', 'name' => 'Dozen', 'category' => 'quantity'],
            ['code' => 'CTN', 'name' => 'Carton', 'category' => 'quantity'],
            ['code' => 'SET', 'name' => 'Set', 'category' => 'quantity'],
            ['code' => 'PAIR', 'name' => 'Pair', 'category' => 'quantity'],

            // Weight
            ['code' => 'MG', 'name' => 'Milligram', 'category' => 'weight'],
            ['code' => 'G', 'name' => 'Gram', 'category' => 'weight'],
            ['code' => 'KG', 'name' => 'Kilogram', 'category' => 'weight'],
            ['code' => 'OZ', 'name' => 'Ounce', 'category' => 'weight'],
            ['code' => 'LB', 'name' => 'Pound', 'category' => 'weight'],

            // Volume
            ['code' => 'ML', 'name' => 'Milliliter', 'category' => 'volume'],
            ['code' => 'L', 'name' => 'Liter', 'category' => 'volume'],
            ['code' => 'GAL', 'name' => 'Gallon', 'category' => 'volume'],

            // Length
            ['code' => 'MM', 'name' => 'Millimeter', 'category' => 'length'],
            ['code' => 'CM', 'name' => 'Centimeter', 'category' => 'length'],
            ['code' => 'M', 'name' => 'Meter', 'category' => 'length'],
            ['code' => 'IN', 'name' => 'Inch', 'category' => 'length'],
            ['code' => 'FT', 'name' => 'Foot', 'category' => 'length'],

            // Time / Service
            ['code' => 'SEC', 'name' => 'Second', 'category' => 'time'],
            ['code' => 'MIN', 'name' => 'Minute', 'category' => 'time'],
            ['code' => 'HR', 'name' => 'Hour', 'category' => 'time'],
            ['code' => 'DAY', 'name' => 'Day', 'category' => 'time'],
            ['code' => 'MO', 'name' => 'Month', 'category' => 'time'],
            ['code' => 'SESS', 'name' => 'Session', 'category' => 'service'],
            ['code' => 'TRIP', 'name' => 'Trip', 'category' => 'service'],
        ];

        foreach ($units as $unit) {
            $code = ucwords(strtolower($unit['code']));
            $unit['code'] = $code;
            Uom::updateOrCreate(['code' => $code], $unit);
        }
    }
}
