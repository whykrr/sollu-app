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
            $table->decimal('total_paid', 15, 4)->default(0)->after('total');
            $table->decimal('balance_due', 15, 4)->default(0)->after('total_paid');
            $table->dateTime('transaction_date')->nullable()->after('transaction_number');

            $table->uuid('created_by')->nullable()->after('shift_id');
            $table->uuid('updated_by')->nullable()->after('created_by');

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);

            $table->dropColumn([
                'total_paid',
                'balance_due',
                'transaction_date',
                'created_by',
                'updated_by',
            ]);
        });
    }
};
