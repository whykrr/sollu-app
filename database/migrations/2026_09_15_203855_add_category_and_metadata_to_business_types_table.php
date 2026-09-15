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
        Schema::table('business_types', function (Blueprint $table) {
            $table->string('category', 50)->default('retail')->after('name');
            $table->string('category_label', 100)->default('Ritel & Toko')->after('category');
            $table->integer('sort_order')->default(0)->after('is_visible');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('business_types', function (Blueprint $table) {
            $table->dropColumn(['category', 'category_label', 'sort_order']);
        });
    }
};
