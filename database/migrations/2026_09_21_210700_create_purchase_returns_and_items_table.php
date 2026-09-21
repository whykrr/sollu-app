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
        Schema::create('purchase_returns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('outlet_id');
            $table->uuid('purchase_order_id')->nullable();
            $table->uuid('supplier_id')->nullable();
            $table->string('return_number');
            $table->date('return_date');
            $table->string('reason')->nullable();
            $table->decimal('total_return_amount', 15, 2)->default(0);
            $table->string('status')->default('completed');
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->unique(['business_id', 'return_number']);
            $table->index('outlet_id');
            $table->index('purchase_order_id');
            $table->index('supplier_id');
            $table->index('status');
            $table->index('return_date');
            $table->index('created_at');

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->cascadeOnDelete();
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->nullOnDelete();
            $table->foreign('supplier_id')->references('id')->on('suppliers')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        Schema::create('purchase_return_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('purchase_return_id');
            $table->uuid('inventory_item_id');
            $table->uuid('uom_id')->nullable();
            $table->decimal('return_purchase_qty', 15, 4);
            $table->decimal('conversion_factor', 15, 4)->default(1);
            $table->decimal('return_inventory_qty', 15, 4);
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);

            $table->foreign('purchase_return_id')->references('id')->on('purchase_returns')->cascadeOnDelete();
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->cascadeOnDelete();
            $table->foreign('uom_id')->references('id')->on('uoms')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_return_items');
        Schema::dropIfExists('purchase_returns');
    }
};
