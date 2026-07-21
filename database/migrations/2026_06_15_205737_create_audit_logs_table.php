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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id')->nullable();
            $table->string('actor_type');
            $table->uuid('actor_id');
            $table->string('entity_type')->nullable();
            $table->uuid('entity_id')->nullable();
            $table->string('action');
            $table->jsonb('before_value')->nullable();
            $table->jsonb('after_value')->nullable();
            $table->jsonb('metadata')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
