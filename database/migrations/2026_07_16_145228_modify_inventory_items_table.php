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
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->uuid('uom_id')->nullable()->after('barcode');
            
            // Laravel renameColumn requires doctrine/dbal. If not present, we can just drop and recreate, 
            // but assuming doctrine is there or we just add the new one.
            // Wait, standard practice in Laravel 11 natively supports renaming without doctrine.
            $table->renameColumn('min_stock', 'minimum_stock');
            
            $table->foreign('uom_id')->references('id')->on('uoms')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropForeign(['uom_id']);
            $table->dropColumn('uom_id');
            $table->renameColumn('minimum_stock', 'min_stock');
        });
    }
};
