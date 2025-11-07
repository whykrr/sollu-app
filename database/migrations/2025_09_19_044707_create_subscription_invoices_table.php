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
        Schema::create('subscription_invoices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamps();
            $table->string('code')->unique();
            $table->uuid('merchant_id');
            $table->integer('subscription_plan_id');
            $table->text('note')->nullable();
            $table->decimal('subtotal', 10, 2);
            $table->decimal('add_ons', 10, 2);
            $table->decimal('tax', 10, 2);
            $table->decimal('discount', 10, 2);
            $table->decimal('total', 10, 2);
            $table->datetime('due_date');
            $table->string('status', 15);
            $table->date('period_end');
        });

        Schema::table('subscription_invoices', function (Blueprint $table) {
            $table->foreign('merchant_id')->references('id')->on('merchants')->onDelete('cascade');
            $table->foreign('subscription_plan_id')->references('id')->on('subscription_plans')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_invoices');
    }
};
