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
        Schema::create('visitors', function (Blueprint $table) {
            $table->id();
            $table->ipAddress();
            $table->string('user_agent')->nullable();
            $table->string('url')->index();
            $table->string('referrer')->nullable();
            $table->string('session_id')->nullable();
            $table->string('created_month', 7);
            $table->timestamps();

            $table->index(['created_at']);
            $table->index(['ip_address', 'session_id']);
            $table->index(['created_month', 'url', 'ip_address', 'session_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('visitors');
    }
};
