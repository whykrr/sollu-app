<?php

namespace Database\Seeders\Dummy;

use App\Models\Merchant;
use App\Models\MerchantType;
use App\Models\Outlet;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class MiniMarketSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $plan = SubscriptionPlan::whereIsTrial(true)->first();
        $type = MerchantType::where('code', 'minimarket')->first();

        /**
         * @var Merchant
         */
        $merchant = $type->merchants()->create([
            'name'               => 'Sollu Mart',
            'owner_name'         => 'Wahyu Kristiawan',
            'email'              => 'sollu.mart@email.com',
            'phone'              => '082132538886',
            'already_free_trial' => true,
            'status'             => 'active',
            'expired_at'         => Carbon::now()->addDays($plan->duration),
        ]);

        $merchant->subscriptions()->create([
            'subscription_plans_id' => $plan->id,
            'start_date'            => Carbon::now(),
            'end_date'              => Carbon::now()->addDays($plan->duration),
        ]);

        /**
         * @var Outlet
         */
        $outlet = $merchant->outlets()->create([
            'name'           => 'Sollu Mart Pusat',
            'address'        => '',
            'is_main_outlet' => true,
        ]);

        /**
         * @var User
         */
        $user = $merchant->users()->create([
            'name'              => $merchant->owner_name,
            'email'             => $merchant->email,
            'password'          => 'password',
            'phone'             => $merchant->phone,
            'pin'               => '123456',
            'photo'             => null,
            'email_verified_at' => now(),
            'is_root_user'      => true,
        ], ['email']);

        $user->assignRole('owner');
        $user->outlets()->attach($outlet);
    }
}
