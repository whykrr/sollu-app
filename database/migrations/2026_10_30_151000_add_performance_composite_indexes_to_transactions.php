<?php

declare(strict_types=1);

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
        Schema::table('transactions', function (Blueprint $table) {
            $table->index(['outlet_id', 'transaction_date'], 'idx_tx_outlet_date');
            $table->index(['outlet_id', 'shift_id'], 'idx_tx_outlet_shift');
            $table->index(['outlet_id', 'status', 'created_at'], 'idx_tx_outlet_status_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropIndex('idx_tx_outlet_status_created');
            $table->dropIndex('idx_tx_outlet_shift');
            $table->dropIndex('idx_tx_outlet_date');
        });
    }
};
