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
        $tables = [
            'stock_adjustments',
            'goods_receipts',
            'purchase_orders',
            'purchase_returns',
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->timestamp('voided_at')->nullable();
                $table->foreignUuid('voided_by')->nullable()->constrained('users')->nullOnDelete();
                $table->text('void_reason')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $tables = [
            'stock_adjustments',
            'goods_receipts',
            'purchase_orders',
            'purchase_returns',
        ];

        foreach ($tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign(['voided_by']);
                $table->dropColumn(['voided_at', 'voided_by', 'void_reason']);
            });
        }
    }
};
