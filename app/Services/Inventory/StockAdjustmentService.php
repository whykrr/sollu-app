<?php

namespace App\Services\Inventory;

use App\Enums\AdjustmentReason;
use App\Enums\AdjustmentStatus;
use App\Enums\InventoryMovementType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\StockAdjustment;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;

class StockAdjustmentService
{
    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Create a new draft stock adjustment.
     */
    public function create(array $data, User $user): StockAdjustment
    {
        return DB::transaction(function () use ($data, $user) {
            $adjustment = StockAdjustment::create([
                'business_id' => $user->business_id,
                'outlet_id' => $data['outlet_id'],
                'adjustment_number' => $this->generateAdjustmentNumber(),
                'status' => AdjustmentStatus::Draft,
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            foreach ($data['items'] as $itemData) {
                $reasonEnum = AdjustmentReason::from($data['reason']);
                $movementType = in_array($reasonEnum, [AdjustmentReason::Waste, AdjustmentReason::Expired])
                    ? InventoryMovementType::Waste
                    : InventoryMovementType::Adjustment;

                $adjustment->items()->create([
                    'inventory_item_id' => $itemData['inventory_item_id'],
                    'movement_type' => $movementType,
                    'qty_change' => $itemData['qty_change'],
                    'unit_cost' => (isset($itemData['unit_cost']) && $itemData['qty_change'] > 0)
                                            ? $itemData['unit_cost']
                                            : null,
                    'description' => $itemData['description'],
                ]);
            }

            $this->activityLogService->log($adjustment, 'created', $user);

            return $adjustment;
        });
    }

    /**
     * Approve a draft stock adjustment.
     */
    public function approve(StockAdjustment $adjustment, User $user): StockAdjustment
    {
        if ($adjustment->status !== AdjustmentStatus::Draft) {
            throw new \Exception('Hanya penyesuaian berstatus Draf yang dapat disetujui.');
        }

        return DB::transaction(function () use ($adjustment, $user) {
            foreach ($adjustment->items as $item) {
                $balance = InventoryBalance::firstOrCreate(
                    [
                        'business_id' => $adjustment->business_id,
                        'outlet_id' => $adjustment->outlet_id,
                        'inventory_item_id' => $item->inventory_item_id,
                    ],
                    [
                        'current_stock' => 0,
                    ]
                );

                $stockBefore = $balance->current_stock;
                $stockAfter = $stockBefore + $item->qty_change;

                // don't allow negative stock (can be configured)
                if ($stockAfter < 0) {
                    throw new \Exception("Stok tidak mencukupi untuk item {$item->inventoryItem->name}. Stok saat ini: {$stockBefore}, perubahan: {$item->qty_change}.");
                }

                $balance->update(['current_stock' => $stockAfter]);

                $item->update([
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                ]);

                $cost = 0;
                if ($item->qty_change > 0) {
                    // In: Use manual unit_cost if provided, otherwise moving average (we can assume 0 or fetch from inventoryItem if it had moving avg)
                    $cost = $item->unit_cost ?? 0;
                } else {
                    // Out: Use moving average (assume 0 for now)
                    $cost = 0;
                }

                $adjustment->inventoryMovements()->create([
                    'business_id' => $adjustment->business_id,
                    'outlet_id' => $adjustment->outlet_id,
                    'inventory_item_id' => $item->inventory_item_id,
                    'movement_type' => $item->movement_type,
                    'qty_change' => $item->qty_change,
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                    'cost' => $cost,
                    'description' => $item->description,
                    'created_by' => $user->id,
                    'created_at' => now(),
                ]);
            }

            $adjustment->update([
                'status' => AdjustmentStatus::Approved,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            $this->activityLogService->log($adjustment, 'approved', $user);

            return $adjustment;
        });
    }

    /**
     * Reject a draft stock adjustment.
     */
    public function reject(StockAdjustment $adjustment, string $notes, User $user): StockAdjustment
    {
        if ($adjustment->status !== AdjustmentStatus::Draft) {
            throw new \Exception('Hanya penyesuaian berstatus Draf yang dapat ditolak.');
        }

        return DB::transaction(function () use ($adjustment, $notes, $user) {
            $adjustment->update([
                'status' => AdjustmentStatus::Rejected,
                'notes' => $notes,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            $this->activityLogService->log($adjustment, 'rejected', $user);

            return $adjustment;
        });
    }

    /**
     * Void an approved stock adjustment.
     */
    public function void(StockAdjustment $adjustment, User $user): StockAdjustment
    {
        if ($adjustment->status !== AdjustmentStatus::Approved) {
            throw new \Exception('Hanya penyesuaian berstatus Disetujui yang dapat dibatalkan.');
        }

        return DB::transaction(function () use ($adjustment, $user) {
            foreach ($adjustment->items as $item) {
                $balance = InventoryBalance::where('outlet_id', $adjustment->outlet_id)
                    ->where('inventory_item_id', $item->inventory_item_id)
                    ->first();

                if ($balance) {
                    $stockBefore = $balance->current_stock;
                    // Reversal movement: opposite of original qty_change
                    $reversalQty = -$item->qty_change;
                    $stockAfter = $stockBefore + $reversalQty;

                    $balance->update(['current_stock' => $stockAfter]);

                    $adjustment->inventoryMovements()->create([
                        'business_id' => $adjustment->business_id,
                        'outlet_id' => $adjustment->outlet_id,
                        'inventory_item_id' => $item->inventory_item_id,
                        'movement_type' => $item->movement_type,
                        'qty_change' => $reversalQty,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'cost' => 0, // Void reversal inherits cost 0 or previous
                        'description' => 'Void: '.$item->description,
                        'created_by' => $user->id,
                        'created_at' => now(),
                    ]);
                }
            }

            $adjustment->update([
                'status' => AdjustmentStatus::Voided,
            ]);

            $this->activityLogService->log($adjustment, 'voided', $user);

            return $adjustment;
        });
    }

    /**
     * Generate unique adjustment number.
     */
    protected function generateAdjustmentNumber(): string
    {
        $prefix = 'ADJ-'.now()->format('Ymd').'-';
        $latest = StockAdjustment::where('adjustment_number', 'like', "{$prefix}%")
            ->orderBy('adjustment_number', 'desc')
            ->first();

        if ($latest) {
            $sequence = (int) str_replace($prefix, '', $latest->adjustment_number);
            $nextSequence = str_pad($sequence + 1, 3, '0', STR_PAD_LEFT);
        } else {
            $nextSequence = '001';
        }

        return $prefix.$nextSequence;
    }
}
