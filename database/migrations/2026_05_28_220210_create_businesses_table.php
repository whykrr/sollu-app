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
        Schema::create('businesses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamps();
            $table->string('slug', 200)->unique();
            $table->string('name', 200);
            $table->string('owner_name', 200);
            $table->string('email', 200)->unique();
            $table->string('phone', 20);
            $table->text('address')->nullable();
            $table->text('logo')->nullable();
            $table->dateTime('trial_end_at');
            $table->tinyInteger('business_type_id')->unsigned();
            $table->string('status', 15)->default('active')->comment('options: active, suspend');
            $table->json('settings')->nullable();
        });

        Schema::table('businesses', function (Blueprint $table) {
            $table->foreign('business_type_id')->references('id')->on('business_types')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};
