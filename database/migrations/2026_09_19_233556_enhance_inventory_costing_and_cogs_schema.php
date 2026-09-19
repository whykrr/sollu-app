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
        // 1. Perkaya tabel inventory_balances dengan average_cost, last_cost, total_value
        Schema::table('inventory_balances', function (Blueprint $table) {
            $table->decimal('average_cost', 15, 4)->default(0)->after('current_stock');
            $table->decimal('last_cost', 15, 4)->default(0)->after('average_cost');
            $table->decimal('total_value', 15, 4)->default(0)->after('last_cost');

            $table->index(['business_id', 'outlet_id', 'inventory_item_id'], 'inv_bal_biz_out_item_idx');
        });

        // 2. Perkaya tabel inventory_cost_layers untuk multi-tenant isolation, polymorphic ref, dan presisi desimal 15,4
        Schema::table('inventory_cost_layers', function (Blueprint $table) {
            $table->uuid('business_id')->nullable()->after('id');
            $table->string('reference_type')->nullable()->after('reference_id');

            $table->index(['outlet_id', 'inventory_item_id', 'qty_remaining', 'created_at'], 'inv_cost_layers_fifo_idx');
            $table->index(['business_id', 'outlet_id'], 'inv_cost_layers_biz_out_idx');
        });

        // Backfill business_id di inventory_cost_layers dari tabel outlets jika ada data existing
        if (Schema::hasTable('outlets') && Schema::hasTable('inventory_cost_layers')) {
            DB::statement('
                UPDATE inventory_cost_layers
                SET business_id = outlets.business_id
                FROM outlets
                WHERE inventory_cost_layers.outlet_id = outlets.id
                AND inventory_cost_layers.business_id IS NULL
            ');
        }

        // 3. Perkaya tabel inventory_movements dengan unit_cost, total_cost, balance_value_after
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->decimal('unit_cost', 15, 4)->default(0)->after('cost');
            $table->decimal('total_cost', 15, 4)->default(0)->after('unit_cost');
            $table->decimal('balance_value_after', 15, 4)->default(0)->after('total_cost');

            $table->index(['outlet_id', 'inventory_item_id', 'created_at'], 'inv_mov_out_item_created_idx');
            $table->index(['business_id', 'movement_type', 'created_at'], 'inv_mov_biz_type_created_idx');
        });

        // 4. Perkaya tabel transaction_items dengan unit_cogs dan cogs_amount untuk snapshot COGS instan
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->decimal('unit_cogs', 15, 4)->default(0)->after('subtotal');
            $table->decimal('cogs_amount', 15, 4)->default(0)->after('unit_cogs');

            $table->index(['transaction_id', 'inventory_item_id'], 'trx_items_trx_inv_item_idx');
            $table->index(['transaction_id', 'product_id'], 'trx_items_trx_product_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_items', function (Blueprint $table) {
            $table->dropIndex('trx_items_trx_inv_item_idx');
            $table->dropIndex('trx_items_trx_product_idx');
            $table->dropColumn(['unit_cogs', 'cogs_amount']);
        });

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropIndex('inv_mov_out_item_created_idx');
            $table->dropIndex('inv_mov_biz_type_created_idx');
            $table->dropColumn(['unit_cost', 'total_cost', 'balance_value_after']);
        });

        Schema::table('inventory_cost_layers', function (Blueprint $table) {
            $table->dropIndex('inv_cost_layers_fifo_idx');
            $table->dropIndex('inv_cost_layers_biz_out_idx');
            $table->dropColumn(['business_id', 'reference_type']);
        });

        Schema::table('inventory_balances', function (Blueprint $table) {
            $table->dropIndex('inv_bal_biz_out_item_idx');
            $table->dropColumn(['average_cost', 'last_cost', 'total_value']);
        });
    }
};
