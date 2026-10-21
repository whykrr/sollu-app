<?php

use App\Models\Feature;
use App\Models\SubscriptionPlan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();
        $featureId = DB::table('features')->where('code', 'feature_personalization')->value('id');

        if (! $featureId) {
            $featureId = (string) Str::uuid();
            DB::table('features')->insert([
                'id' => $featureId,
                'code' => 'feature_personalization',
                'name' => 'Personalisasi Fitur',
                'description' => 'Kemampuan untuk menyesuaikan, mengaktifkan, atau menyembunyikan modul sesuai alur kerja tokomu.',
                'module' => 'settings',
                'group' => 'outlets_and_operations',
                'group_label' => 'Outlet & Operasional',
                'sort_order' => 380,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Attach to Basic and Pro plans
        $plans = DB::table('subscription_plans')
            ->whereIn('code', ['basic', 'pro'])
            ->pluck('id');

        foreach ($plans as $planId) {
            $exists = DB::table('plan_features')
                ->where('plan_id', $planId)
                ->where('feature_id', $featureId)
                ->exists();

            if (! $exists) {
                DB::table('plan_features')->insert([
                    'plan_id' => $planId,
                    'feature_id' => $featureId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        Feature::clearCache();
        SubscriptionPlan::clearCache();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $feature = DB::table('features')->where('code', 'feature_personalization')->first();

        if ($feature) {
            DB::table('plan_features')->where('feature_id', $feature->id)->delete();
            DB::table('features')->where('id', $feature->id)->delete();
        }

        Feature::clearCache();
        SubscriptionPlan::clearCache();
    }
};
