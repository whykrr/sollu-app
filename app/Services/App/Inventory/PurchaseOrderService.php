<?php

namespace App\Services\App\Inventory;

use App\Enums\InventoryMovementType;
use App\Enums\PurchaseOrderStatus;
use App\Models\Inventory\InventoryCostLayer;
use App\Models\Inventory\PurchaseOrder;
use App\Models\User;
use App\Services\App\Master\ActivityLogService;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    public function __construct(
        protected ActivityLogService $activityLogService,
        protected InventoryCostingService $costingService
    ) {}

    /**
     * Create a new Purchase Order.
     */
    public function createPO(array $data, User $creator): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $creator) {
            $data['business_id'] = $creator->business_id;
            $data['created_by'] = $creator->id;

            $count = PurchaseOrder::where('business_id', $creator->business_id)
                ->whereMonth('created_at', now()->month)
                ->count();
            $data['po_number'] = 'PO-'.now()->format('Ym').'-'.str_pad($count + 1, 3, '0', STR_PAD_LEFT);
            $data['status'] = PurchaseOrderStatus::Draft;

            $totalAmount = 0;
            $items = $data['items'] ?? [];

            $po = PurchaseOrder::create($data);

            foreach ($items as $itemData) {
                $subtotal = $itemData['qty_ordered'] * $itemData['purchase_price'];
                $totalAmount += $subtotal;

                $po->items()->create([
                    'inventory_item_id' => $itemData['inventory_item_id'],
                    'uom_id' => $itemData['uom_id'] ?? null,
                    'qty_ordered' => $itemData['qty_ordered'],
                    'qty_received' => 0,
                    'purchase_price' => $itemData['purchase_price'],
                    'subtotal' => $subtotal,
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
            if ($po->status !== PurchaseOrderStatus::Draft) {
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
                        'uom_id' => $itemData['uom_id'] ?? null,
                        'qty_ordered' => $itemData['qty_ordered'],
                        'qty_received' => 0,
                        'purchase_price' => $itemData['purchase_price'],
                        'subtotal' => $subtotal,
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
            if ($po->status !== PurchaseOrderStatus::Draft) {
                abort(403, 'Hanya PO berstatus Draft yang dapat diproses menjadi Ordered.');
            }

            $po->status = PurchaseOrderStatus::Ordered;
            $po->save();

            $this->activityLogService->log($po, 'ordered', $user);

            return $po;
        });
    }

    public function cancel(PurchaseOrder $po, User $user): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $user) {
            if ($po->status !== PurchaseOrderStatus::Ordered) {
                abort(403, 'Hanya PO berstatus Ordered yang dapat dibatalkan.');
            }

            $po->status = PurchaseOrderStatus::Cancelled;
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
            if ($po->status !== PurchaseOrderStatus::Ordered) {
                abort(403, 'Hanya PO berstatus Ordered yang dapat diterima.');
            }

            $po->load(['outlet.business', 'items.inventoryItem']);
            $outlet = $po->outlet;
            $business = $outlet?->business ?? $receiver->business;

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

                        // 2. Cost Calculation
                        $convertedPurchasePrice = $conversionFactor > 0
                            ? $poItem->purchase_price / $conversionFactor
                            : $poItem->purchase_price;

                        // 3. Rekam Stok Masuk, FIFO Layer & Moving Average via Costing Service
                        $this->costingService->recordIncomingStock(
                            business: $business,
                            outlet: $outlet,
                            item: $poItem->inventoryItem,
                            qty: $convertedQty,
                            unitCost: $convertedPurchasePrice,
                            movementType: InventoryMovementType::Purchase,
                            reference: $po,
                            description: 'Penerimaan barang dari PO: '.$po->po_number,
                            user: $receiver
                        );
                    }
                }
            }

            $po->status = PurchaseOrderStatus::Received;
            $po->approved_by = $receiver->id;
            $po->save();

            $this->activityLogService->log($po, 'received', $receiver);

            return $po;
        });
    }

    public function void(PurchaseOrder $po, User $voider): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $voider) {
            if ($po->status !== PurchaseOrderStatus::Received) {
                abort(403, 'Hanya PO berstatus Received yang dapat di-void.');
            }

            $po->load(['outlet.business', 'items.inventoryItem']);
            $outlet = $po->outlet;
            $business = $outlet?->business ?? $voider->business;

            foreach ($po->items as $poItem) {
                if ($poItem->converted_qty > 0) {
                    $this->costingService->recordOutgoingStock(
                        business: $business,
                        outlet: $outlet,
                        item: $poItem->inventoryItem,
                        qty: (float) $poItem->converted_qty,
                        movementType: InventoryMovementType::PurchaseVoid,
                        reference: $po,
                        description: 'Void penerimaan barang dari PO: '.$po->po_number,
                        user: $voider
                    );

                    // Bersihkan layer FIFO yang berasal dari PO ini jika masih tersisa
                    InventoryCostLayer::where('reference_id', $po->id)
                        ->where('inventory_item_id', $poItem->inventory_item_id)
                        ->delete();
                }
            }

            $po->status = PurchaseOrderStatus::Cancelled;
            $po->save();

            $this->activityLogService->log($po, 'voided', $voider);

            return $po;
        });
    }
}
