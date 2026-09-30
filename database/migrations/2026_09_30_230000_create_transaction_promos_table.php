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
        if (! Schema::hasTable('transaction_promos')) {
            Schema::create('transaction_promos', function (Blueprint $table) {
                $table->uuid('id')->primary();
                $table->foreignUuid('transaction_id')->constrained('transactions')->cascadeOnDelete();
                $table->foreignUuid('transaction_item_id')->nullable()->constrained('transaction_items')->cascadeOnDelete();
                $table->foreignUuid('promo_id')->nullable()->constrained('promotions')->nullOnDelete();
                $table->string('promo_name', 255);
                $table->string('promo_code', 50)->nullable();
                $table->string('target_scope', 20)->default('transaction');
                $table->string('discount_type', 20)->default('fixed');
                $table->decimal('discount_value', 15, 4)->default(0);
                $table->decimal('discount_amount', 15, 4)->default(0);
                $table->timestamps();

                $table->index(['transaction_id', 'transaction_item_id']);
                $table->index(['promo_id', 'created_at']);
            });
        } else {
            Schema::table('transaction_promos', function (Blueprint $table) {
                if (! Schema::hasColumn('transaction_promos', 'transaction_item_id')) {
                    $table->foreignUuid('transaction_item_id')->nullable()->constrained('transaction_items')->cascadeOnDelete();
                }
                if (! Schema::hasColumn('transaction_promos', 'promo_code')) {
                    $table->string('promo_code', 50)->nullable();
                }
                if (! Schema::hasColumn('transaction_promos', 'target_scope')) {
                    $table->string('target_scope', 20)->default('transaction');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_promos');
    }
};
