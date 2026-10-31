<?php

declare(strict_types=1);

use App\Models\BusinessType;
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
        $featureId = DB::table('features')->where('code', 'cash_drawer')->value('id');

        if (! $featureId) {
            $featureId = (string) Str::uuid();
            DB::table('features')->insert([
                'id' => $featureId,
                'code' => 'cash_drawer',
                'name' => 'Manajemen Kas Laci (Cash Drawer)',
                'description' => 'Pencatatan kas masuk, kas keluar, modal awal, dan rekonsiliasi uang fisik di laci kasir.',
                'module' => 'pos',
                'group' => 'pos_and_transactions',
                'group_label' => 'Penjualan & Kasir',
                'sort_order' => 25,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            DB::table('features')->where('id', $featureId)->update([
                'is_active' => true,
                'updated_at' => $now,
            ]);
        }

        // Attach to all active subscription plans
        $activePlanIds = DB::table('subscription_plans')
            ->where('is_active', true)
            ->pluck('id');

        foreach ($activePlanIds as $planId) {
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

        // Synchronize business_types that support POS features
        $businessTypes = DB::table('business_types')->whereNotNull('features')->get();
        foreach ($businessTypes as $bt) {
            $features = json_decode((string) $bt->features, true);
            if (is_array($features) && in_array('pos', $features, true) && ! in_array('cash_drawer', $features, true)) {
                $features[] = 'cash_drawer';
                DB::table('business_types')->where('id', $bt->id)->update([
                    'features' => json_encode(array_values(array_unique($features))),
                ]);
            }
        }

        Feature::clearCache();
        SubscriptionPlan::clearCache();
        BusinessType::clearCache();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $feature = DB::table('features')->where('code', 'cash_drawer')->first();

        if ($feature) {
            DB::table('plan_features')->where('feature_id', $feature->id)->delete();
            DB::table('features')->where('id', $feature->id)->delete();
        }

        // Revert business_types features synchronization
        $businessTypes = DB::table('business_types')->whereNotNull('features')->get();
        foreach ($businessTypes as $bt) {
            $features = json_decode((string) $bt->features, true);
            if (is_array($features) && in_array('cash_drawer', $features, true)) {
                $features = array_values(array_diff($features, ['cash_drawer']));
                DB::table('business_types')->where('id', $bt->id)->update([
                    'features' => json_encode($features),
                ]);
            }
        }

        Feature::clearCache();
        SubscriptionPlan::clearCache();
        BusinessType::clearCache();
    }
};
