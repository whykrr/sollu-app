<?php

declare(strict_types=1);

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
        Schema::table('transactions', function (Blueprint $table) {
            $table->timestamp('voided_at')->nullable()->after('status');
            $table->text('void_reason')->nullable()->after('voided_at');
            $table->foreignUuid('voided_by')->nullable()->after('void_reason')->constrained('users')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['voided_by']);
            $table->dropColumn(['voided_by', 'void_reason', 'voided_at']);
        });
    }
};
