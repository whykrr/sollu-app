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
        Schema::create('stock_transfers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('from_outlet_id');
            $table->uuid('to_outlet_id');
            $table->string('transfer_number');
            $table->string('status')->default('pending'); // pending, approved, in_transit, completed, rejected
            $table->text('notes')->nullable();
            $table->uuid('requested_by')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->uuid('received_by')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'transfer_number']);
            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('from_outlet_id')->references('id')->on('outlets')->cascadeOnDelete();
            $table->foreign('to_outlet_id')->references('id')->on('outlets')->cascadeOnDelete();
            $table->foreign('requested_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('received_by')->references('id')->on('users')->onDelete('set null');
        });

        Schema::create('stock_transfer_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('stock_transfer_id');
            $table->uuid('inventory_item_id');
            $table->decimal('qty', 15, 4);
            $table->decimal('qty_received', 15, 4)->default(0);

            $table->foreign('stock_transfer_id')->references('id')->on('stock_transfers')->cascadeOnDelete();
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_transfer_items');
        Schema::dropIfExists('stock_transfers');
    }
};
