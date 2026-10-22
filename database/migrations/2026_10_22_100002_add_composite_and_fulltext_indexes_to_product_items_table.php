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
        Schema::table('product_items', function (Blueprint $table) {
            $table->index(['business_id', 'sku'], 'idx_product_items_business_sku');
            $table->index(['business_id', 'barcode'], 'idx_product_items_business_barcode');
            $table->index('product_id', 'idx_product_items_product_id');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement("
                CREATE INDEX idx_product_items_omni_search ON product_items USING gin (
                    to_tsvector('simple', coalesce(name, '') || ' ' || coalesce(variant_combination, '') || ' ' || coalesce(sku, '') || ' ' || coalesce(barcode, ''))
                )
            ");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS idx_product_items_omni_search');
        }

        Schema::table('product_items', function (Blueprint $table) {
            $table->dropIndex('idx_product_items_product_id');
            $table->dropIndex('idx_product_items_business_barcode');
            $table->dropIndex('idx_product_items_business_sku');
        });
    }
};
