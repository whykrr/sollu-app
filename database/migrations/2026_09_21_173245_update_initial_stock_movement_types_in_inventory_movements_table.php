<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('inventory_movements')
            ->where('movement_type', 'adjustment')
            ->where(function ($q) {
                $q->where('description', 'like', 'Input Stok Awal%')
                    ->orWhere('description', 'like', 'Stok Awal%');
            })
            ->update(['movement_type' => 'initial_stock']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('inventory_movements')
            ->where('movement_type', 'initial_stock')
            ->where(function ($q) {
                $q->where('description', 'like', 'Input Stok Awal%')
                    ->orWhere('description', 'like', 'Stok Awal%');
            })
            ->update(['movement_type' => 'adjustment']);
    }
};
