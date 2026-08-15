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
            $table->string('app_version')->nullable()->after('is_active');
            $table->string('platform_type')->nullable()->after('app_version');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outlet_devices', function (Blueprint $table) {
            $table->dropColumn(['app_version', 'platform_type']);
        });
    }
};
