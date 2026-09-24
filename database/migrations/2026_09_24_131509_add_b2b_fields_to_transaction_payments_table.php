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
        Schema::table('transaction_payments', function (Blueprint $table) {
            $table->dateTime('payment_date')->nullable()->after('change_amount');
            $table->text('notes')->nullable()->after('payment_reference');
            $table->uuid('created_by')->nullable()->after('notes');

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transaction_payments', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropColumn(['payment_date', 'notes', 'created_by']);
        });
    }
};
