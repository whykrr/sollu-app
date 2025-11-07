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
        Schema::create('subscription_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamps();
            $table->uuid('merchant_id');
            $table->uuid('invoice_id');
            $table->decimal('amount', 10, 2);
            $table->string('payment_type', 20);
            $table->string('order_id')->unique();
            $table->string('transaction_id')->nullable()->index();
            $table->string('payment_method', 30)->nullable()->index();
            $table->string('status', 15)->index();
            $table->datetime('paid_at')->nullable();
            $table->json('json_request');
            $table->json('json_respond');
            $table->json('json_notification')->nullable();
        });

        Schema::table('subscription_payments', function (Blueprint $table) {
            $table->foreign('invoice_id')->references('id')->on('subscription_invoices')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_payments');
    }
};
