<?php

namespace App\Services\App\Inventory;

use App\Enums\GoodsReceiptStatus;
use App\Enums\InventoryMovementType;
use App\Enums\PurchaseOrderStatus;
use App\Models\Inventory\GoodsReceipt;
use App\Models\Inventory\InventoryCostLayer;
use App\Models\Inventory\PurchaseOrder;
use App\Models\User;
use App\Services\App\Master\ActivityLogService;
use Illuminate\Support\Facades\DB;

class GoodsReceiptService
{
    public function __construct(
        protected ActivityLogService $activityLogService,
        protected InventoryCostingService $costingService
    ) {}

    /**
     * Catat penerimaan fisik barang untuk suatu Purchase Order (mendukung penerimaan sebagian / bertahap).
     */
    public function createReceipt(PurchaseOrder $po, array $data, User $receiver): GoodsReceipt
    {
        return DB::transaction(function () use ($po, $data, $receiver) {
            if (! in_array($po->status, [PurchaseOrderStatus::Ordered, PurchaseOrderStatus::PartialReceived], true)) {
                abort(403, 'Hanya pesanan berstatus Dipesan atau Diterima Sebagian yang dapat diproses penerimaannya.');
            }

            $po->load(['outlet.business', 'items.inventoryItem', 'supplier']);
            $outlet = $po->outlet;
            $business = $outlet?->business ?? $receiver->business;

            // Generate nomor penerimaan unik (GR-YYYYMM-XXX)
            $count = GoodsReceipt::query()
                ->where('business_id', $business->id)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            $seq = $count + 1;
            do {
                $receiptNumber = 'GR-'.now()->format('Ym').'-'.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
                $seq++;
            } while (GoodsReceipt::query()->where('business_id', $business->id)->where('receipt_number', $receiptNumber)->exists());

            $receipt = GoodsReceipt::create([
                'business_id' => $business->id,
                'outlet_id' => $outlet->id,
                'purchase_order_id' => $po->id,
                'receipt_number' => $receiptNumber,
                'delivery_order_number' => $data['delivery_order_number'] ?? null,
                'received_at' => $data['received_at'] ?? now(),
                'status' => GoodsReceiptStatus::Completed,
                'notes' => $data['notes'] ?? null,
                'received_by' => $receiver->id,
            ]);

            // Index input items berdasarkan id baris PO
            $inputs = collect($data['items'] ?? [])->keyBy(function ($item) {
                return $item['purchase_order_item_id'] ?? $item['id'] ?? null;
            });

            foreach ($po->items as $poItem) {
                if (! $inputs->has($poItem->id)) {
                    continue;
                }

                $input = $inputs->get($poItem->id);
                $receivedPurchaseQty = (float) ($input['qty_received'] ?? $input['received_purchase_qty'] ?? 0);

                if ($receivedPurchaseQty <= 0) {
                    continue;
                }

                $conversionFactor = (float) ($input['conversion_factor'] ?? $poItem->conversion_factor ?? 1.0);
                if ($conversionFactor <= 0) {
                    $conversionFactor = 1.0;
                }

                $receivedInventoryQty = $receivedPurchaseQty * $conversionFactor;

                // Kalkulasi total biaya dan HPP per unit stok persediaan (PRD Formula)
                $unitPurchasePrice = (float) $poItem->purchase_price;
                $qtyOrdered = (float) $poItem->qty_ordered;
                $discountAmount = (float) ($poItem->discount_amount ?? 0);
                $taxAmount = (float) ($poItem->tax_amount ?? 0);

                $proportionalDiscount = ($qtyOrdered > 0)
                    ? ($discountAmount / $qtyOrdered) * $receivedPurchaseQty
                    : 0;

                $proportionalTax = ($qtyOrdered > 0)
                    ? ($taxAmount / $qtyOrdered) * $receivedPurchaseQty
                    : 0;

                $totalCost = max(0, ($receivedPurchaseQty * $unitPurchasePrice) - $proportionalDiscount + $proportionalTax);
                $unitCost = ($receivedInventoryQty > 0) ? ($totalCost / $receivedInventoryQty) : 0;

                // 1. Catat Goods Receipt Item
                $receipt->items()->create([
                    'purchase_order_item_id' => $poItem->id,
                    'inventory_item_id' => $poItem->inventory_item_id,
                    'uom_id' => $input['uom_id'] ?? $poItem->uom_id,
                    'received_purchase_qty' => $receivedPurchaseQty,
                    'conversion_factor' => $conversionFactor,
                    'received_inventory_qty' => $receivedInventoryQty,
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                ]);

                // 2. Akumulasi kuantitas diterima pada baris PO
                $poItem->qty_received = (float) $poItem->qty_received + $receivedPurchaseQty;
                $poItem->conversion_factor = $conversionFactor;
                $poItem->converted_qty = (float) $poItem->converted_qty + $receivedInventoryQty;
                $poItem->save();

                // 3. Masukkan stok, perbarui layer FIFO dan moving average
                $this->costingService->recordIncomingStock(
                    business: $business,
                    outlet: $outlet,
                    item: $poItem->inventoryItem,
                    qty: $receivedInventoryQty,
                    unitCost: $unitCost,
                    movementType: InventoryMovementType::Purchase,
                    reference: $receipt,
                    description: 'Penerimaan PO '.$po->po_number.' (Surat Jalan: '.($receipt->delivery_order_number ?: '-').')',
                    user: $receiver
                );

                // 4. Update riwayat harga beli terakhir pada supplier
                if ($po->supplier_id) {
                    DB::table('supplier_inventory_items')->updateOrInsert(
                        [
                            'supplier_id' => $po->supplier_id,
                            'inventory_item_id' => $poItem->inventory_item_id,
                        ],
                        [
                            'last_purchase_price' => $unitPurchasePrice,
                        ]
                    );
                }
            }

            // 5. Evaluasi dan perbarui status PO (Partial vs Received)
            $po->refresh();
            $allCompleted = $po->items->every(function ($item) {
                return (float) $item->qty_received >= (float) $item->qty_ordered;
            });

            $po->status = $allCompleted ? PurchaseOrderStatus::Received : PurchaseOrderStatus::PartialReceived;
            $po->approved_by = $receiver->id;
            $po->save();

            // 6. Audit Logging
            $this->activityLogService->log($receipt, 'created', $receiver);
            $this->activityLogService->log($po, 'goods_received', $receiver);

            return $receipt;
        });
    }

    /**
     * Batalkan (void) dokumen penerimaan fisik barang dan lakukan pembalikan mutasi stok.
     */
    public function voidReceipt(GoodsReceipt $receipt, User $voider, ?string $reason = null): GoodsReceipt
    {
        return DB::transaction(function () use ($receipt, $voider, $reason) {
            if ($receipt->status === GoodsReceiptStatus::Voided) {
                abort(400, 'Penerimaan barang ini sudah dibatalkan sebelumnya.');
            }

            $receipt->load(['purchaseOrder.items', 'items.inventoryItem', 'items.purchaseReturnItems.purchaseReturn', 'outlet.business']);
            $po = $receipt->purchaseOrder;
            $outlet = $receipt->outlet;
            $business = $outlet?->business ?? $voider->business;

            // 1. Validasi dependensi retur aktif
            foreach ($receipt->items as $receiptItem) {
                foreach ($receiptItem->purchaseReturnItems as $prItem) {
                    $pr = $prItem->purchaseReturn;
                    if ($pr && $pr->status !== \App\Enums\PurchaseReturnStatus::Voided) {
                        abort(422, "Penerimaan barang ini memiliki riwayat retur aktif ({$pr->return_number}). Silakan batalkan (void) retur terlebih dahulu sebelum membatalkan penerimaan.");
                    }
                }
            }

            // 2. Validasi ketersediaan stok fisik di outlet (Anti-Negative Stock Guard)
            foreach ($receipt->items as $receiptItem) {
                $qtyToReverse = (float) $receiptItem->received_inventory_qty;
                if ($qtyToReverse > 0) {
                    $balance = \App\Models\Inventory\InventoryBalance::query()
                        ->where('outlet_id', $outlet->id)
                        ->where('inventory_item_id', $receiptItem->inventory_item_id)
                        ->first();

                    $currentStock = (float) ($balance?->current_stock ?? 0);
                    if ($currentStock < $qtyToReverse - 0.0001) {
                        $itemName = $receiptItem->inventoryItem?->name ?? 'Barang';
                        $unitName = $receiptItem->inventoryItem?->uom?->name ?? 'unit';
                        abort(422, "Penerimaan barang tidak dapat dibatalkan karena sisa stok {$itemName} di outlet tersisa {$currentStock} {$unitName}, kurang dari jumlah penerimaan yang hendak dibatalkan ({$qtyToReverse} {$unitName}).");
                    }
                }
            }

            foreach ($receipt->items as $receiptItem) {
                $qtyToReverse = (float) $receiptItem->received_inventory_qty;

                if ($qtyToReverse > 0) {
                    // 1. Catat mutasi stok keluar pembalik bertipe purchase_void
                    $this->costingService->recordOutgoingStock(
                        business: $business,
                        outlet: $outlet,
                        item: $receiptItem->inventoryItem,
                        qty: $qtyToReverse,
                        movementType: InventoryMovementType::PurchaseVoid,
                        reference: $receipt,
                        description: 'Void penerimaan barang '.$receipt->receipt_number.($reason ? ' - Alasan: '.$reason : ''),
                        user: $voider
                    );

                    // 2. Bersihkan layer FIFO yang berasal dari penerimaan ini
                    InventoryCostLayer::query()
                        ->where('reference_id', $receipt->id)
                        ->where('inventory_item_id', $receiptItem->inventory_item_id)
                        ->delete();

                    // 3. Kurangi kembali akumulasi qty_received pada baris PO
                    if ($receiptItem->purchaseOrderItem) {
                        $poItem = $receiptItem->purchaseOrderItem;
                        $poItem->qty_received = max(0, (float) $poItem->qty_received - (float) $receiptItem->received_purchase_qty);
                        $poItem->converted_qty = max(0, (float) $poItem->converted_qty - (float) $receiptItem->received_inventory_qty);
                        $poItem->save();
                    }
                }
            }

            // 4. Ubah status receipt menjadi voided
            $receipt->status = GoodsReceiptStatus::Voided;
            $receipt->notes = trim(($receipt->notes ? $receipt->notes."\n" : '').'Dibatalkan oleh '.$voider->name.($reason ? ': '.$reason : ''));
            $receipt->save();

            // 5. Evaluasi ulang status PO
            if ($po) {
                $po->refresh();
                $totalReceived = $po->items->sum('qty_received');

                if ($totalReceived <= 0) {
                    $po->status = PurchaseOrderStatus::Ordered;
                } else {
                    $allCompleted = $po->items->every(function ($item) {
                        return (float) $item->qty_received >= (float) $item->qty_ordered;
                    });
                    $po->status = $allCompleted ? PurchaseOrderStatus::Received : PurchaseOrderStatus::PartialReceived;
                }
                $po->save();
            }

            // 6. Audit Logging
            $this->activityLogService->log($receipt, 'voided', $voider);
            if ($po) {
                $this->activityLogService->log($po, 'receipt_voided', $voider);
            }

            return $receipt;
        });
    }
}
