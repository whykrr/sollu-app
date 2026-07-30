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
        Schema::create('stock_adjustment_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('stock_adjustment_id')->constrained('stock_adjustments')->cascadeOnDelete();
            $table->foreignUuid('inventory_item_id')->constrained('inventory_items')->cascadeOnDelete();
            
            $table->string('movement_type'); // adjustment or waste
            $table->decimal('qty_change', 15, 4); // positive or negative
            $table->decimal('unit_cost', 15, 4)->nullable(); // manual cost override when IN (+)
            
            $table->decimal('stock_before', 15, 4)->nullable();
            $table->decimal('stock_after', 15, 4)->nullable();
            
            $table->text('description');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_adjustment_items');
    }
};
