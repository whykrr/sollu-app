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
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->uuid('outlet_id')->nullable()->after('business_id');
            
            // Convert the enum column to string for flexibility
            $table->string('movement_type')->change();
            
            $table->foreign('outlet_id')->references('id')->on('outlets')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropForeign(['outlet_id']);
            $table->dropColumn('outlet_id');
            // Reverting to enum would require knowing the exact previous enum values, 
            // usually we can leave it as string on down() or define it specifically.
            $table->enum('movement_type', ['sale', 'purchase', 'adjustment', 'recipe_deduction', 'bundle_deduction'])->change();
        });
    }
};
