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
        Schema::create('product_variation_values', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->bigInteger('product_variation_id');
            $table->string('name', 100);
            $table->boolean('is_active');
        });

        Schema::table('product_variation_values', function (Blueprint $table) {
            $table->foreign('product_variation_id')->references('id')->on('product_variations')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variation_values');
    }
};
