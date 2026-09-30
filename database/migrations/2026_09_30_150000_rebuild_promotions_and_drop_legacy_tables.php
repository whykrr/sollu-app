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
        // 1. Buat Tabel Master Promosi Baru (V1)
        Schema::create('promotions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('business_id')->constrained('businesses')->cascadeOnDelete();
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->string('application_mode', 20)->default('automatic');
            $table->string('promo_code', 50)->nullable();
            $table->string('target_scope', 20)->default('transaction');
            $table->string('discount_type', 20)->default('percentage');
            $table->decimal('discount_value', 15, 4)->default(0);
            $table->decimal('max_discount_amount', 15, 4)->nullable();
            $table->decimal('min_subtotal', 15, 4)->default(0);
            $table->decimal('min_quantity', 15, 4)->default(1);
            $table->boolean('applies_to_all_outlets')->default(true);
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->jsonb('days_of_week')->nullable();
            $table->string('status', 20)->default('draft');
            $table->foreignUuid('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable();
            $table->foreignUuid('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('business_id');
            $table->index(['business_id', 'status']);
            $table->unique(['business_id', 'promo_code']);
        });

        // 2. Buat Pivot Outlets
        Schema::create('promotion_outlets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->foreignUuid('outlet_id')->constrained('outlets')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['promotion_id', 'outlet_id']);
        });

        // 3. Buat Pivot Target Kategori
        Schema::create('promotion_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->foreignUuid('category_id')->constrained('product_categories')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['promotion_id', 'category_id']);
        });

        // 4. Buat Pivot Target Produk Master
        Schema::create('promotion_products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->foreignUuid('product_id')->constrained('products')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['promotion_id', 'product_id']);
        });

        // 5. Buat Pivot Target Varian (Product Item)
        Schema::create('promotion_product_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('promotion_id')->constrained('promotions')->cascadeOnDelete();
            $table->foreignUuid('product_item_id')->constrained('product_items')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->unique(['promotion_id', 'product_item_id']);
        });

        // 6. Alihkan FK pada transaction_promos dari promos ke promotions (jika tabel ada)
        if (Schema::hasTable('transaction_promos')) {
            Schema::table('transaction_promos', function (Blueprint $table) {
                $table->dropForeign(['promo_id']);
            });

            Schema::table('transaction_promos', function (Blueprint $table) {
                $table->foreign('promo_id')->references('id')->on('promotions')->nullOnDelete();
            });
        }

        // 7. Drop Tabel Legacy Promo
        Schema::dropIfExists('promo_inventory_items');
        Schema::dropIfExists('promo_outlets');
        Schema::dropIfExists('promos');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Re-create Tabel Legacy Promo (Absolute Rollback Symmetry)
        Schema::create('promos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('promo_type');
            $table->string('target_type');
            $table->decimal('discount_value', 15, 4);
            $table->decimal('max_discount', 15, 4)->nullable();
            $table->boolean('applies_to_all_outlets')->default(true);
            $table->date('start_date');
            $table->date('end_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('status')->default('draft');
            $table->uuid('published_by')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->uuid('created_by');
            $table->timestamps();

            $table->foreign('business_id')->references('id')->on('businesses')->cascadeOnDelete();
            $table->foreign('published_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnDelete();
        });

        Schema::create('promo_outlets', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('promo_id');
            $table->uuid('outlet_id');
            $table->timestamps();

            $table->foreign('promo_id')->references('id')->on('promos')->cascadeOnDelete();
            $table->foreign('outlet_id')->references('id')->on('outlets')->cascadeOnDelete();
        });

        Schema::create('promo_inventory_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('promo_id');
            $table->uuid('inventory_item_id');
            $table->timestamps();

            $table->foreign('promo_id')->references('id')->on('promos')->cascadeOnDelete();
            $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->cascadeOnDelete();
        });

        // 2. Kembalikan FK transaction_promos ke promos
        Schema::table('transaction_promos', function (Blueprint $table) {
            $table->dropForeign(['promo_id']);
        });

        Schema::table('transaction_promos', function (Blueprint $table) {
            $table->foreign('promo_id')->references('id')->on('promos')->nullOnDelete();
        });

        // 3. Drop Tabel Baru V1
        Schema::dropIfExists('promotion_product_items');
        Schema::dropIfExists('promotion_products');
        Schema::dropIfExists('promotion_categories');
        Schema::dropIfExists('promotion_outlets');
        Schema::dropIfExists('promotions');
    }
};
