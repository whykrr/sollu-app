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
        $plans = [
            [
                'code'        => 'trial',
                'name'        => 'Uji Coba Gratis',
                'description' => 'langganan untuk trial',
                'price'       => 0,
                'status'      => 'active',
                'duration'    => 15,
                'is_trial'    => true,
            ],
            [
                'code'        => 'micro',
                'name'        => 'Paket Mikro',
                'description' => 'langganan untuk usaha mikro',
                'price'       => 99000,
                'status'      => 'active',
                'duration'    => 30,
                'is_trial'    => false,
            ],
            [
                'code'        => 'basic',
                'name'        => 'Paket Basic',
                'description' => 'langganan untuk usaha menegah',
                'price'       => 199000,
                'status'      => 'active',
                'duration'    => 30,
                'is_trial'    => false,
            ],
            [
                'code'        => 'pro',
                'name'        => 'Paket Pro',
                'description' => 'langganan untuk usaha besar',
                'price'       => 349000,
                'status'      => 'active',
                'duration'    => 30,
                'is_trial'    => false,
            ],
            [
                'code'          => 'micro',
                'name'          => 'Paket Mikro',
                'description'   => 'langganan untuk usaha mikro',
                'price'         => 999000,
                'billing_cycle' => 'yearly',
                'status'        => 'active',
                'duration'      => 365,
                'is_trial'      => false,
            ],
            [
                'code'          => 'basic',
                'name'          => 'Paket Basic',
                'description'   => 'langganan untuk usaha menegah',
                'price'         => 1999000,
                'billing_cycle' => 'yearly',
                'status'        => 'active',
                'duration'      => 365,
                'is_trial'      => false,
            ],
            [
                'code'          => 'pro',
                'name'          => 'Paket Pro',
                'description'   => 'langganan untuk usaha besar',
                'price'         => 3499000,
                'billing_cycle' => 'yearly',
                'status'        => 'active',
                'duration'      => 365,
                'is_trial'      => false,
            ],
        ];

        foreach ($plans as $plan) {
            SubscriptionPlan::create($plan);
        }
    }
}
