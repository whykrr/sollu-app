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
        // 1. Create a reversible backup table to store removed pivot records for absolute rollback symmetry
        if (! Schema::hasTable('product_item_variant_group_options_repair_backup')) {
            Schema::create('product_item_variant_group_options_repair_backup', function (Blueprint $table) {
                $table->uuid('product_item_id');
                $table->uuid('variant_group_option_id');
                $table->primary(['product_item_id', 'variant_group_option_id']);
            });
        }

        // 2. Identify items that have multiple options belonging to the same variant_group
        $duplicates = DB::table('product_item_variant_group_options as piv')
            ->join('product_items as pi', 'pi.id', '=', 'piv.product_item_id')
            ->join('variant_group_options as vgo', 'vgo.id', '=', 'piv.variant_group_option_id')
            ->join('variant_groups as vg', 'vg.id', '=', 'vgo.variant_group_id')
            ->select([
                'piv.product_item_id',
                'piv.variant_group_option_id',
                'pi.name as item_name',
                'pi.sku',
                'vgo.name as option_name',
                'vg.id as variant_group_id',
            ])
            ->whereIn('piv.product_item_id', function ($query) {
                $query->select('piv2.product_item_id')
                    ->from('product_item_variant_group_options as piv2')
                    ->join('variant_group_options as vgo2', 'vgo2.id', '=', 'piv2.variant_group_option_id')
                    ->groupBy('piv2.product_item_id', 'vgo2.variant_group_id')
                    ->havingRaw('COUNT(piv2.variant_group_option_id) > 1');
            })
            ->get();

        $toDelete = [];
        $grouped = $duplicates->groupBy(fn ($row) => $row->product_item_id.'_'.$row->variant_group_id);

        foreach ($grouped as $options) {
            if ($options->count() <= 1) {
                continue;
            }

            $first = $options->first();
            $nameParts = array_map(fn ($p) => strtolower(trim($p)), explode(' - ', $first->item_name));
            $skuParts = array_map(fn ($p) => strtolower(trim($p)), explode('-', $first->sku ?? ''));

            // Find the genuine matching option
            $matchedOption = null;
            foreach ($options as $opt) {
                $optNameLower = strtolower(trim($opt->option_name));
                $optSlug = strtolower(str_replace(' ', '', $opt->option_name));

                if (
                    in_array($optNameLower, $nameParts, true)
                    || in_array($optNameLower, $skuParts, true)
                    || in_array($optSlug, $skuParts, true)
                ) {
                    $matchedOption = $opt;
                    break;
                }
            }

            foreach ($options as $opt) {
                if ($matchedOption && $opt->variant_group_option_id === $matchedOption->variant_group_option_id) {
                    // Keep genuine option
                    continue;
                }
                $toDelete[] = [
                    'product_item_id' => $opt->product_item_id,
                    'variant_group_option_id' => $opt->variant_group_option_id,
                ];
            }
        }

        if (! empty($toDelete)) {
            DB::table('product_item_variant_group_options_repair_backup')->insertOrIgnore($toDelete);

            foreach ($toDelete as $record) {
                DB::table('product_item_variant_group_options')
                    ->where('product_item_id', $record['product_item_id'])
                    ->where('variant_group_option_id', $record['variant_group_option_id'])
                    ->delete();
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('product_item_variant_group_options_repair_backup')) {
            $backupRows = DB::table('product_item_variant_group_options_repair_backup')->get();

            foreach ($backupRows as $row) {
                DB::table('product_item_variant_group_options')->insertOrIgnore([
                    'product_item_id' => $row->product_item_id,
                    'variant_group_option_id' => $row->variant_group_option_id,
                ]);
            }

            Schema::dropIfExists('product_item_variant_group_options_repair_backup');
        }
    }
};
