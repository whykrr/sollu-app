<?php

declare(strict_types=1);

use App\Enums\TransactionTypeEnum;
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
        Schema::table('transactions', function (Blueprint $table) {
            $table->string('type', 20)
                ->default(TransactionTypeEnum::Invoice->value)
                ->after('channel')
                ->index();
        });

        // Backfill: Transaksi yang memiliki shift_id atau tidak memiliki invoice adalah transaksi POS
        DB::table('transactions')
            ->whereNotNull('shift_id')
            ->orWhereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('transaction_invoices')
                    ->whereColumn('transaction_invoices.transaction_id', 'transactions.id');
            })
            ->update(['type' => TransactionTypeEnum::Pos->value]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
