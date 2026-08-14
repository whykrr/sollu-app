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
        Schema::table('transactions', function (Blueprint $table) {
            $table->renameColumn('receipt_number', 'transaction_number');
            $table->decimal('shipping_fee', 15, 2)->default(0)->after('tax_amount');

            $table->dropUnique(['offline_id']);
            $table->dropColumn(['is_offline', 'offline_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->renameColumn('transaction_number', 'receipt_number');
            $table->dropColumn('shipping_fee');
            $table->boolean('is_offline')->default(false);
            $table->uuid('offline_id')->nullable();
        });
    }
};
