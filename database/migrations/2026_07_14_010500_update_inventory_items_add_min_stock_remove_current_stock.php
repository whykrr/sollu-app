<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->decimal('min_stock', 15, 4)->default(0)->after('track_inventory');
            $table->dropColumn('current_stock');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->decimal('current_stock', 15, 4)->default(0)->after('track_inventory');
            $table->dropColumn('min_stock');
        });
    }
};
