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
        Schema::create('content_types', function (Blueprint $table) {
            $table->integer('id')->autoIncrement()->primary()->unsigned();
            $table->integer('parent_id')->unsigned()->nullable();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_listed')->default(false);
            $table->integer('max_row')->nullable();
            $table->string('title_aliases')->nullable();
            $table->boolean('with_meta')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_types');
    }
};
