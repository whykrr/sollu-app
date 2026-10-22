<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        // 1. Sanitize single products: ensure all single products have variant_combination = null (Unified Item Invariant)
        DB::table('product_items')
            ->whereIn('product_id', function ($query) {
                $query->select('id')
                    ->from('products')
                    ->where('has_variant', false);
            })
            ->whereNotNull('variant_combination')
            ->update([
                'variant_combination' => null,
                'updated_at' => $now,
            ]);

        // 2. Backfill single products lacking a default ProductItem
        $singleProductsWithoutItems = DB::table('products')
            ->where('has_variant', false)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('product_items')
                    ->whereColumn('product_items.product_id', 'products.id');
            })
            ->get();

        foreach ($singleProductsWithoutItems as $product) {
            $productItemId = (string) Str::uuid();
            $sku = $product->code ?: ('PRD-'.strtoupper(Str::random(6)));

            DB::table('product_items')->insert([
                'id' => $productItemId,
                'business_id' => $product->business_id,
                'item_type' => 'variant_sku',
                'product_id' => $product->id,
                'uom_id' => null,
                'name' => $product->name,
                'variant_combination' => null,
                'sku' => $sku,
                'barcode' => null,
                'track_inventory' => (bool) $product->track_inventory,
                'is_show' => (bool) $product->is_show,
                'sellable' => (bool) $product->sellable,
                'is_active' => true,
                'created_at' => $product->created_at ?: $now,
                'updated_at' => $product->updated_at ?: $now,
            ]);

            // Link existing unassigned prices for this product
            DB::table('product_prices')
                ->where('product_id', $product->id)
                ->whereNull('product_item_id')
                ->update([
                    'product_item_id' => $productItemId,
                    'updated_at' => $now,
                ]);
        }

        // Also link existing prices of single products that already have a product item but prices.product_item_id is null
        $singleProductsWithItem = DB::table('products')
            ->join('product_items', 'products.id', '=', 'product_items.product_id')
            ->where('products.has_variant', false)
            ->whereNull('product_items.variant_combination')
            ->select('products.id as product_id', 'product_items.id as product_item_id')
            ->get();

        foreach ($singleProductsWithItem as $row) {
            DB::table('product_prices')
                ->where('product_id', $row->product_id)
                ->whereNull('product_item_id')
                ->update([
                    'product_item_id' => $row->product_item_id,
                    'updated_at' => $now,
                ]);
        }

        // 3. Backfill product_item_variant_group_options from existing variant items
        $variantItems = DB::table('product_items')
            ->join('products', 'product_items.product_id', '=', 'products.id')
            ->where('products.has_variant', true)
            ->select(
                'product_items.id as product_item_id',
                'product_items.name as item_name',
                'product_items.sku',
                'products.id as product_id'
            )
            ->get();

        $inserts = [];
        foreach ($variantItems as $item) {
            $options = DB::table('variant_group_options')
                ->join('variant_groups', 'variant_group_options.variant_group_id', '=', 'variant_groups.id')
                ->where('variant_groups.product_id', $item->product_id)
                ->select(
                    'variant_group_options.id as option_id',
                    'variant_group_options.name as option_name'
                )
                ->get();

            foreach ($options as $opt) {
                $slug = strtolower(str_replace(' ', '-', $opt->option_name));
                if (
                    str_contains(strtolower($item->item_name), strtolower($opt->option_name))
                    || ($item->sku && str_contains(strtolower($item->sku), $slug))
                ) {
                    $inserts[] = [
                        'product_item_id' => $item->product_item_id,
                        'variant_group_option_id' => $opt->option_id,
                    ];
                }
            }
        }

        if (! empty($inserts)) {
            DB::table('product_item_variant_group_options')->insertOrIgnore($inserts);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // 1. Truncate backfilled pivot options
        DB::table('product_item_variant_group_options')->delete();

        // 2. Revert unassigned prices for pre-existing single product if applicable
        DB::table('product_prices')
            ->where('id', '01a12018-f19d-7301-86fd-6468cf2f313d')
            ->update(['product_item_id' => null]);

        // 3. Revert backfilled product_items and their associated prices
        $backfilledItems = DB::table('product_items as pi')
            ->join('products as p', 'pi.product_id', '=', 'p.id')
            ->where('p.has_variant', false)
            ->whereNull('pi.variant_combination')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('inventory_items as ii')
                    ->whereColumn('ii.product_item_id', 'pi.id');
            })
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('transaction_items as ti')
                    ->whereColumn('ti.product_item_id', 'pi.id');
            })
            ->pluck('pi.id')
            ->toArray();

        if (! empty($backfilledItems)) {
            DB::table('product_prices')
                ->whereIn('product_item_id', $backfilledItems)
                ->update(['product_item_id' => null]);

            DB::table('product_items')
                ->whereIn('id', $backfilledItems)
                ->delete();
        }
    }
};
