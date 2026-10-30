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
        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->string('transfer_mode', 20)->default('direct')->after('status');
            $table->timestamp('shipped_at')->nullable()->after('notes');
            $table->foreignUuid('shipped_by')->nullable()->after('shipped_at')->constrained('users')->nullOnDelete();
            $table->timestamp('received_at')->nullable()->after('shipped_by');
            $table->timestamp('voided_at')->nullable()->after('received_at');
            $table->foreignUuid('voided_by')->nullable()->after('voided_at')->constrained('users')->nullOnDelete();
            $table->text('void_reason')->nullable()->after('voided_by');
        });

        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->decimal('unit_cost', 15, 4)->nullable()->after('qty_received');
            $table->decimal('qty_difference', 15, 4)->default(0)->after('unit_cost');
            $table->string('difference_reason')->nullable()->after('qty_difference');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stock_transfer_items', function (Blueprint $table) {
            $table->dropColumn(['unit_cost', 'qty_difference', 'difference_reason']);
        });

        Schema::table('stock_transfers', function (Blueprint $table) {
            $table->dropForeign(['shipped_by']);
            $table->dropForeign(['voided_by']);
            $table->dropColumn([
                'transfer_mode',
                'shipped_at',
                'shipped_by',
                'received_at',
                'voided_at',
                'voided_by',
                'void_reason',
            ]);
        });
    }
};
