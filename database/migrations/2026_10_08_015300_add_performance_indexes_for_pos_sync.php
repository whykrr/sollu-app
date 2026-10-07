<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('variant_groups', function (Blueprint $table) {
            $table->index('product_id', 'variant_groups_product_id_idx');
        });

        Schema::table('variant_group_options', function (Blueprint $table) {
            $table->index('variant_group_id', 'variant_group_options_variant_group_id_idx');
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->index('product_id', 'product_images_product_id_idx');
        });

        Schema::table('modifier_options', function (Blueprint $table) {
            $table->index('modifier_group_id', 'modifier_options_modifier_group_id_idx');
        });

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->index('product_item_id', 'inventory_items_product_item_id_idx');
            $table->index('business_id', 'inventory_items_business_id_idx');
        });

        Schema::table('promotion_outlets', function (Blueprint $table) {
            $table->index('outlet_id', 'promotion_outlets_outlet_id_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotion_outlets', function (Blueprint $table) {
            $table->dropIndex('promotion_outlets_outlet_id_idx');
        });

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropIndex('inventory_items_product_item_id_idx');
            $table->dropIndex('inventory_items_business_id_idx');
        });

        Schema::table('modifier_options', function (Blueprint $table) {
            $table->dropIndex('modifier_options_modifier_group_id_idx');
        });

        Schema::table('product_images', function (Blueprint $table) {
            $table->dropIndex('product_images_product_id_idx');
        });

        Schema::table('variant_group_options', function (Blueprint $table) {
            $table->dropIndex('variant_group_options_variant_group_id_idx');
        });

        Schema::table('variant_groups', function (Blueprint $table) {
            $table->dropIndex('variant_groups_product_id_idx');
        });
    }
};
