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
        Schema::table('inventory_balances', function (Blueprint $table) {
            $table->decimal('minimum_stock', 15, 4)->default(0)->after('current_stock');
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('UPDATE inventory_balances SET minimum_stock = inventory_items.minimum_stock FROM inventory_items WHERE inventory_balances.inventory_item_id = inventory_items.id');
        } else {
            DB::statement('UPDATE inventory_balances SET minimum_stock = (SELECT COALESCE(minimum_stock, 0) FROM inventory_items WHERE inventory_items.id = inventory_balances.inventory_item_id)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_balances', function (Blueprint $table) {
            $table->dropColumn('minimum_stock');
        });
    }
};
