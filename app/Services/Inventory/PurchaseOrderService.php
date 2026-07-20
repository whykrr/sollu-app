<?php

namespace App\Services\Inventory;

use App\Enums\InventoryMovementType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryCostLayer;
use App\Models\Inventory\InventoryMovement;
use App\Models\Inventory\PurchaseOrder;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    /**
     * Create a new Purchase Order.
     *
     * @param array $data
     * @param User $creator
     * @return PurchaseOrder
     */
    public function createPO(array $data, User $creator): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $creator) {
            $data['business_id'] = $creator->business_id;
            $data['created_by']  = $creator->id;
            
            // Generate simple PO number (in real app, use generator)
            $count = PurchaseOrder::where('business_id', $creator->business_id)
                ->whereMonth('created_at', now()->month)
                ->count();
            $data['po_number'] = 'PO-' . now()->format('Ym') . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
            $data['status']    = 'draft';

            $totalAmount = 0;
            $items = $data['items'] ?? [];

            $po = PurchaseOrder::create($data);

            foreach ($items as $itemData) {
                $subtotal = $itemData['qty_ordered'] * $itemData['purchase_price'];
                $totalAmount += $subtotal;

                $po->items()->create([
                    'inventory_item_id' => $itemData['inventory_item_id'],
                    'qty_ordered'       => $itemData['qty_ordered'],
                    'qty_received'      => 0,
                    'purchase_price'    => $itemData['purchase_price'],
                    'subtotal'          => $subtotal,
                ]);
            }

            $po->update(['total_amount' => $totalAmount]);

            return $po;
        });
    }

    /**
     * Update an existing Purchase Order (only if draft).
     *
     * @param PurchaseOrder $po
     * @param array $data
     * @return PurchaseOrder
     */
    public function updatePO(PurchaseOrder $po, array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $data) {
            if ($po->status !== 'draft') {
                abort(403, 'Hanya PO berstatus Draft yang dapat diubah.');
            }

            $po->update($data);

            if (isset($data['items'])) {
                $po->items()->delete(); // recreate items for simplicity
                $totalAmount = 0;

                foreach ($data['items'] as $itemData) {
                    $subtotal = $itemData['qty_ordered'] * $itemData['purchase_price'];
                    $totalAmount += $subtotal;

                    $po->items()->create([
                        'inventory_item_id' => $itemData['inventory_item_id'],
                        'qty_ordered'       => $itemData['qty_ordered'],
                        'qty_received'      => 0,
                        'purchase_price'    => $itemData['purchase_price'],
                        'subtotal'          => $subtotal,
                    ]);
                }

                $po->update(['total_amount' => $totalAmount]);
            }

            return $po;
        });
    }

    /**
     * Process receiving of items for a PO.
     *
     * @param PurchaseOrder $po
     * @param array $receivedData (array of ['id' => item_id, 'qty_received' => amount])
     * @param User $receiver
     * @return PurchaseOrder
     */
    public function receivePO(PurchaseOrder $po, array $receivedData, User $receiver): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $receivedData, $receiver) {
            if (!in_array($po->status, ['draft', 'ordered', 'partial_received'])) {
                abort(403, 'PO ini sudah selesai atau dibatalkan.');
            }

            $itemsMap = collect($receivedData['items'])->keyBy('id');
            $isFullyReceived = true;
            $hasReceivedAnything = false;

            foreach ($po->items as $poItem) {
                if ($itemsMap->has($poItem->id)) {
                    $qtyToReceive = (float) $itemsMap->get($poItem->id)['qty_received'];
                    
                    if ($qtyToReceive > 0) {
                        $hasReceivedAnything = true;
                        
                        // 1. Update PO Item
                        $poItem->qty_received += $qtyToReceive;
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
                        $stockAfter  = $stockBefore + $qtyToReceive;

                        $balance->update(['current_stock' => $stockAfter]);

                        // 3. Create Movement
                        $movement = InventoryMovement::create([
                            'business_id'       => $po->business_id,
                            'outlet_id'         => $po->outlet_id,
                            'inventory_item_id' => $poItem->inventory_item_id,
                            'movement_type'     => InventoryMovementType::Purchase,
                            'qty_change'        => $qtyToReceive,
                            'stock_before'      => $stockBefore,
                            'stock_after'       => $stockAfter,
                            'purchase_price'    => $poItem->purchase_price,
                            'description'       => 'Penerimaan barang dari PO: ' . $po->po_number,
                            'created_by'        => $receiver->id,
                            'created_at'        => now(),
                        ]);

                        // Link polymorphic relation manually
                        $movement->reference_id = $po->id;
                        $movement->reference_type = PurchaseOrder::class;
                        $movement->save();

                        // 4. Create Cost Layer (FIFO)
                        InventoryCostLayer::create([
                            'inventory_item_id' => $poItem->inventory_item_id,
                            'outlet_id'         => $po->outlet_id,
                            'purchase_price'    => $poItem->purchase_price,
                            'qty_purchased'     => $qtyToReceive,
                            'qty_remaining'     => $qtyToReceive,
                            'reference_id'      => $po->id,
                            'created_at'        => now(),
                        ]);
                    }
                }

                if ($poItem->qty_received < $poItem->qty_ordered) {
                    $isFullyReceived = false;
                }
            }

            if ($hasReceivedAnything) {
                $po->status = $isFullyReceived ? 'received' : 'partial_received';
                $po->approved_by = $receiver->id;
                $po->save();
            }

            return $po;
        });
    }
}
