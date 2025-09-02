<?php

namespace Database\Seeders\V1_0;

use App\Models\Merchant;
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
        $plan      = SubscriptionPlan::whereIsTrial(true)->first();
        $merchants = Merchant::all();

        foreach ($merchants as $merchant) {
            $merchant->plans()->create([
                'subscription_plans_id' => $plan->id,
                'start_date'            => Carbon::now(),
                'end_date'              => Carbon::now()->addDays($plan->duration),
            ]);
        }
    }
}
