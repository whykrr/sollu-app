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
        Schema::create('merchants', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug', 200)->unique();
            $table->string('name', 200);
            $table->string('owner_name', 200);
            $table->string('email', 200)->unique();
            $table->string('phone', 20);
            $table->text('address')->nullable();
            $table->text('logo_url')->nullable();
            $table->boolean('already_free_trial')->default(false);
            $table->tinyInteger('merchant_type_id')->unsigned();
            $table->json('settings')->nullable();
            $table->timestamps();
        });

        Schema::table('merchants', function (Blueprint $table) {
            $table->foreign('merchant_type_id')->references('id')->on('merchant_types')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchants');
    }
};
