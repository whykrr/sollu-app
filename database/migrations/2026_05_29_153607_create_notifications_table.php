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
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->timestamps();
            $table->string('type');
            $table->uuidMorphs('notifiable');
            if (\Illuminate\Support\Facades\DB::connection()->getDriverName() !== 'sqlite') {
                $table->json('data')->fulltext();
            } else {
                $table->json('data');
            }
            $table->timestamp('read_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
