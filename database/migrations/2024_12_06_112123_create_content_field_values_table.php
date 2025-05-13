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
        Schema::create('content_field_values', function (Blueprint $table) {
            $table->integer('id')->autoIncrement()->primary()->unsigned();
            $table->integer('content_id')->unsigned();
            $table->integer('content_field_id')->unsigned()->nullable();
            $table->text('value')->nullable();
            $table->timestamps();

            $table->foreign('content_id')->references('id')->on('contents')->onDelete('RESTRICT');
            $table->foreign('content_field_id')->references('id')->on('content_fields')->onDelete('SET NULL');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_field_values');
    }
};
