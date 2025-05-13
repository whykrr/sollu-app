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
        Schema::create('contents', function (Blueprint $table) {
            $table->integer('id')->autoIncrement()->primary()->unsigned();
            $table->integer('content_type_id')->unsigned()->nullable();
            $table->string('title');
            $table->string('slug')->unique();
            $table->boolean('published')->default(true);
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();
            $table->string('meta_keyword')->nullable();
            $table->foreignId('created_by')->unsigned()->nullable();
            $table->timestamps();

            $table->foreign('content_type_id')->references('id')->on('content_types')->onDelete('SET NULL');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contents');
    }
};
