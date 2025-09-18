<?php

namespace Database\Seeders\Dummy;

use App\Models\MerchantType;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class OnlineShopSeeder extends Seeder
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
            'name'               => 'Sollu Clothing Store',
            'owner_name'         => 'Wahyu Kristiawan',
            'email'              => 'sollu.cloth@email.com',
            'phone'              => '082132538887',
            'already_free_trial' => true,
            'status'             => 'active',
            'expired_at'         => Carbon::now()->addDays($plan->duration),
        ], ['email']);

        $merchant->subscriptions()->create([
            'subscription_plans_id' => $plan->id,
            'start_date'            => Carbon::now(),
            'end_date'              => Carbon::now()->addDays($plan->duration),
        ]);

        /**
         * @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Outlet>
         */
        $outlets = $merchant->outlets()->createMany([
            [
                'name'           => 'Store Ijen (Pusat)',
                'address'        => '',
                'is_main_outlet' => true,
            ],
            [
                'name'           => 'Store Soekarno Hatta',
                'address'        => '',
                'is_main_outlet' => false,
            ],
            [
                'name'           => 'Store Pasar Besar',
                'address'        => '',
                'is_main_outlet' => false,
            ],
        ]);

        $users = [
            [
                'detail' => [
                    'name'              => $merchant->owner_name,
                    'email'             => $merchant->email,
                    'password'          => 'password',
                    'phone'             => $merchant->phone,
                    'pin'               => '123456',
                    'photo'             => null,
                    'email_verified_at' => now(),
                    'is_root_user'      => true,
                ],
                'role'    => 'owner',
                'outlets' => $outlets->pluck('id')->toArray(),
            ],
            [
                'detail' => [
                    'name'              => 'Manager Semua Outlet',
                    'email'             => "manager_all_{$merchant->email}",
                    'phone'             => $merchant->phone.'1',
                    'password'          => 'password',
                    'pin'               => '123456',
                    'photo'             => null,
                    'email_verified_at' => now(),
                    'is_root_user'      => false,
                ],
                'role'    => 'manager',
                'outlets' => $outlets->pluck('id')->toArray(),
            ],
            [
                'detail' => [
                    'name'              => 'Manager Soekarno Hatta',
                    'email'             => "manager_soekarno_{$merchant->email}",
                    'phone'             => $merchant->phone.'2',
                    'password'          => 'password',
                    'pin'               => '123456',
                    'photo'             => null,
                    'email_verified_at' => now(),
                    'is_root_user'      => false,
                ],
                'role'    => 'manager',
                'outlets' => $outlets[1],
            ],
            [
                'detail' => [
                    'name'              => 'Cashier Multi Outlet',
                    'email'             => "cashier_multi_{$merchant->email}",
                    'phone'             => $merchant->phone.'3',
                    'password'          => 'password',
                    'pin'               => '123456',
                    'photo'             => null,
                    'email_verified_at' => now(),
                    'is_root_user'      => false,
                ],
                'role'    => 'cashier',
                'outlets' => $outlets->take(2)->pluck('id')->toArray(),
            ],
            [
                'detail' => [
                    'name'              => 'Cashier Pasar Besar',
                    'email'             => "cashier_pasar_{$merchant->email}",
                    'phone'             => $merchant->phone.'4',
                    'password'          => 'password',
                    'pin'               => '123456',
                    'photo'             => null,
                    'email_verified_at' => now(),
                    'is_root_user'      => false,
                ],
                'role'    => 'cashier',
                'outlets' => $outlets[2],
            ],
        ];


        foreach ($users as $user) {
            /**
             * @var User
             */
            $model = $merchant->users()->create($user['detail']);

            $model->assignRole($user['role']);
            $model->outlets()->sync($user['outlets']);
        }

    }
}
