<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outlet_payment_method', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('outlet_id');
            $table->uuid('payment_method_id');
            $table->boolean('is_enabled')->default(true);
            $table->timestamps();

            $table->foreign('outlet_id')->references('id')->on('outlets')->cascadeOnDelete();
            $table->foreign('payment_method_id')->references('id')->on('payment_methods')->cascadeOnDelete();
            $table->unique(['outlet_id', 'payment_method_id'], 'outlet_pm_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outlet_payment_method');
    }
};
