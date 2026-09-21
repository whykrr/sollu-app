<?php

namespace App\Services\App\Inventory;

use App\Enums\InventoryMovementType;
use App\Enums\PurchaseReturnStatus;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\PurchaseReturn;
use App\Models\Outlet;
use App\Models\User;
use App\Services\App\Master\ActivityLogService;
use Illuminate\Support\Facades\DB;

class PurchaseReturnService
{
    public function __construct(
        protected ActivityLogService $activityLogService,
        protected InventoryCostingService $costingService
    ) {}

    /**
     * Buat dokumen retur pembelian ke pemasok dan kurangi persediaan fisik barang.
     */
    public function createReturn(array $data, User $creator): PurchaseReturn
    {
        return DB::transaction(function () use ($data, $creator) {
            $businessId = $creator->business_id;
            $outletId = $data['outlet_id'];
            $outlet = Outlet::query()->where('business_id', $businessId)->findOrFail($outletId);
            $business = $creator->business;

            // Generate nomor retur unik (PR-YYYYMM-XXX)
            $count = PurchaseReturn::query()
                ->where('business_id', $businessId)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();

            $seq = $count + 1;
            do {
                $returnNumber = 'PR-'.now()->format('Ym').'-'.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
                $seq++;
            } while (PurchaseReturn::query()->where('business_id', $businessId)->where('return_number', $returnNumber)->exists());

            $return = PurchaseReturn::create([
                'business_id' => $businessId,
                'outlet_id' => $outletId,
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'supplier_id' => $data['supplier_id'] ?? null,
                'return_number' => $returnNumber,
                'return_date' => $data['return_date'] ?? now()->format('Y-m-d'),
                'reason' => $data['reason'] ?? null,
                'total_return_amount' => 0,
                'status' => PurchaseReturnStatus::Completed,
                'created_by' => $creator->id,
            ]);

            $totalReturnAmount = 0;
            $items = $data['items'] ?? [];

            foreach ($items as $itemData) {
                $inventoryItemId = $itemData['inventory_item_id'];
                $inventoryItem = InventoryItem::query()
                    ->where('business_id', $businessId)
                    ->findOrFail($inventoryItemId);

                $returnPurchaseQty = (float) $itemData['return_purchase_qty'];
                if ($returnPurchaseQty <= 0) {
                    continue;
                }

                $conversionFactor = (float) ($itemData['conversion_factor'] ?? 1.0);
                if ($conversionFactor <= 0) {
                    $conversionFactor = 1.0;
                }

                $returnInventoryQty = $returnPurchaseQty * $conversionFactor;
                $unitCost = (float) ($itemData['unit_cost'] ?? 0);
                $subtotal = $returnPurchaseQty * $unitCost;
                $totalReturnAmount += $subtotal;

                // 1. Catat baris retur
                $return->items()->create([
                    'inventory_item_id' => $inventoryItemId,
                    'uom_id' => $itemData['uom_id'] ?? null,
                    'return_purchase_qty' => $returnPurchaseQty,
                    'conversion_factor' => $conversionFactor,
                    'return_inventory_qty' => $returnInventoryQty,
                    'unit_cost' => $unitCost,
                    'subtotal' => $subtotal,
                ]);

                // 2. Potong stok keluar persediaan
                $this->costingService->recordOutgoingStock(
                    business: $business,
                    outlet: $outlet,
                    item: $inventoryItem,
                    qty: $returnInventoryQty,
                    movementType: InventoryMovementType::PurchaseReturn,
                    reference: $return,
                    description: 'Retur pembelian ke supplier ('.$return->return_number.')',
                    user: $creator
                );
            }

            $return->total_return_amount = $totalReturnAmount;
            $return->save();

            $this->activityLogService->log($return, 'created', $creator);

            return $return;
        });
    }

    /**
     * Batalkan (void) retur pembelian dan kembalikan stok fisik barang.
     */
    public function voidReturn(PurchaseReturn $return, User $voider, ?string $reason = null): PurchaseReturn
    {
        return DB::transaction(function () use ($return, $voider, $reason) {
            if ($return->status === PurchaseReturnStatus::Voided) {
                abort(400, 'Retur pembelian ini sudah dibatalkan sebelumnya.');
            }

            $return->load(['items.inventoryItem', 'outlet.business']);
            $outlet = $return->outlet;
            $business = $outlet?->business ?? $voider->business;

            foreach ($return->items as $item) {
                $qtyToRestore = (float) $item->return_inventory_qty;

                if ($qtyToRestore > 0) {
                    $this->costingService->recordIncomingStock(
                        business: $business,
                        outlet: $outlet,
                        item: $item->inventoryItem,
                        qty: $qtyToRestore,
                        unitCost: (float) $item->unit_cost,
                        movementType: InventoryMovementType::AdjustmentIn,
                        reference: $return,
                        description: 'Void retur pembelian '.$return->return_number.($reason ? ' - Alasan: '.$reason : ''),
                        user: $voider
                    );
                }
            }

            $return->status = PurchaseReturnStatus::Voided;
            $return->reason = trim(($return->reason ? $return->reason."\n" : '').'Dibatalkan oleh '.$voider->name.($reason ? ': '.$reason : ''));
            $return->save();

            $this->activityLogService->log($return, 'voided', $voider);

            return $return;
        });
    }
}
