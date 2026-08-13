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
        Schema::create('promo_outlets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('promo_id');
            $table->uuid('outlet_id');
            $table->timestamps();

            $table->foreign('promo_id')->references('id')->on('promos')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('promo_outlets');
    }
};
