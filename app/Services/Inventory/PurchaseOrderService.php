<?php

namespace App\Services\Inventory;

use App\Enums\InventoryMovementType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryCostLayer;
use App\Models\Inventory\InventoryMovement;
use App\Models\Inventory\PurchaseOrder;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    public function __construct(
        protected ActivityLogService $activityLogService
    ) {}

    /**
     * Create a new Purchase Order.
     */
    public function createPO(array $data, User $creator): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $creator) {
            $data['business_id'] = $creator->business_id;
            $data['created_by']  = $creator->id;
            
            $count = PurchaseOrder::where('business_id', $creator->business_id)
                ->whereMonth('created_at', now()->month)
                ->count();
            $data['po_number'] = 'PO-' . now()->format('Ym') . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
            $data['status']    = PurchaseOrder::STATUS_DRAFT;

            $totalAmount = 0;
            $items = $data['items'] ?? [];

            $po = PurchaseOrder::create($data);

            foreach ($items as $itemData) {
                $subtotal = $itemData['qty_ordered'] * $itemData['purchase_price'];
                $totalAmount += $subtotal;

                $po->items()->create([
                    'inventory_item_id' => $itemData['inventory_item_id'],
                    'uom_id'            => $itemData['uom_id'] ?? null,
                    'qty_ordered'       => $itemData['qty_ordered'],
                    'qty_received'      => 0,
                    'purchase_price'    => $itemData['purchase_price'],
                    'subtotal'          => $subtotal,
                ]);
            }

            $po->update(['total_amount' => $totalAmount]);

            $this->activityLogService->log($po, 'created', $creator);

            return $po;
        });
    }

    /**
     * Update an existing Purchase Order (only if draft).
     */
    public function updatePO(PurchaseOrder $po, array $data, User $updater): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $data, $updater) {
            if ($po->status !== PurchaseOrder::STATUS_DRAFT) {
                abort(403, 'Hanya PO berstatus Draft yang dapat diubah.');
            }

            $po->update($data);

            if (isset($data['items'])) {
                $po->items()->delete();
                $totalAmount = 0;

                foreach ($data['items'] as $itemData) {
                    $subtotal = $itemData['qty_ordered'] * $itemData['purchase_price'];
                    $totalAmount += $subtotal;

                    $po->items()->create([
                        'inventory_item_id' => $itemData['inventory_item_id'],
                        'uom_id'            => $itemData['uom_id'] ?? null,
                        'qty_ordered'       => $itemData['qty_ordered'],
                        'qty_received'      => 0,
                        'purchase_price'    => $itemData['purchase_price'],
                        'subtotal'          => $subtotal,
                    ]);
                }

                $po->update(['total_amount' => $totalAmount]);
            }

            $this->activityLogService->log($po, 'updated', $updater);

            return $po;
        });
    }

    public function markAsOrdered(PurchaseOrder $po, User $user): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $user) {
            if ($po->status !== PurchaseOrder::STATUS_DRAFT) {
                abort(403, 'Hanya PO berstatus Draft yang dapat diproses menjadi Ordered.');
            }

            $po->status = PurchaseOrder::STATUS_ORDERED;
            $po->save();

            $this->activityLogService->log($po, 'ordered', $user);

            return $po;
        });
    }

    public function cancel(PurchaseOrder $po, User $user): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $user) {
            if ($po->status !== PurchaseOrder::STATUS_ORDERED) {
                abort(403, 'Hanya PO berstatus Ordered yang dapat dibatalkan.');
            }

            $po->status = PurchaseOrder::STATUS_CANCELLED;
            $po->save();

            $this->activityLogService->log($po, 'cancelled', $user);

            return $po;
        });
    }

    /**
     * Process receiving of items for a PO with dynamic conversion.
     */
    public function receivePO(PurchaseOrder $po, array $receivedData, User $receiver): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $receivedData, $receiver) {
            if ($po->status !== PurchaseOrder::STATUS_ORDERED) {
                abort(403, 'Hanya PO berstatus Ordered yang dapat diterima.');
            }

            $itemsMap = collect($receivedData['items'])->keyBy('id');

            foreach ($po->items as $poItem) {
                if ($itemsMap->has($poItem->id)) {
                    $input = $itemsMap->get($poItem->id);
                    $qtyToReceive = (float) $input['qty_received'];
                    $conversionFactor = (float) ($input['conversion_factor'] ?? 1.0);
                    $convertedQty = $qtyToReceive * $conversionFactor;
                    
                    if ($qtyToReceive > 0) {
                        // 1. Update PO Item
                        $poItem->qty_received = $qtyToReceive;
                        $poItem->conversion_factor = $conversionFactor;
                        $poItem->converted_qty = $convertedQty;
                        $poItem->save();

                        // 2. Update Balance
                        $balance = InventoryBalance::firstOrCreate([
                            'business_id'       => $po->business_id,
                            'outlet_id'         => $po->outlet_id,
                            'inventory_item_id' => $poItem->inventory_item_id,
                        ], [
                            'current_stock' => 0
                        ]);

                        $stockBefore = $balance->current_stock;
                        $stockAfter  = $stockBefore + $convertedQty;
                        $balance->update(['current_stock' => $stockAfter]);

                        // 3. Cost Calculation
                        $convertedPurchasePrice = $conversionFactor > 0 
                            ? $poItem->purchase_price / $conversionFactor 
                            : $poItem->purchase_price;

                        // 4. Create Movement
                        $movement = InventoryMovement::create([
                            'business_id'       => $po->business_id,
                            'outlet_id'         => $po->outlet_id,
                            'inventory_item_id' => $poItem->inventory_item_id,
                            'movement_type'     => InventoryMovementType::Purchase,
                            'qty_change'        => $convertedQty,
                            'stock_before'      => $stockBefore,
                            'stock_after'       => $stockAfter,
                            'purchase_price'    => $convertedPurchasePrice,
                            'description'       => 'Penerimaan barang dari PO: ' . $po->po_number,
                            'created_by'        => $receiver->id,
                            'created_at'        => now(),
                        ]);

                        $movement->reference_id = $po->id;
                        $movement->reference_type = PurchaseOrder::class;
                        $movement->save();

                        // 5. Create Cost Layer (FIFO)
                        InventoryCostLayer::create([
                            'inventory_item_id' => $poItem->inventory_item_id,
                            'outlet_id'         => $po->outlet_id,
                            'purchase_price'    => $convertedPurchasePrice,
                            'qty_purchased'     => $convertedQty,
                            'qty_remaining'     => $convertedQty,
                            'reference_id'      => $po->id,
                            'created_at'        => now(),
                        ]);
                    }
                }
            }

            $po->status = PurchaseOrder::STATUS_RECEIVED;
            $po->approved_by = $receiver->id;
            $po->save();

            $this->activityLogService->log($po, 'received', $receiver);

            return $po;
        });
    }

    public function void(PurchaseOrder $po, User $voider): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $voider) {
            if ($po->status !== PurchaseOrder::STATUS_RECEIVED) {
                abort(403, 'Hanya PO berstatus Received yang dapat di-void.');
            }

            foreach ($po->items as $poItem) {
                if ($poItem->converted_qty > 0) {
                    $balance = InventoryBalance::where([
                        'business_id'       => $po->business_id,
                        'outlet_id'         => $po->outlet_id,
                        'inventory_item_id' => $poItem->inventory_item_id,
                    ])->first();

                    if ($balance) {
                        $stockBefore = $balance->current_stock;
                        $stockAfter  = $stockBefore - $poItem->converted_qty;
                        $balance->update(['current_stock' => $stockAfter]);

                        $movement = InventoryMovement::create([
                            'business_id'       => $po->business_id,
                            'outlet_id'         => $po->outlet_id,
                            'inventory_item_id' => $poItem->inventory_item_id,
                            'movement_type'     => InventoryMovementType::PurchaseVoid,
                            'qty_change'        => -$poItem->converted_qty,
                            'stock_before'      => $stockBefore,
                            'stock_after'       => $stockAfter,
                            'purchase_price'    => 0,
                            'description'       => 'Void penerimaan barang dari PO: ' . $po->po_number,
                            'created_by'        => $voider->id,
                            'created_at'        => now(),
                        ]);

                        $movement->reference_id = $po->id;
                        $movement->reference_type = PurchaseOrder::class;
                        $movement->save();
                    }

                    // Remove cost layer
                    InventoryCostLayer::where('reference_id', $po->id)
                        ->where('inventory_item_id', $poItem->inventory_item_id)
                        ->delete();
                }
            }

            $po->status = PurchaseOrder::STATUS_CANCELLED;
            $po->save();

            $this->activityLogService->log($po, 'voided', $voider);

            return $po;
        });
    }
}
