<?php

namespace Database\Seeders;

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
                'code'        => 'micro',
                'name'        => 'Paket Mikro',
                'description' => 'langganan untuk usaha mikro',
                'price'       => 99000,
                'status'      => 'active',
                'duration'    => 30,
                'features'    => $features,
            ],
            [
                'code'        => 'basic',
                'name'        => 'Paket Basic',
                'description' => 'langganan untuk usaha menegah',
                'price'       => 199000,
                'status'      => 'active',
                'duration'    => 30,
                'features'    => $features,
            ],
            [
                'code'        => 'pro',
                'name'        => 'Paket Pro',
                'description' => 'langganan untuk usaha besar',
                'price'       => 349000,
                'status'      => 'active',
                'duration'    => 30,
                'features'    => $features,
            ],
            [
                'code'          => 'micro',
                'name'          => 'Paket Mikro',
                'description'   => 'langganan untuk usaha mikro',
                'price'         => 999000,
                'billing_cycle' => 'yearly',
                'status'        => 'active',
                'duration'      => 365,
                'features'      => $features,
            ],
            [
                'code'          => 'basic',
                'name'          => 'Paket Basic',
                'description'   => 'langganan untuk usaha menegah',
                'price'         => 1999000,
                'billing_cycle' => 'yearly',
                'status'        => 'active',
                'duration'      => 365,
                'features'      => $features,
            ],
            [
                'code'          => 'pro',
                'name'          => 'Paket Pro',
                'description'   => 'langganan untuk usaha besar',
                'price'         => 3499000,
                'billing_cycle' => 'yearly',
                'status'        => 'active',
                'duration'      => 365,
                'features'      => $features,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::create($plan);
        }
    }
}
