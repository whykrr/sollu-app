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
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('reference_number')->nullable()->after('expected_date');
        });

        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->decimal('discount_amount', 15, 2)->default(0)->after('purchase_price');
            $table->decimal('tax_amount', 15, 2)->default(0)->after('discount_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {
            $table->dropColumn(['discount_amount', 'tax_amount']);
        });

        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn('reference_number');
        });
    }
};
