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
        Schema::create('product_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->enum('item_type', ['variant_sku', 'raw_material']);
            $table->uuid('product_id')->nullable();
            $table->uuid('uom_id')->nullable();

            $table->string('name')->nullable();

            $table->string('variant_combination')->nullable()->index();
            $table->string('sku')->nullable()->index();
            $table->string('barcode')->nullable()->index();

            $table->boolean('track_inventory')->default(false);
            $table->boolean('is_show')->default(true);
            $table->boolean('sellable')->default(true);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
            $table->foreign('uom_id')->references('id')->on('uoms')->nullOnDelete();
        });

        if (DB::getDriverName() !== 'sqlite') {
            Schema::table('product_items', function (Blueprint $table) {
                $table->fullText('name');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_items');
    }
};
