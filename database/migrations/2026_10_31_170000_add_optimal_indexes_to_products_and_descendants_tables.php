<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->index(['business_id', 'product_category_id'], 'idx_products_business_category');
            $table->index(['business_id', 'deleted_at', 'created_at'], 'idx_products_business_deleted_created');
        });

        Schema::table('product_items', function (Blueprint $table) {
            $table->index(['product_id', 'item_type'], 'idx_product_items_product_type');
            $table->index(['business_id', 'item_type'], 'idx_product_items_business_type');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("CREATE UNIQUE INDEX idx_product_items_business_sku_unique ON product_items (business_id, sku) WHERE deleted_at IS NULL AND sku IS NOT NULL AND sku != ''");
            DB::statement("CREATE UNIQUE INDEX idx_product_items_business_barcode_unique ON product_items (business_id, barcode) WHERE deleted_at IS NULL AND barcode IS NOT NULL AND barcode != ''");
        }

        Schema::table('product_prices', function (Blueprint $table) {
            $table->index(['product_id', 'outlet_id', 'product_item_id'], 'idx_product_prices_lookup');
        });

        Schema::table('product_recipe_items', function (Blueprint $table) {
            $table->index(['recipe_version_id', 'product_item_id'], 'idx_recipe_version_product_item');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_recipe_items', function (Blueprint $table) {
            $table->dropIndex('idx_recipe_version_product_item');
        });

        Schema::table('product_prices', function (Blueprint $table) {
            $table->dropIndex('idx_product_prices_lookup');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('DROP INDEX IF EXISTS idx_product_items_business_barcode_unique');
            DB::statement('DROP INDEX IF EXISTS idx_product_items_business_sku_unique');
        }

        Schema::table('product_items', function (Blueprint $table) {
            $table->dropIndex('idx_product_items_business_type');
            $table->dropIndex('idx_product_items_product_type');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_business_deleted_created');
            $table->dropIndex('idx_products_business_category');
        });
    }
};
