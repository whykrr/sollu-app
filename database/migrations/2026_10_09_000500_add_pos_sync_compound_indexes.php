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
        // 1. Tambah timestamps dan index pada outlet_product
        Schema::table('outlet_product', function (Blueprint $table) {
            $table->timestamps();
            $table->index(['outlet_id', 'is_enabled', 'updated_at'], 'idx_outlet_product_sync');
        });

        // 2. Tambah index compound updated_at pada products
        Schema::table('products', function (Blueprint $table) {
            $table->index(['business_id', 'updated_at'], 'idx_products_business_sync');
        });

        // 3. Tambah index compound updated_at pada product_items
        Schema::table('product_items', function (Blueprint $table) {
            $table->index(['business_id', 'updated_at'], 'idx_product_items_business_sync');
            $table->index(['product_id', 'updated_at'], 'idx_product_items_product_sync');
        });

        // 4. Tambah index compound updated_at pada product_prices
        Schema::table('product_prices', function (Blueprint $table) {
            $table->index(['outlet_id', 'updated_at'], 'idx_product_prices_sync');
        });

        // 5. Tambah index compound updated_at pada inventory_balances
        Schema::table('inventory_balances', function (Blueprint $table) {
            $table->index(['outlet_id', 'updated_at'], 'idx_inventory_balances_sync');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_balances', function (Blueprint $table) {
            $table->dropIndex('idx_inventory_balances_sync');
        });

        Schema::table('product_prices', function (Blueprint $table) {
            $table->dropIndex('idx_product_prices_sync');
        });

        Schema::table('product_items', function (Blueprint $table) {
            $table->dropIndex('idx_product_items_product_sync');
            $table->dropIndex('idx_product_items_business_sync');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('idx_products_business_sync');
        });

        Schema::table('outlet_product', function (Blueprint $table) {
            $table->dropIndex('idx_outlet_product_sync');
            $table->dropTimestamps();
        });
    }
};
