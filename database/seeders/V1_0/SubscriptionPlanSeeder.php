<?php

namespace Database\Seeders\V1_0;

use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;

class SubscriptionPlanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        SubscriptionPlan::create([
            'name'        => 'Free Trial',
            'description' => 'Subscription for free trial user',
            'price'       => 0,
            'status'      => 'active',
            'duration'    => 15,
            'is_trial'    => true,
        ]);
    }
}
