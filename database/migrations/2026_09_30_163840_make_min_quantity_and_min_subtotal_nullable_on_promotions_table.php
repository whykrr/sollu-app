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
        Schema::table('promotions', function (Blueprint $table) {
            $table->decimal('min_quantity', 15, 4)->nullable()->default(1)->change();
            $table->decimal('min_subtotal', 15, 4)->nullable()->default(0)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('promotions', function (Blueprint $table) {
            $table->decimal('min_quantity', 15, 4)->default(1)->nullable(false)->change();
            $table->decimal('min_subtotal', 15, 4)->default(0)->nullable(false)->change();
        });
    }
};
