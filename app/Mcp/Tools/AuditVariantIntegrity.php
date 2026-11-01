<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Audits product item variant pivot integrity, detecting duplicate options per variant group, orphan pivot rows, and combination discrepancies.')]
class AuditVariantIntegrity extends Tool
{
    protected string $name = 'audit_variant_integrity';

    protected string $title = 'Audit Variant Integrity';

    /**
     * Get the tool's input schema.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'scope' => $schema->string()
                ->enum(['all', 'duplicates', 'orphans', 'combinations'])
                ->description('Filter audit scope: "all", "duplicates", "orphans", or "combinations". Defaults to "all".'),
            'product_id' => $schema->string()
                ->description('Optional Product ID (UUID) or SKU to audit a specific product.'),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $scope = (string) $request->get('scope', 'all');
        $productId = (string) $request->get('product_id', '');

        if (! Schema::hasTable('product_item_variant_group_options') || ! Schema::hasTable('product_items')) {
            return Response::json([
                'status' => 'error',
                'summary' => 'Required product variant tables do not exist in the database.',
                'violations' => [],
            ]);
        }

        $violations = [];

        // 1. Check for Duplicate Options from the Same Variant Group on a Single Product Item
        if (in_array($scope, ['all', 'duplicates'], true)) {
            $duplicateViolations = $this->checkDuplicateGroupOptions($productId);
            if (! empty($duplicateViolations)) {
                $violations['duplicate_group_options'] = $duplicateViolations;
            }
        }

        // 2. Check for Orphan Pivot Records
        if (in_array($scope, ['all', 'orphans'], true)) {
            $orphanViolations = $this->checkOrphanPivots($productId);
            if (! empty($orphanViolations)) {
                $violations['orphan_pivots'] = $orphanViolations;
            }
        }

        // 3. Check for Empty Variant Combination on Variant Product Items
        if (in_array($scope, ['all', 'combinations'], true)) {
            $combinationViolations = $this->checkCombinationSync($productId);
            if (! empty($combinationViolations)) {
                $violations['combination_discrepancies'] = $combinationViolations;
            }
        }

        $totalViolations = array_sum(array_map('count', $violations));
        $hasErrors = ! empty($violations['duplicate_group_options']) || ! empty($violations['orphan_pivots']);
        $hasWarnings = ! empty($violations['combination_discrepancies']);

        $status = $hasErrors ? 'error' : ($hasWarnings ? 'warning' : 'pass');

        $summary = $totalViolations === 0
            ? 'All product variant relationships and pivot records are 100% healthy.'
            : sprintf('Found %d variant integrity issue(s) across %d category/categories (Status: %s).', $totalViolations, count($violations), $status);

        return Response::json([
            'status' => $status,
            'summary' => $summary,
            'total_violations' => $totalViolations,
            'violations' => $violations,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function checkDuplicateGroupOptions(string $productId): array
    {
        $query = DB::table('product_item_variant_group_options as piv')
            ->join('variant_group_options as vgo', 'vgo.id', '=', 'piv.variant_group_option_id')
            ->join('variant_groups as vg', 'vg.id', '=', 'vgo.variant_group_id')
            ->join('product_items as pi', 'pi.id', '=', 'piv.product_item_id')
            ->join('products as p', 'p.id', '=', 'pi.product_id')
            ->select(
                'pi.id as product_item_id',
                'pi.sku',
                'pi.barcode',
                'p.id as product_id',
                'p.name as product_name',
                'vg.id as variant_group_id',
                'vg.name as variant_group_name',
                DB::raw('COUNT(*) as total_options'),
                DB::raw("STRING_AGG(vgo.name, ', ') as attached_options")
            )
            ->groupBy('pi.id', 'pi.sku', 'pi.barcode', 'p.id', 'p.name', 'vg.id', 'vg.name')
            ->havingRaw('COUNT(*) > 1');

        if (! empty($productId)) {
            $query->where(function ($q) use ($productId) {
                $q->where('p.id', $productId)
                    ->orWhere('pi.sku', $productId)
                    ->orWhere('p.name', 'ILIKE', "%{$productId}%");
            });
        }

        return $query->get()->map(function ($row) {
            return [
                'product_id' => $row->product_id,
                'product_name' => $row->product_name,
                'product_item_id' => $row->product_item_id,
                'sku' => $row->sku,
                'variant_group' => $row->variant_group_name,
                'total_attached' => (int) $row->total_options,
                'attached_options' => $row->attached_options,
                'message' => "Product item has {$row->total_options} options for group '{$row->variant_group_name}'. An item may have at most 1 option per group.",
            ];
        })->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function checkOrphanPivots(string $productId): array
    {
        $query = DB::table('product_item_variant_group_options as piv')
            ->leftJoin('product_items as pi', 'pi.id', '=', 'piv.product_item_id')
            ->leftJoin('variant_group_options as vgo', 'vgo.id', '=', 'piv.variant_group_option_id')
            ->whereNull('pi.id')
            ->orWhereNull('vgo.id')
            ->select(
                'piv.product_item_id',
                'piv.variant_group_option_id',
                DB::raw('CASE WHEN pi.id IS NULL THEN true ELSE false END as missing_item'),
                DB::raw('CASE WHEN vgo.id IS NULL THEN true ELSE false END as missing_option')
            );

        if (! empty($productId)) {
            $query->where('piv.product_item_id', $productId);
        }

        return $query->limit(50)->get()->map(function ($row) {
            return [
                'product_item_id' => $row->product_item_id,
                'variant_group_option_id' => $row->variant_group_option_id,
                'missing_item' => (bool) $row->missing_item,
                'missing_option' => (bool) $row->missing_option,
                'message' => 'Orphan pivot row found referencing non-existent item or option.',
            ];
        })->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function checkCombinationSync(string $productId): array
    {
        $query = DB::table('product_items as pi')
            ->join('products as p', 'p.id', '=', 'pi.product_id')
            ->where('p.has_variant', true)
            ->where(function ($q) {
                $q->whereNull('pi.variant_combination')
                    ->orWhere('pi.variant_combination', '');
            })
            ->whereNull('pi.deleted_at')
            ->whereNull('p.deleted_at')
            ->select('pi.id as product_item_id', 'pi.sku', 'p.id as product_id', 'p.name as product_name');

        if (! empty($productId)) {
            $query->where(function ($q) use ($productId) {
                $q->where('p.id', $productId)
                    ->orWhere('pi.sku', $productId);
            });
        }

        return $query->limit(50)->get()->map(function ($row) {
            return [
                'product_id' => $row->product_id,
                'product_name' => $row->product_name,
                'product_item_id' => $row->product_item_id,
                'sku' => $row->sku,
                'message' => 'Product item belongs to a multi-variant product but has null or empty variant_combination.',
            ];
        })->all();
    }
}
