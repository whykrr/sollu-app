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
        $features = [
            [
                'code' => 'product_modifiers',
                'name' => 'Opsi Tambahan (Modifier / Add-on)',
                'description' => 'Pengaturan opsi tambahan produk seperti topping, level gula, ekstra shot dengan tambahan harga.',
                'module' => 'product',
                'group' => 'products_and_menu',
                'group_label' => 'Produk & Menu',
                'sort_order' => 100,
                'is_active' => true,
            ],
            [
                'code' => 'recipe_management',
                'name' => 'Manajemen Resep & HPP',
                'description' => 'Perhitungan otomatis Harga Pokok Penjualan (HPP) berbasis komposisi bahan baku dan versi resep.',
                'module' => 'product',
                'group' => 'products_and_menu',
                'group_label' => 'Produk & Menu',
                'sort_order' => 120,
                'is_active' => true,
            ],
            [
                'code' => 'raw_materials',
                'name' => 'Bahan Baku (Raw Materials)',
                'description' => 'Pengelolaan inventori khusus bahan baku dapur dan bahan setengah jadi yang tidak dijual langsung.',
                'module' => 'inventory',
                'group' => 'inventory_and_supply_chain',
                'group_label' => 'Inventori & Rantai Pasok',
                'sort_order' => 140,
                'is_active' => true,
            ],
        ];

        $proPlan = DB::table('subscription_plans')->where('code', 'pro')->first();

        foreach ($features as $f) {
            $featureId = DB::table('features')->where('code', $f['code'])->value('id');

            if (! $featureId) {
                $featureId = (string) Str::uuid();
                DB::table('features')->insert(array_merge($f, [
                    'id' => $featureId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            } else {
                DB::table('features')->where('id', $featureId)->update([
                    'is_active' => true,
                    'updated_at' => $now,
                ]);
            }

            if ($proPlan) {
                $exists = DB::table('plan_features')
                    ->where('plan_id', $proPlan->id)
                    ->where('feature_id', $featureId)
                    ->exists();

                if (! $exists) {
                    DB::table('plan_features')->insert([
                        'plan_id' => $proPlan->id,
                        'feature_id' => $featureId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
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
        $featureCodes = ['product_modifiers', 'recipe_management', 'raw_materials'];
        $featureIds = DB::table('features')->whereIn('code', $featureCodes)->pluck('id');

        DB::table('plan_features')->whereIn('feature_id', $featureIds)->delete();
        DB::table('features')->whereIn('code', $featureCodes)->delete();

        Feature::clearCache();
        SubscriptionPlan::clearCache();
    }
};
