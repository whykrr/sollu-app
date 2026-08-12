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
        Schema::table('outlet_devices', function (Blueprint $table) {
            $table->string('client_device_uuid')->nullable()->after('serial_number');
            $table->string('hardware_fingerprint')->nullable()->after('client_device_uuid');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outlet_devices', function (Blueprint $table) {
            $table->dropColumn(['client_device_uuid', 'hardware_fingerprint']);
        });
    }
};
