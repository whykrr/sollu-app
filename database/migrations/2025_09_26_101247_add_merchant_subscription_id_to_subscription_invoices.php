<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscription_invoices', function (Blueprint $table) {
            $table->uuid('merchant_subscription_id')->nullable();

            $table->foreign('merchant_subscription_id')->references('id')->on('merchant_subscriptions')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscription_invoices', function (Blueprint $table) {
            $table->dropForeign(['merchant_subscription_id']);
            $table->dropColumn('merchant_subscription_id');
        });
    }
};
