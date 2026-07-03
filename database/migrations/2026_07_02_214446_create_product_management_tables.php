<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('parent_id')->nullable();
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // $table->foreign('parent_id')->references('id')->on('product_categories')->onDelete('set null');
        });

        Schema::create('products', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('product_category_id')->nullable();
            $table->enum('product_type', ['basic', 'service', 'bundle']);

            // Feature flags
            $table->boolean('has_variant')->default(false);
            $table->boolean('has_modifier')->default(false);
            $table->boolean('has_recipe')->default(false);
            $table->boolean('track_inventory')->default(false);

            $table->string('code')->nullable();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_show')->default(true);
            $table->boolean('sellable')->default(true);
            $table->boolean('purchasable')->default(false);

            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('variant_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('variant_group_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('variant_group_id');
            $table->string('name');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('modifier_groups', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->string('name');
            $table->enum('selection_type', ['single', 'multi']);
            $table->integer('max_select')->nullable();
            $table->boolean('is_required')->default(false);
            $table->timestamps();
        });

        Schema::create('modifier_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('modifier_group_id');
            $table->string('name');
            $table->decimal('additional_price', 15, 2)->default(0);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
        });

        Schema::create('product_modifier_groups', function (Blueprint $table) {
            $table->uuid('product_id');
            $table->uuid('modifier_group_id');
            $table->primary(['product_id', 'modifier_group_id']);
        });

        Schema::create('recipe_versions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->integer('version_number');
            $table->boolean('is_active')->default(false);
            $table->timestamp('effective_from')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->enum('item_type', ['variant_sku', 'raw_material']);
            $table->uuid('product_id')->nullable();
            $table->uuid('raw_material_id')->nullable(); // In case we want to support raw materials entity explicitly
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->boolean('track_inventory')->default(false);
            $table->decimal('current_stock', 15, 4)->default(0);
            $table->timestamps();
        });

        Schema::create('product_recipe_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('recipe_version_id');
            $table->uuid('inventory_item_id');
            $table->decimal('qty', 15, 4);
            $table->string('uom');
            $table->timestamps();
        });

        Schema::create('modifier_recipe_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('modifier_option_id');
            $table->uuid('inventory_item_id');
            $table->decimal('qty', 15, 4);
            $table->string('uom');
            $table->timestamps();
        });

        Schema::create('product_bundle_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('bundle_product_id');
            $table->uuid('component_product_id');
            $table->uuid('component_inventory_item_id')->nullable();
            $table->decimal('qty', 15, 4);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('outlet_product', function (Blueprint $table) {
            $table->uuid('outlet_id');
            $table->uuid('product_id');
            $table->boolean('is_enabled')->default(true);
            $table->boolean('is_available')->default(true);
            $table->primary(['outlet_id', 'product_id']);
        });

        Schema::create('product_prices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('product_id');
            $table->uuid('outlet_id')->nullable();
            $table->uuid('inventory_item_id')->nullable();
            $table->decimal('amount', 15, 2);
            $table->timestamps();
        });

        Schema::create('inventory_item_variant_group_option', function (Blueprint $table) {
            $table->uuid('inventory_item_id');
            $table->uuid('variant_group_option_id');
            $table->primary(['inventory_item_id', 'variant_group_option_id'], 'inv_item_vgo_primary');
        });

        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('business_id');
            $table->uuid('inventory_item_id');
            $table->enum('movement_type', ['sale', 'purchase', 'adjustment', 'recipe_deduction', 'bundle_deduction']);
            $table->decimal('qty_change', 15, 4);
            $table->decimal('stock_before', 15, 4);
            $table->decimal('stock_after', 15, 4);
            $table->uuid('reference_id')->nullable();
            $table->string('reference_type')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('inventory_item_variant_group_option');
        Schema::dropIfExists('product_prices');
        Schema::dropIfExists('outlet_product');
        Schema::dropIfExists('product_bundle_items');
        Schema::dropIfExists('modifier_recipe_items');
        Schema::dropIfExists('product_recipe_items');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('recipe_versions');
        Schema::dropIfExists('product_modifier_groups');
        Schema::dropIfExists('modifier_options');
        Schema::dropIfExists('modifier_groups');
        Schema::dropIfExists('variant_group_options');
        Schema::dropIfExists('variant_groups');
        Schema::dropIfExists('products');
        Schema::dropIfExists('product_categories');
    }
};
