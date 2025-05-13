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
        Schema::create('localized_contents', function (Blueprint $table) {
            $table->integer('id')->autoIncrement()->primary()->unsigned();
            $table->integer('content_type_id')->unsigned()->nullable();
            $table->integer('language_id')->unsigned();
            $table->string('title');
            $table->string('slug')->unique();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keyword')->nullable();
            $table->timestamps();

            $table->foreign('content_type_id')->references('id')->on('content_types')->onDelete('SET NULL');
            $table->foreign('language_id')->references('id')->on('languages')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('localized_contents');
    }
};
