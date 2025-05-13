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
        Schema::create('content_fields', function (Blueprint $table) {
            $table->integer('id')->autoIncrement()->primary()->unsigned();
            $table->integer('content_type_id')->unsigned()->nullable();
            $table->string('key');
            $table->string('name');
            $table->enum('field_type', ['text', 'textarea', 'number', 'date', 'image', 'file', 'hyperlink', 'wysiwyg']);
            $table->boolean('is_required')->default(false);
            $table->text('validation')->nullable();
            $table->timestamps();

            $table->foreign('content_type_id')->references('id')->on('content_types')->onDelete('SET NULL');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('content_fields');
    }
};
