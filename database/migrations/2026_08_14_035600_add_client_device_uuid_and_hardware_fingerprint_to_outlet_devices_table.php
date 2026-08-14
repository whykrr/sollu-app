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
            if (Schema::hasColumn('outlet_devices', 'fingerprint')) {
                $table->dropUnique(['fingerprint']);
                $table->dropColumn('fingerprint');
            }
            $table->string('client_device_uuid')->nullable()->index();
            $table->string('hardware_fingerprint')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outlet_devices', function (Blueprint $table) {
            $table->dropIndex(['client_device_uuid']);
            $table->dropColumn(['client_device_uuid', 'hardware_fingerprint']);
            $table->string('fingerprint')->nullable()->unique();
        });
    }
};
