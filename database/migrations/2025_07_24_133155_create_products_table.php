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
        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('merchant_id');
            $table->integer('product_type_id');
            $table->string('name');
            $table->text('description');
            $table->decimal('base_price', 8, 2)->index();
            $table->bigInteger('product_unit_id');
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreign('merchant_id')->references('id')->on('merchants')->onDelete('cascade');
            $table->foreign('product_type_id')->references('id')->on('product_types')->onDelete('restrict');
            $table->foreign('product_unit_id')->references('id')->on('product_units')->onDelete('restrict');
            $table->fullText(['name', 'description']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
