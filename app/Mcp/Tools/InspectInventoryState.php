<?php

declare(strict_types=1);

namespace App\Mcp\Tools;

use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryCostLayer;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryMovement;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Facades\Schema;
use Laravel\Mcp\Request;
use Laravel\Mcp\Response;
use Laravel\Mcp\Server\Attributes\Description;
use Laravel\Mcp\Server\Tool;

#[Description('Inspects real-time inventory balances, FIFO cost layer queues, latest immutable ledger movements, and stock freeze status for an inventory item.')]
class InspectInventoryState extends Tool
{
    protected string $name = 'inspect_inventory_state';

    protected string $title = 'Inspect Inventory State';

    /**
     * Get the tool's input schema.
     *
     * @return array<string, mixed>
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'item_id' => $schema->string()
                ->description('Inventory Item ID (UUID), SKU, or item name.'),
            'outlet_id' => $schema->string()
                ->description('Optional Outlet ID (UUID) to filter by a specific outlet.'),
            'movement_limit' => $schema->integer()
                ->min(1)
                ->max(20)
                ->description('Number of recent ledger movements to return. Defaults to 5.'),
        ];
    }

    /**
     * Handle the tool request.
     */
    public function handle(Request $request): Response
    {
        $itemId = (string) $request->get('item_id', '');
        $outletId = $request->get('outlet_id');
        $movementLimit = (int) $request->get('movement_limit', 5);

        if (empty($itemId)) {
            return Response::json([
                'error' => 'Item ID, SKU, or name is required.',
            ]);
        }

        if (! Schema::hasTable('inventory_items')) {
            return Response::json([
                'error' => 'inventory_items table does not exist in the current database.',
            ]);
        }

        /** @var InventoryItem|null $item */
        $item = InventoryItem::query()
            ->where('id', $itemId)
            ->orWhere('sku', $itemId)
            ->orWhere('name', $itemId)
            ->with(['unit', 'category'])
            ->first();

        if (! $item) {
            return Response::json([
                'error' => "Inventory item [{$itemId}] not found.",
            ]);
        }

        // Query Balances
        $balanceQuery = InventoryBalance::query()
            ->where('inventory_item_id', $item->id)
            ->with('outlet');

        if ($outletId) {
            $balanceQuery->where('outlet_id', $outletId);
        }

        $balances = $balanceQuery->get()->map(function (InventoryBalance $bal) {
            return [
                'outlet_id' => $bal->outlet_id,
                'outlet_name' => $bal->outlet?->name,
                'current_stock' => (float) $bal->current_stock,
                'average_cost' => (float) $bal->average_cost,
                'last_cost' => (float) $bal->last_cost,
                'total_value' => (float) $bal->total_value,
                'is_stock_frozen' => (bool) ($bal->outlet?->is_stock_frozen ?? false),
            ];
        })->values()->all();

        // Query Active FIFO Cost Layers
        $layerQuery = InventoryCostLayer::query()
            ->where('inventory_item_id', $item->id)
            ->where('qty_remaining', '>', 0)
            ->orderBy('created_at', 'asc');

        if ($outletId) {
            $layerQuery->where('outlet_id', $outletId);
        }

        $costLayers = $layerQuery->get()->map(function (InventoryCostLayer $layer) {
            return [
                'id' => $layer->id,
                'outlet_id' => $layer->outlet_id,
                'batch_date' => $layer->created_at?->toIso8601String(),
                'purchase_price' => (float) $layer->purchase_price,
                'qty_purchased' => (float) $layer->qty_purchased,
                'qty_remaining' => (float) $layer->qty_remaining,
                'total_layer_value' => (float) ($layer->qty_remaining * $layer->purchase_price),
                'reference_type' => $layer->reference_type ? class_basename($layer->reference_type) : null,
                'reference_id' => $layer->reference_id,
            ];
        })->values()->all();

        // Query Immutable Ledger Movements
        $movementQuery = InventoryMovement::query()
            ->where('inventory_item_id', $item->id)
            ->latest('created_at')
            ->limit($movementLimit);

        if ($outletId) {
            $movementQuery->where('outlet_id', $outletId);
        }

        $movements = $movementQuery->get()->map(function (InventoryMovement $mov) {
            return [
                'id' => $mov->id,
                'outlet_id' => $mov->outlet_id,
                'timestamp' => $mov->created_at?->toIso8601String(),
                'movement_type' => $mov->movement_type instanceof \BackedEnum ? $mov->movement_type->value : $mov->movement_type,
                'qty_change' => (float) $mov->qty_change,
                'stock_before' => (float) $mov->stock_before,
                'stock_after' => (float) $mov->stock_after,
                'unit_cost' => (float) $mov->unit_cost,
                'total_cost' => (float) $mov->total_cost,
                'balance_value_after' => (float) $mov->balance_value_after,
                'reference_type' => $mov->reference_type ? class_basename($mov->reference_type) : null,
                'reference_id' => $mov->reference_id,
            ];
        })->values()->all();

        return Response::json([
            'item' => [
                'id' => $item->id,
                'name' => $item->name,
                'sku' => $item->sku,
                'barcode' => $item->barcode,
                'unit' => $item->unit?->name,
                'category' => $item->category?->name,
                'min_stock' => (float) $item->min_stock,
                'is_active' => (bool) $item->is_active,
            ],
            'balances' => $balances,
            'active_fifo_layers' => [
                'count' => count($costLayers),
                'total_fifo_stock' => array_sum(array_column($costLayers, 'qty_remaining')),
                'total_fifo_value' => array_sum(array_column($costLayers, 'total_layer_value')),
                'layers' => $costLayers,
            ],
            'recent_movements' => $movements,
        ]);
    }
}
