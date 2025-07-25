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
        Schema::create('merchant_outlet_subscriptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('merchant_id');
            $table->uuid('outlet_id');
            $table->integer('subscription_plans_id')->unsigned();
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->string('status', 15)->default('payment')->comment('options: payment, active, cancelled');
            $table->timestamps();
        });

        Schema::table('merchant_outlet_subscriptions', function (Blueprint $table) {
            $table->foreign('merchant_id')->references('id')->on('merchants')->onDelete('cascade');
            $table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('cascade');
            $table->foreign('subscription_plans_id')->references('id')->on('subscription_plans')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_outlet_subscriptions');
    }
};
