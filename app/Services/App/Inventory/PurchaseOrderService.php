<?php

namespace App\Services\App\Inventory;

use App\Enums\GoodsReceiptStatus;
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
     * Buat Purchase Order baru (Draf atau Dipesan).
     */
    public function createPO(array $data, User $creator): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $creator) {
            $data['business_id'] = $creator->business_id;
            $data['created_by'] = $creator->id;

            $count = PurchaseOrder::query()
                ->where('business_id', $creator->business_id)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            $seq = $count + 1;
            do {
                $poNumber = 'PO-'.now()->format('Ym').'-'.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
                $seq++;
            } while (PurchaseOrder::query()->where('business_id', $creator->business_id)->where('po_number', $poNumber)->exists());

            $data['po_number'] = $poNumber;
            $data['status'] = $data['status'] ?? PurchaseOrderStatus::Draft;

            $totalAmount = 0;
            $items = $data['items'] ?? [];

            $po = PurchaseOrder::create($data);

            foreach ($items as $itemData) {
                $qty = (float) $itemData['qty_ordered'];
                $price = (float) $itemData['purchase_price'];
                $discount = (float) ($itemData['discount_amount'] ?? 0);
                $tax = (float) ($itemData['tax_amount'] ?? 0);
                $subtotal = max(0, ($qty * $price) - $discount + $tax);
                $totalAmount += $subtotal;

                $po->items()->create([
                    'inventory_item_id' => $itemData['inventory_item_id'],
                    'uom_id' => $itemData['uom_id'] ?? null,
                    'qty_ordered' => $qty,
                    'qty_received' => 0,
                    'purchase_price' => $price,
                    'discount_amount' => $discount,
                    'tax_amount' => $tax,
                    'subtotal' => $subtotal,
                ]);
            }

            $po->update(['total_amount' => $totalAmount]);

            $this->activityLogService->log($po, 'created', $creator);

            return $po;
        });
    }

    /**
     * Update Purchase Order (hanya jika berstatus Draft).
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
                    $qty = (float) $itemData['qty_ordered'];
                    $price = (float) $itemData['purchase_price'];
                    $discount = (float) ($itemData['discount_amount'] ?? 0);
                    $tax = (float) ($itemData['tax_amount'] ?? 0);
                    $subtotal = max(0, ($qty * $price) - $discount + $tax);
                    $totalAmount += $subtotal;

                    $po->items()->create([
                        'inventory_item_id' => $itemData['inventory_item_id'],
                        'uom_id' => $itemData['uom_id'] ?? null,
                        'qty_ordered' => $qty,
                        'qty_received' => 0,
                        'purchase_price' => $price,
                        'discount_amount' => $discount,
                        'tax_amount' => $tax,
                        'subtotal' => $subtotal,
                    ]);
                }

                $po->update(['total_amount' => $totalAmount]);
            }

            $this->activityLogService->log($po, 'updated', $updater);

            return $po;
        });
    }

    /**
     * Kunci draf PO menjadi pesanan resmi (Ordered).
     */
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

    /**
     * Batalkan pesanan PO yang belum diterima.
     */
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
     * Alur Pembelian Langsung (Direct / Quick Purchase) satu langkah instan.
     */
    public function directPurchase(array $data, User $user): PurchaseOrder
    {
        return DB::transaction(function () use ($data, $user) {
            // 1. Buat PO dengan status Ordered
            $data['status'] = PurchaseOrderStatus::Ordered;
            $po = $this->createPO($data, $user);

            // 2. Siapkan item untuk penerimaan fisik barang
            $receiptItems = [];
            foreach ($po->items as $index => $poItem) {
                $itemInput = $data['items'][$index] ?? [];
                $receiptItems[] = [
                    'id' => $poItem->id,
                    'purchase_order_item_id' => $poItem->id,
                    'qty_received' => $itemInput['qty_received'] ?? $poItem->qty_ordered,
                    'conversion_factor' => $itemInput['conversion_factor'] ?? 1.0,
                    'uom_id' => $itemInput['uom_id'] ?? $poItem->uom_id,
                ];
            }

            $receiptData = [
                'delivery_order_number' => $data['delivery_order_number'] ?? $data['reference_number'] ?? null,
                'received_at' => $data['received_at'] ?? now(),
                'notes' => $data['notes'] ?? 'Pembelian langsung (Direct Purchase)',
                'items' => $receiptItems,
            ];

            // 3. Eksekusi penerimaan barang dan pembentukan mutasi serta layer FIFO
            app(GoodsReceiptService::class)->createReceipt($po, $receiptData, $user);

            return $po->fresh(['items', 'goodsReceipts']);
        });
    }

    /**
     * Proses penerimaan barang untuk PO (didelegasikan ke GoodsReceiptService).
     */
    public function receivePO(PurchaseOrder $po, array $receivedData, User $receiver): PurchaseOrder
    {
        app(GoodsReceiptService::class)->createReceipt($po, $receivedData, $receiver);

        return $po->fresh(['items', 'goodsReceipts']);
    }

    /**
     * Void seluruh penerimaan pada PO.
     */
    public function void(PurchaseOrder $po, User $voider): PurchaseOrder
    {
        return DB::transaction(function () use ($po, $voider) {
            if ($po->status !== PurchaseOrderStatus::Received && $po->status !== PurchaseOrderStatus::PartialReceived) {
                abort(403, 'Hanya PO berstatus Diterima yang dapat di-void.');
            }

            $receipts = $po->goodsReceipts()->where('status', GoodsReceiptStatus::Completed)->get();
            $goodsReceiptService = app(GoodsReceiptService::class);

            if ($receipts->isNotEmpty()) {
                foreach ($receipts as $receipt) {
                    $goodsReceiptService->voidReceipt($receipt, $voider);
                }
            } else {
                // Fallback untuk data PO historis
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

                        InventoryCostLayer::query()
                            ->where('reference_id', $po->id)
                            ->where('inventory_item_id', $poItem->inventory_item_id)
                            ->delete();
                    }
                }
            }

            $po->status = PurchaseOrderStatus::Cancelled;
            $po->save();

            $this->activityLogService->log($po, 'voided', $voider);

            return $po;
        });
    }
}
