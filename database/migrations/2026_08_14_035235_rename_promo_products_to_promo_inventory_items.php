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
        Schema::table('promo_products', function (Blueprint $table) {
            $table->dropForeign(['promo_id']);
            $table->dropForeign(['product_id']);
            $table->renameColumn('product_id', 'inventory_item_id');
        });

        Schema::rename('promo_products', 'promo_inventory_items');

        Schema::table('promo_inventory_items', function (Blueprint $table) {
            $table->foreign('promo_id')->references('id')->on('promos')->cascadeOnDelete();
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promo_inventory_items', function (Blueprint $table) {
            $table->dropForeign(['promo_id']);
            $table->dropForeign(['inventory_item_id']);
            $table->renameColumn('inventory_item_id', 'product_id');
        });

        Schema::rename('promo_inventory_items', 'promo_products');

        Schema::table('promo_products', function (Blueprint $table) {
            $table->foreign('promo_id')->references('id')->on('promos')->cascadeOnDelete();
            $table->foreign('product_id')->references('id')->on('products')->cascadeOnDelete();
        });
    }
};
