<?php

namespace Database\Seeders\V1_0;

use App\Models\Outlet;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AttachSubscriptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plan    = SubscriptionPlan::whereIsTrial(true)->first();
        $outlets = Outlet::all();

        foreach ($outlets as $outlet) {
            $outlet->subscription_plans()->create([
                'merchant_id'           => $outlet->merchant_id,
                'subscription_plans_id' => $plan->id,
                'start_date'            => Carbon::now(),
                'end_date'              => Carbon::now()->addDays($plan->duration),
                'status'                => 'active',
            ]);
        }
    }
}
