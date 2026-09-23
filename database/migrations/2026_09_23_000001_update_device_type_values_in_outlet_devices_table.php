<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('outlet_devices')
            ->where('device_type', 'pos')
            ->update(['device_type' => 'pos_terminal']);

        DB::table('outlet_devices')
            ->where('device_type', 'kds')
            ->update(['device_type' => 'kitchen_display']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('outlet_devices')
            ->where('device_type', 'pos_terminal')
            ->update(['device_type' => 'pos']);

        DB::table('outlet_devices')
            ->where('device_type', 'kitchen_display')
            ->update(['device_type' => 'kds']);
    }
};
