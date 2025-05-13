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
        Schema::create('message_responses', function (Blueprint $table) {
            $table->integer('id')->autoIncrement()->primary()->unsigned();
            $table->integer('message_id')->unsigned();
            $table->text('message');
            $table->foreignId('created_by')->unsigned()->nullable();
            $table->dateTime('created_at')->nullable();

            $table->foreign('message_id')->references('id')->on('messages')->onDelete('RESTRICT');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('message_responses');
    }
};
