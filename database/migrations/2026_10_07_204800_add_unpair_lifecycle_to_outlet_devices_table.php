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
            $table->timestamp('unpaired_at')->nullable()->after('is_active');
            $table->foreignUuid('unpaired_by')->nullable()->after('unpaired_at')->constrained('users')->nullOnDelete();
            $table->index(['outlet_id', 'is_active'], 'idx_outlet_devices_active_lookup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outlet_devices', function (Blueprint $table) {
            $table->dropIndex('idx_outlet_devices_active_lookup');
            $table->dropForeign(['unpaired_by']);
            $table->dropColumn(['unpaired_by', 'unpaired_at']);
        });
    }
};
