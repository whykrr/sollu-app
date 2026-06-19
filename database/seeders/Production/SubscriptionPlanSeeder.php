<?php

namespace Database\Seeders\Production;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
            ['title' => 'Fitur Penjualan Lengkap', 'detail' => 'fitur lengkap dan lain lain'],
            ['title' => 'Laporan Penjualan', 'detail' => 'laporan penjualan lengkap'],
            ['title' => 'Manajemen Stok', 'detail' => 'manajemen stok mudah'],
            ['title' => 'Multi Outlet', 'detail' => 'kelola banyak outlet'],
            ['title' => 'Multi User', 'detail' => 'banyak user dalam 1 outlet'],
            ['title' => 'Support 24/7', 'detail' => 'bantuan kapan saja'],
        ];

        $plans = [
            [
                'code'                    => 'micro',
                'name'                    => 'Paket Mikro',
                'price_per_outlet'        => 59000,
                'max_outlet'              => 3,
                'yearly_discount_percent' => 20,
                'features'                => $features,
            ],
            [
                'code'                    => 'basic',
                'name'                    => 'Paket Basic',
                'price_per_outlet'        => 129000,
                'max_outlet'              => 10,
                'yearly_discount_percent' => 20,
                'features'                => $features,
            ],
            [
                'code'                    => 'pro',
                'name'                    => 'Paket Pro',
                'price_per_outlet'        => 299000,
                'max_outlet'              => 99,
                'yearly_discount_percent' => 20,
                'features'                => $features,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::updateOrCreate(
                [
                    'code' => $plan['code'],
                ],
                $plan
            );
        }
    }
}
