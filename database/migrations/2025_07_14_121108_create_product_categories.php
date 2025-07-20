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
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id()->unsigned();
            $table->string('merchant_id', 36)->index()->nullable();
            $table->bigInteger('parent_id')->index()->nullable();
            $table->string('name', 100)->fulltext();
            $table->string('slug', 100)->index();
            $table->text('description')->nullable();
            $table->tinyInteger('level')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        schema::table('product_categories', function (Blueprint $table) {
            $table->foreign('merchant_id')->references('id')->on('merchants')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('product_categories')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};
