<?php

declare(strict_types=1);

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
        Schema::table('products', function (Blueprint $table) {
            $table->index(['business_id', 'is_show', 'sellable'], 'products_business_catalog_idx');
            $table->index(['business_id', 'name'], 'products_business_name_idx');
            $table->index(['business_id', 'code'], 'products_business_code_idx');
        });

        Schema::table('product_items', function (Blueprint $table) {
            $table->index(['business_id', 'product_id'], 'product_items_business_product_idx');
            $table->index(['product_id', 'is_active', 'sellable'], 'product_items_catalog_idx');
        });

        Schema::table('product_prices', function (Blueprint $table) {
            $table->index(['product_id', 'outlet_id'], 'product_prices_product_outlet_idx');
            $table->index(['product_item_id', 'outlet_id'], 'product_prices_item_outlet_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('product_prices', function (Blueprint $table) {
            $table->dropIndex('product_prices_item_outlet_idx');
            $table->dropIndex('product_prices_product_outlet_idx');
        });

        Schema::table('product_items', function (Blueprint $table) {
            $table->dropIndex('product_items_catalog_idx');
            $table->dropIndex('product_items_business_product_idx');
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_business_code_idx');
            $table->dropIndex('products_business_name_idx');
            $table->dropIndex('products_business_catalog_idx');
        });
    }
};
