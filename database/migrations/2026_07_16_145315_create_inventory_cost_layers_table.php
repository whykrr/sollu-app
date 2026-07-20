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
        Schema::create('inventory_cost_layers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('inventory_item_id');
            $table->uuid('outlet_id');
            $table->decimal('purchase_price', 15, 2);
            $table->decimal('qty_purchased', 15, 4);
            $table->decimal('qty_remaining', 15, 4);
            $table->uuid('reference_id')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_cost_layers');
    }
};
