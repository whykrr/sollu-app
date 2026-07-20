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
            $table->dropColumn('raw_material_id');
        });

        Schema::dropIfExists('raw_materials');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Re-creating raw_materials table for rollback
        Schema::create('raw_materials', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
        });

        Schema::table('inventory_items', function (Blueprint $table) {
            $table->uuid('raw_material_id')->nullable()->after('product_id');
            $table->foreign('raw_material_id')->references('id')->on('raw_materials')->onDelete('set null');
        });
    }
};
