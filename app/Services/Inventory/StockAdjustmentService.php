<?php

namespace App\Services\Inventory;

use App\Enums\InventoryMovementType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryMovement;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StockAdjustmentService
{
    public function createAdjustment(array $data, User $creator): InventoryMovement
    {
        return DB::transaction(function () use ($data, $creator) {
            $balance = InventoryBalance::firstOrCreate([
                'business_id'       => $creator->business_id,
                'outlet_id'         => $data['outlet_id'],
                'inventory_item_id' => $data['inventory_item_id'],
            ], ['current_stock' => 0]);

            $stockBefore = $balance->current_stock;
            $qtyChange   = (float) $data['qty_change'];
            $stockAfter  = $stockBefore + $qtyChange;

            $balance->update(['current_stock' => $stockAfter]);

            // Ensure the passed movement_type is mapped correctly or defaults to Adjustment
            $type = match ($data['movement_type']) {
                'waste'      => InventoryMovementType::Waste,
                'correction' => InventoryMovementType::Adjustment,
                'expired'    => InventoryMovementType::Waste, // Treating expired as waste for now
                default      => InventoryMovementType::Adjustment,
            };

            $movement = InventoryMovement::create([
                'business_id'       => $creator->business_id,
                'outlet_id'         => $data['outlet_id'],
                'inventory_item_id' => $data['inventory_item_id'],
                'movement_type'     => $type,
                'qty_change'        => $qtyChange,
                'stock_before'      => $stockBefore,
                'stock_after'       => $stockAfter,
                'description'       => $data['description'],
                'created_by'        => $creator->id,
                'created_at'        => now(),
            ]);

            return $movement;
        });
    }
}
