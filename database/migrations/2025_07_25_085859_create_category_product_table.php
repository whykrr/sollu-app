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
        Schema::create('product_category_product', function (Blueprint $table) {
            $table->bigInteger('product_category_id');
            $table->uuid('product_id');
        });

        Schema::table('product_category_product', function (Blueprint $table) {
            $table->primary(['product_id', 'product_category_id']);
            $table->foreign('product_category_id')->references('id')->on('product_categories')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_product');
    }
};
