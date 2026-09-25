<?php

use App\Helpers\VariantStringGenerator;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Add product_item_id to inventory_items
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->uuid('product_item_id')->nullable()->after('product_id');
        });

        // 2. Drop old foreign key constraints referencing inventory_items before data update
        if (Schema::hasTable('transaction_items')) {
            Schema::table('transaction_items', function (Blueprint $table) {
                $table->dropForeign(['inventory_item_id']);
            });
        }

        if (Schema::hasTable('promo_inventory_items')) {
            Schema::table('promo_inventory_items', function (Blueprint $table) {
                $table->dropForeign(['inventory_item_id']);
            });
        }

        // 3. Data Normalization
        $inventoryItems = DB::table('inventory_items')->get();

        foreach ($inventoryItems as $item) {
            $is_show = true;
            $sellable = true;

            // Get product settings if applicable
            if ($item->product_id) {
                $product = DB::table('products')->where('id', $item->product_id)->first();
                if ($product) {
                    $is_show = $product->is_show;
                    $sellable = $product->sellable;
                }
            }

            // Extract variant options if this is a variant_sku
            $variantCombination = null;
            if ($item->item_type === 'variant_sku') {
                $options = DB::table('inventory_item_variant_group_option')
                    ->join('variant_group_options', 'inventory_item_variant_group_option.variant_group_option_id', '=', 'variant_group_options.id')
                    ->where('inventory_item_variant_group_option.inventory_item_id', $item->id)
                    ->orderBy('variant_group_options.sort_order')
                    ->pluck('variant_group_options.name')
                    ->toArray();

                if (! empty($options)) {
                    // Check if parent product name is part of the array
                    $parts = [];
                    if ($product && ! empty($product->name)) {
                        $parts[] = $product->name;
                    }
                    $parts = array_merge($parts, $options);
                    $variantCombination = VariantStringGenerator::generate($parts);
                }
            }

            // We generate a new UUID for the product_item
            $productItemId = (string) Str::uuid();

            DB::table('product_items')->insert([
                'id' => $productItemId,
                'business_id' => $item->business_id,
                'item_type' => $item->item_type,
                'product_id' => $item->product_id,
                'uom_id' => $item->uom_id,
                'name' => $item->name,
                'variant_combination' => $variantCombination,
                'sku' => $item->sku,
                'barcode' => $item->barcode,
                'track_inventory' => $item->track_inventory,
                'is_show' => $is_show,
                'sellable' => $sellable,
                'is_active' => $item->is_active,
                'created_at' => $item->created_at,
                'updated_at' => $item->updated_at,
            ]);

            // Link the product item back to inventory item
            DB::table('inventory_items')
                ->where('id', $item->id)
                ->update(['product_item_id' => $productItemId]);

            // Update foreign keys that logically belong to the catalog variant
            DB::table('product_prices')
                ->where('inventory_item_id', $item->id)
                ->update(['inventory_item_id' => $productItemId]);

            DB::table('product_recipe_items')
                ->where('inventory_item_id', $item->id)
                ->update(['inventory_item_id' => $productItemId]);

            DB::table('modifier_recipe_items')
                ->where('inventory_item_id', $item->id)
                ->update(['inventory_item_id' => $productItemId]);

            DB::table('product_bundle_items')
                ->where('component_inventory_item_id', $item->id)
                ->update(['component_inventory_item_id' => $productItemId]);

            DB::table('transaction_items')
                ->where('inventory_item_id', $item->id)
                ->update(['inventory_item_id' => $productItemId]);

            if (Schema::hasTable('product_images') && Schema::hasColumn('product_images', 'inventory_item_id')) {
                DB::table('product_images')
                    ->where('inventory_item_id', $item->id)
                    ->update(['inventory_item_id' => $productItemId]);
            }

            if (Schema::hasTable('promo_inventory_items') && Schema::hasColumn('promo_inventory_items', 'inventory_item_id')) {
                DB::table('promo_inventory_items')
                    ->where('inventory_item_id', $item->id)
                    ->update(['inventory_item_id' => $productItemId]);
            }
        }

        // 4. Rename columns in related tables and add new foreign keys
        Schema::table('product_prices', function (Blueprint $table) {
            $table->renameColumn('inventory_item_id', 'product_item_id');
        });
        Schema::table('product_recipe_items', function (Blueprint $table) {
            $table->renameColumn('inventory_item_id', 'product_item_id');
        });
        Schema::table('modifier_recipe_items', function (Blueprint $table) {
            $table->renameColumn('inventory_item_id', 'product_item_id');
        });
        Schema::table('product_bundle_items', function (Blueprint $table) {
            $table->renameColumn('component_inventory_item_id', 'component_product_item_id');
        });

        if (Schema::hasTable('transaction_items')) {
            Schema::table('transaction_items', function (Blueprint $table) {
                $table->renameColumn('inventory_item_id', 'product_item_id');
                $table->foreign('product_item_id')->references('id')->on('product_items')->nullOnDelete();
            });
        }

        if (Schema::hasTable('product_images') && Schema::hasColumn('product_images', 'inventory_item_id')) {
            Schema::table('product_images', function (Blueprint $table) {
                $table->renameColumn('inventory_item_id', 'product_item_id');
            });
        }

        if (Schema::hasTable('promo_inventory_items')) {
            Schema::table('promo_inventory_items', function (Blueprint $table) {
                $table->renameColumn('inventory_item_id', 'product_item_id');
                $table->foreign('product_item_id')->references('id')->on('product_items')->cascadeOnDelete();
            });
        }

        // 5. Clean up inventory_items table
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn(['item_type', 'product_id', 'sku', 'barcode', 'track_inventory']);
        });

        // 6. Drop pivot table
        Schema::dropIfExists('inventory_item_variant_group_option');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Recreate dropped pivot table
        Schema::create('inventory_item_variant_group_option', function (Blueprint $table) {
            $table->uuid('inventory_item_id');
            $table->uuid('variant_group_option_id');
            $table->primary(['inventory_item_id', 'variant_group_option_id'], 'inv_item_vgo_primary');
        });

        // 2. Restore dropped columns to inventory_items
        Schema::table('inventory_items', function (Blueprint $table) {
            if (! Schema::hasColumn('inventory_items', 'name')) {
                $table->string('name')->nullable();
            }
            $table->enum('item_type', ['variant_sku', 'raw_material'])->default('variant_sku');
            $table->uuid('product_id')->nullable();
            $table->string('sku')->nullable();
            $table->string('barcode')->nullable();
            $table->boolean('track_inventory')->default(false);
        });

        // 3. Drop new foreign keys before data reversion
        if (Schema::hasTable('transaction_items')) {
            Schema::table('transaction_items', function (Blueprint $table) {
                $table->dropForeign(['product_item_id']);
            });
        }

        if (Schema::hasTable('promo_inventory_items')) {
            Schema::table('promo_inventory_items', function (Blueprint $table) {
                $table->dropForeign(['product_item_id']);
            });
        }

        // 4. Reverse Data Migration & restore foreign key values
        if (Schema::hasTable('product_items')) {
            $inventoryItems = DB::table('inventory_items')->whereNotNull('product_item_id')->get();

            foreach ($inventoryItems as $item) {
                $productItem = DB::table('product_items')->where('id', $item->product_item_id)->first();

                if ($productItem) {
                    DB::table('inventory_items')
                        ->where('id', $item->id)
                        ->update([
                            'name' => $productItem->name,
                            'item_type' => $productItem->item_type,
                            'product_id' => $productItem->product_id,
                            'sku' => $productItem->sku,
                            'barcode' => $productItem->barcode,
                            'track_inventory' => $productItem->track_inventory,
                        ]);

                    // Revert foreign key references from product_item_id back to inventory_item_id
                    DB::table('product_prices')
                        ->where('product_item_id', $productItem->id)
                        ->update(['product_item_id' => $item->id]);

                    DB::table('product_recipe_items')
                        ->where('product_item_id', $productItem->id)
                        ->update(['product_item_id' => $item->id]);

                    DB::table('modifier_recipe_items')
                        ->where('product_item_id', $productItem->id)
                        ->update(['product_item_id' => $item->id]);

                    DB::table('product_bundle_items')
                        ->where('component_product_item_id', $productItem->id)
                        ->update(['component_product_item_id' => $item->id]);

                    DB::table('transaction_items')
                        ->where('product_item_id', $productItem->id)
                        ->update(['product_item_id' => $item->id]);

                    if (Schema::hasTable('product_images') && Schema::hasColumn('product_images', 'product_item_id')) {
                        DB::table('product_images')
                            ->where('product_item_id', $productItem->id)
                            ->update(['product_item_id' => $item->id]);
                    }

                    if (Schema::hasTable('promo_inventory_items') && Schema::hasColumn('promo_inventory_items', 'product_item_id')) {
                        DB::table('promo_inventory_items')
                            ->where('product_item_id', $productItem->id)
                            ->update(['product_item_id' => $item->id]);
                    }
                }
            }
        }

        // 5. Rename columns back to their original names and restore old foreign keys
        Schema::table('product_prices', function (Blueprint $table) {
            $table->renameColumn('product_item_id', 'inventory_item_id');
        });
        Schema::table('product_recipe_items', function (Blueprint $table) {
            $table->renameColumn('product_item_id', 'inventory_item_id');
        });
        Schema::table('modifier_recipe_items', function (Blueprint $table) {
            $table->renameColumn('product_item_id', 'inventory_item_id');
        });
        Schema::table('product_bundle_items', function (Blueprint $table) {
            $table->renameColumn('component_product_item_id', 'component_inventory_item_id');
        });

        if (Schema::hasTable('transaction_items')) {
            Schema::table('transaction_items', function (Blueprint $table) {
                $table->renameColumn('product_item_id', 'inventory_item_id');
                $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->nullOnDelete();
            });
        }

        if (Schema::hasTable('product_images') && Schema::hasColumn('product_images', 'product_item_id')) {
            Schema::table('product_images', function (Blueprint $table) {
                $table->renameColumn('product_item_id', 'inventory_item_id');
            });
        }

        if (Schema::hasTable('promo_inventory_items')) {
            Schema::table('promo_inventory_items', function (Blueprint $table) {
                $table->renameColumn('product_item_id', 'inventory_item_id');
                $table->foreign('inventory_item_id')->references('id')->on('inventory_items')->cascadeOnDelete();
            });
        }

        // 6. Remove product_item_id from inventory_items
        Schema::table('inventory_items', function (Blueprint $table) {
            $table->dropColumn('product_item_id');
        });
    }
};
