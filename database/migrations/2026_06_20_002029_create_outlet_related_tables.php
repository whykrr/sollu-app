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
        Schema::create('outlet_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('outlet_id')->index();
            $table->string('category');
            $table->string('key');
            $table->jsonb('value')->nullable();
            $table->timestamps();

            $table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('cascade');
        });

        Schema::create('outlet_operational_hours', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('outlet_id')->index();
            $table->tinyInteger('day_of_week');
            $table->time('open_time')->nullable();
            $table->time('close_time')->nullable();
            $table->boolean('is_closed')->default(false);
            $table->timestamps();

            $table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('cascade');
        });

        Schema::create('outlet_devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('outlet_id')->index();
            $table->string('device_name');
            $table->string('device_type');
            $table->string('serial_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('cascade');
        });

        Schema::create('outlet_audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('outlet_id')->index();
            $table->uuid('user_id')->nullable()->index();
            $table->string('action');
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->foreign('outlet_id')->references('id')->on('outlets')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outlet_audit_logs');
        Schema::dropIfExists('outlet_devices');
        Schema::dropIfExists('outlet_operational_hours');
        Schema::dropIfExists('outlet_settings');
    }
};
