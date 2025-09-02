<?php

namespace Database\Seeders\V1_0;

use App\Models\MerchantType;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class MerchantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plan = SubscriptionPlan::whereIsTrial(true)->first();
        $type = MerchantType::all();

        $mart     = $type->where('code', 'minimarket')->first();
        $merchant = $mart->merchants()->create([
            'name'               => 'Sollu Mart',
            'owner_name'         => 'Wahyu Kristiawan',
            'email'              => 'sollu.mart@email.com',
            'phone'              => '082132538886',
            'already_free_trial' => true,
            'status'             => 'active',
            'expired_at'         => Carbon::now()->addDays($plan->duration),
            'settings'           => $mart->default_settings,
        ]);
        $merchant->plans()->create([
            'subscription_plans_id' => $plan->id,
            'start_date'            => Carbon::now(),
            'end_date'              => Carbon::now()->addDays($plan->duration),
        ]);

        $cloth    = $type->where('code', 'fashion_store')->first();
        $merchant = $cloth->merchants()->create([
            'name'               => 'Sollu Clothing Store',
            'owner_name'         => 'Wahyu Kristiawan',
            'email'              => 'sollu.cloth@email.com',
            'phone'              => '082132538887',
            'already_free_trial' => true,
            'status'             => 'active',
            'expired_at'         => Carbon::now()->addDays($plan->duration),
            'settings'           => $cloth->default_settings,
        ]);
        $merchant->plans()->create([
            'subscription_plans_id' => $plan->id,
            'start_date'            => Carbon::now(),
            'end_date'              => Carbon::now()->addDays($plan->duration),
        ]);

        $pets     = $type->where('code', 'petshop')->first();
        $merchant = $pets->merchants()->create([
            'name'               => 'Sollu Petshop',
            'owner_name'         => 'Wahyu Kristiawan',
            'email'              => 'sollu.pershop@email.com',
            'phone'              => '082132538888',
            'already_free_trial' => true,
            'status'             => 'active',
            'expired_at'         => Carbon::now()->addDays($plan->duration),
            'settings'           => $pets->default_settings,
        ]);
        $merchant->plans()->create([
            'subscription_plans_id' => $plan->id,
            'start_date'            => Carbon::now(),
            'end_date'              => Carbon::now()->addDays($plan->duration),
        ]);
    }
}
