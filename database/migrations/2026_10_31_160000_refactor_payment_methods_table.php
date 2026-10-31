<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->boolean('is_active')->default(true)->after('type');
            $table->boolean('is_system')->default(false)->after('is_active');
            $table->string('provider', 50)->nullable()->after('type');
            $table->string('account_number', 50)->nullable()->after('provider');
            $table->string('account_name', 100)->nullable()->after('account_number');
            $table->string('reference_policy', 20)->default('optional')->after('account_name');
            $table->boolean('open_cash_drawer')->default(false)->after('reference_policy');
            $table->text('instructions')->nullable()->after('open_cash_drawer');
            $table->string('qr_image_path')->nullable()->after('instructions');
        });

        // ETL: Set system defaults for cash and edc methods
        DB::table('payment_methods')
            ->where('type', 'cash')
            ->update([
                'is_system' => true,
                'open_cash_drawer' => true,
                'reference_policy' => 'hidden',
                'is_active' => true,
            ]);

        DB::table('payment_methods')
            ->where('type', 'edc')
            ->update([
                'reference_policy' => 'mandatory',
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn([
                'is_active',
                'is_system',
                'provider',
                'account_number',
                'account_name',
                'reference_policy',
                'open_cash_drawer',
                'instructions',
                'qr_image_path',
            ]);
        });
    }
};
