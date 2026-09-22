<?php

namespace App\Services\App\Inventory;

use App\Enums\AdjustmentReason;
use App\Enums\AdjustmentStatus;
use App\Enums\InventoryMovementType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\StockAdjustment;
use App\Models\User;
use App\Services\App\Master\ActivityLogService;
use Illuminate\Support\Facades\DB;

class StockAdjustmentService
{
    public function __construct(
        protected ActivityLogService $activityLogService,
        protected InventoryCostingService $costingService
    ) {}

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
                    'description' => $itemData['description'] ?? '',
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

        if (! $user->can('business.*') && $adjustment->created_by === $user->id) {
            throw new \Exception('Anda tidak dapat menyetujui penyesuaian yang Anda buat sendiri.');
        }

        return DB::transaction(function () use ($adjustment, $user) {
            $adjustment->load(['outlet.business', 'items.inventoryItem']);
            $outlet = $adjustment->outlet;
            $business = $outlet?->business ?? $user->business;

            foreach ($adjustment->items as $item) {
                $qtyChange = (float) $item->qty_change;
                $invItem = $item->inventoryItem;

                $balance = InventoryBalance::firstOrCreate([
                    'business_id' => $adjustment->business_id,
                    'outlet_id' => $adjustment->outlet_id,
                    'inventory_item_id' => $item->inventory_item_id,
                ], [
                    'current_stock' => 0,
                ]);

                $stockBefore = (float) $balance->current_stock;
                $stockAfter = $stockBefore + $qtyChange;

                if ($stockAfter < 0) {
                    throw new \Exception("Stok tidak mencukupi untuk item {$invItem->name}. Stok saat ini: {$stockBefore}, perubahan: {$qtyChange}.");
                }

                $item->update([
                    'stock_before' => $stockBefore,
                    'stock_after' => $stockAfter,
                ]);

                if ($qtyChange > 0) {
                    $unitCost = (float) ($item->unit_cost ?? $balance->average_cost ?? $balance->last_cost ?? 0);
                    $movementType = $item->movement_type instanceof InventoryMovementType
                        ? ($item->movement_type === InventoryMovementType::Waste ? InventoryMovementType::Waste : InventoryMovementType::AdjustmentIn)
                        : InventoryMovementType::AdjustmentIn;

                    $this->costingService->recordIncomingStock(
                        business: $business,
                        outlet: $outlet,
                        item: $invItem,
                        qty: $qtyChange,
                        unitCost: $unitCost,
                        movementType: $movementType,
                        reference: $adjustment,
                        description: $item->description ?: ('Penyesuaian Masuk ('.$adjustment->adjustment_number.')'),
                        user: $user
                    );
                } elseif ($qtyChange < 0) {
                    $movementType = $item->movement_type instanceof InventoryMovementType
                        ? ($item->movement_type === InventoryMovementType::Waste ? InventoryMovementType::Waste : InventoryMovementType::AdjustmentOut)
                        : InventoryMovementType::AdjustmentOut;

                    $this->costingService->recordOutgoingStock(
                        business: $business,
                        outlet: $outlet,
                        item: $invItem,
                        qty: abs($qtyChange),
                        movementType: $movementType,
                        reference: $adjustment,
                        description: $item->description ?: ('Penyesuaian Keluar ('.$adjustment->adjustment_number.')'),
                        user: $user
                    );
                }
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
            $adjustment->load(['outlet.business', 'items.inventoryItem']);
            $outlet = $adjustment->outlet;
            $business = $outlet?->business ?? $user->business;

            foreach ($adjustment->items as $item) {
                $qtyChange = (float) $item->qty_change;
                $invItem = $item->inventoryItem;

                // Reversal movement: opposite of original qty_change
                if ($qtyChange > 0) {
                    $this->costingService->recordOutgoingStock(
                        business: $business,
                        outlet: $outlet,
                        item: $invItem,
                        qty: $qtyChange,
                        movementType: InventoryMovementType::AdjustmentOut,
                        reference: $adjustment,
                        description: 'Void Penyesuaian Masuk ('.$adjustment->adjustment_number.')',
                        user: $user
                    );
                } elseif ($qtyChange < 0) {
                    $unitCost = (float) ($item->unit_cost ?? $invItem->balances()->where('outlet_id', $outlet->id)->value('average_cost') ?? 0);
                    $this->costingService->recordIncomingStock(
                        business: $business,
                        outlet: $outlet,
                        item: $invItem,
                        qty: abs($qtyChange),
                        unitCost: $unitCost,
                        movementType: InventoryMovementType::AdjustmentIn,
                        reference: $adjustment,
                        description: 'Void Penyesuaian Keluar ('.$adjustment->adjustment_number.')',
                        user: $user
                    );
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
