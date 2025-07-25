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
        Schema::create('product_variation_option_values', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->uuid('product_variation_option_id');
            $table->bigInteger('product_variation_value_id');
            $table->timestamps();
        });

        Schema::table('product_variation_option_values', function (Blueprint $table) {
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('product_variation_option_id')->references('id')->on('product_variation_options')->onDelete('cascade');
            $table->foreign('product_variation_value_id')->references('id')->on('product_variation_values')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_variation_option_values');
    }
};
