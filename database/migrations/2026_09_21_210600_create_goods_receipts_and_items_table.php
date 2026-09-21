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
        Schema::create('goods_receipts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('outlet_id');
            $table->uuid('purchase_order_id')->nullable();
            $table->string('receipt_number');
            $table->string('delivery_order_number')->nullable();
            $table->timestamp('received_at');
            $table->string('status')->default('completed');
            $table->text('notes')->nullable();
            $table->uuid('received_by')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'receipt_number']);
            $table->index('outlet_id');
            $table->index('purchase_order_id');
            $table->index('status');
            $table->index('received_at');
            $table->index('created_at');

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->cascadeOnDelete();
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->nullOnDelete();
            $table->foreign('received_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('goods_receipt_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('goods_receipt_id');
            $table->uuid('purchase_order_item_id')->nullable();
            $table->uuid('inventory_item_id');
            $table->uuid('uom_id')->nullable();
            $table->decimal('received_purchase_qty', 15, 4);
            $table->decimal('conversion_factor', 15, 4)->default(1);
            $table->decimal('received_inventory_qty', 15, 4);
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->decimal('total_cost', 15, 4)->default(0);

            $table->foreign('goods_receipt_id')->references('id')->on('goods_receipts')->cascadeOnDelete();
            $table->foreign('purchase_order_item_id')->references('id')->on('purchase_order_items')->nullOnDelete();
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->cascadeOnDelete();
            $table->foreign('uom_id')->references('id')->on('uoms')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receipt_items');
        Schema::dropIfExists('goods_receipts');
    }
};
