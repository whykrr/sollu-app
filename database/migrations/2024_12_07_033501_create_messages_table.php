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
        Schema::create('messages', function (Blueprint $table) {
            $table->integer('id')->autoIncrement()->primary()->unsigned();
            $table->string('name')->fulltext();
            $table->string('email')->index();
            $table->string('subject')->fulltext();
            $table->text('message');
            $table->enum('status', ['unread', 'read', 'replied', 'archived'])->index()->default('unread');
            $table->timestamps();
            $table->dateTime('read_at')->nullable();
            $table->foreignId('read_by')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};
