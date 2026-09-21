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
        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->uuid('goods_receipt_item_id')->nullable()->after('inventory_item_id');
            $table->foreign('goods_receipt_item_id')
                ->references('id')
                ->on('goods_receipt_items')
                ->nullOnDelete();
            $table->index(['purchase_return_id', 'goods_receipt_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_return_items', function (Blueprint $table) {
            $table->dropForeign(['goods_receipt_item_id']);
            $table->dropIndex(['purchase_return_id', 'goods_receipt_item_id']);
            $table->dropColumn('goods_receipt_item_id');
        });
    }
};
