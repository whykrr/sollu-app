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
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('outlet_id');
            $table->string('opname_number');
            $table->string('status')->default('in_progress'); // in_progress, pending_approval, approved, rejected
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'opname_number']);
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('stock_opname_id');
            $table->uuid('inventory_item_id');
            $table->decimal('system_qty', 15, 4);
            $table->decimal('actual_qty', 15, 4);
            $table->decimal('difference_qty', 15, 4);

            $table->foreign('stock_opname_id')->references('id')->on('stock_opnames')->cascadeOnDelete();
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opnames');
    }
};
