<?php

declare(strict_types=1);

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
        // 1. Data patch: Backfill channels lama (direct, e_commerce, social_media, custom) ke wholesale
        DB::table('transactions')
            ->whereIn('channel', ['direct', 'e_commerce', 'social_media', 'custom'])
            ->update(['channel' => 'wholesale']);

        // 2. Data patch: Transaksi POS (memiliki shift_id atau channel pos terdahulu) jika bertipe pos diubah ke walk_in
        DB::table('transactions')
            ->where('type', 'pos')
            ->update(['type' => 'walk_in']);

        // Pastikan default type pada skema adalah 'walk_in'
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('type', 20)->default('walk_in')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('type', 20)->default('invoice')->change();
        });

        DB::table('transactions')
            ->where('type', 'walk_in')
            ->where('channel', 'pos')
            ->update(['type' => 'pos']);
    }
};
