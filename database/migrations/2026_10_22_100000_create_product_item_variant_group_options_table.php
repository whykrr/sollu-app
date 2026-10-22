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
        Schema::create('product_item_variant_group_options', function (Blueprint $table) {
            $table->foreignUuid('product_item_id')
                ->constrained('product_items')
                ->cascadeOnDelete();

            $table->foreignUuid('variant_group_option_id')
                ->constrained('variant_group_options')
                ->cascadeOnDelete();

            $table->primary(['product_item_id', 'variant_group_option_id'], 'prod_item_vgo_primary');
            $table->index('variant_group_option_id', 'idx_product_item_vgo_option_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_item_variant_group_options');
    }
};
