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
        Schema::create('merchant_type_product_category', function (Blueprint $table) {
            $table->integer('merchant_type_id')->unsigned();
            $table->bigInteger('product_category_id')->unsigned();
        });

        Schema::table('merchant_type_product_category', function (Blueprint $table) {
            $table->foreign('merchant_type_id')->references('id')->on('merchant_types')->onDelete('restrict');
            $table->foreign('product_category_id')->references('id')->on('product_categories')->onDelete('cascade');

            $table->primary(['merchant_type_id', 'product_category_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_type_product_category');
    }
};
