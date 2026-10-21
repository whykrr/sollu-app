<?php

declare(strict_types=1);

use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add quota and trial settings to subscription_plans
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->integer('max_outlets')->nullable()->after('price_per_outlet');
            $table->boolean('has_trial')->default(false)->after('is_public');
            $table->integer('trial_duration_days')->nullable()->after('has_trial');
        });

        // 2. Add is_trial flag to subscriptions
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->boolean('is_trial')->default(false)->after('billing_cycle');
            $table->index(['business_id', 'status', 'is_trial'], 'idx_subscriptions_biz_status_trial');
        });

        // 3. Add has_used_trial to businesses
        Schema::table('businesses', function (Blueprint $table) {
            $table->boolean('has_used_trial')->default(false)->after('status');
        });

        // 4. Update default plan configurations
        DB::table('subscription_plans')->where('code', 'micro')->update([
            'max_outlets' => 1,
            'has_trial' => true,
            'trial_duration_days' => null,
        ]);

        DB::table('subscription_plans')->where('code', 'basic')->update([
            'max_outlets' => null,
            'has_trial' => true,
            'trial_duration_days' => null,
        ]);

        DB::table('subscription_plans')->where('code', 'pro')->update([
            'max_outlets' => null,
            'has_trial' => false,
            'trial_duration_days' => null,
        ]);

        // 5. ETL Data Patch: Migrate existing trial merchants to micro plan
        $microPlan = DB::table('subscription_plans')->where('code', 'micro')->first();

        if ($microPlan) {
            // Find businesses with no existing subscriptions
            $businessesWithoutSub = DB::table('businesses')
                ->whereNotExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('subscriptions')
                        ->whereColumn('subscriptions.business_id', 'businesses.id');
                })
                ->get();

            $now = Carbon::now();

            foreach ($businessesWithoutSub as $business) {
                $isFuture = false;
                $expiredAt = $now;

                if (isset($business->trial_end_at) && $business->trial_end_at !== null) {
                    $trialEnd = Carbon::parse($business->trial_end_at);
                    $isFuture = $trialEnd->isFuture();
                    $expiredAt = $trialEnd;
                }

                $subscriptionId = (string) Str::uuid();
                $startedAt = isset($business->created_at) ? Carbon::parse($business->created_at) : $now;

                DB::table('subscriptions')->insert([
                    'id' => $subscriptionId,
                    'business_id' => $business->id,
                    'plan_id' => $microPlan->id,
                    'status' => $isFuture ? 'active' : 'canceled',
                    'billing_cycle' => 'monthly',
                    'is_trial' => true,
                    'started_at' => $startedAt,
                    'expired_at' => $expiredAt,
                    'canceled_at' => $isFuture ? null : $expiredAt,
                    'created_at' => $startedAt,
                    'updated_at' => $now,
                ]);

                // Attach main outlet to subscription_outlets
                $outlet = DB::table('outlets')
                    ->where('business_id', $business->id)
                    ->orderByDesc('is_main_outlet')
                    ->first();

                if ($outlet) {
                    DB::table('subscription_outlets')->insert([
                        'id' => (string) Str::uuid(),
                        'subscription_id' => $subscriptionId,
                        'outlet_id' => $outlet->id,
                        'activated_at' => $startedAt,
                        'deactivated_at' => $isFuture ? null : $expiredAt,
                        'created_at' => $startedAt,
                        'updated_at' => $now,
                    ]);
                }

                DB::table('businesses')->where('id', $business->id)->update([
                    'has_used_trial' => true,
                ]);
            }
        }

        // 6. Mark all businesses that already have subscriptions as having used trial
        DB::table('businesses')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('subscriptions')
                    ->whereColumn('subscriptions.business_id', 'businesses.id');
            })
            ->update([
                'has_used_trial' => true,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Remove subscriptions created during ETL if they are trial
        DB::table('subscription_outlets')
            ->whereIn('subscription_id', function ($query) {
                $query->select('id')
                    ->from('subscriptions')
                    ->where('is_trial', true);
            })
            ->delete();

        DB::table('subscriptions')->where('is_trial', true)->delete();

        // 2. Drop has_used_trial from businesses
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('has_used_trial');
        });

        // 3. Drop index and is_trial from subscriptions
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropIndex('idx_subscriptions_biz_status_trial');
            $table->dropColumn('is_trial');
        });

        // 4. Drop columns from subscription_plans
        Schema::table('subscription_plans', function (Blueprint $table) {
            $table->dropColumn(['max_outlets', 'has_trial', 'trial_duration_days']);
        });
    }
};
