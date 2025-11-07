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
        Schema::create('subscription_invoice_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamps();
            $table->uuid('subscription_invoice_id');
            $table->uuid('outlet_id');
            $table->decimal('total', 10, 2);
        });

        Schema::table('subscription_invoice_items', function (Blueprint $table) {
            $table->foreign('subscription_invoice_id')->references('id')->on('subscription_invoices')->onDelete('cascade');
            $table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscription_invoice_items');
    }
};
