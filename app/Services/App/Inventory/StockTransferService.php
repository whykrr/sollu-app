<?php

namespace App\Services\App\Inventory;

use App\Enums\InventoryMovementType;
use App\Enums\StockTransferStatus;
use App\Models\Inventory\StockTransfer;
use App\Models\User;
use App\Services\App\Master\ActivityLogService;
use Illuminate\Support\Facades\DB;

class StockTransferService
{
    public function __construct(
        protected ActivityLogService $activityLogService,
        protected StockFreezeService $stockFreezeService,
        protected InventoryCostingService $costingService,
        protected InventorySodService $inventorySodService
    ) {}

    public function createTransfer(array $data, User $creator): StockTransfer
    {
        return DB::transaction(function () use ($data, $creator) {
            $data['business_id'] = $creator->business_id;
            $data['requested_by'] = $creator->id;

            $count = StockTransfer::where('business_id', $creator->business_id)
                ->whereMonth('created_at', now()->month)
                ->count();
            $data['transfer_number'] = 'TF-'.now()->format('Ym').'-'.str_pad($count + 1, 3, '0', STR_PAD_LEFT);
            $data['status'] = StockTransferStatus::Pending->value;

            $transfer = StockTransfer::create($data);

            foreach ($data['items'] ?? [] as $itemData) {
                $transfer->items()->create([
                    'inventory_item_id' => $itemData['inventory_item_id'],
                    'qty' => $itemData['qty'],
                    'qty_received' => 0,
                ]);
            }

            $this->activityLogService->log(
                $transfer,
                'created',
                $creator,
                ['message' => 'Permintaan transfer dibuat']
            );

            return $transfer;
        });
    }

    public function updateTransfer(StockTransfer $transfer, array $data): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $data) {
            if ($transfer->status !== StockTransferStatus::Pending->value) {
                abort(403, 'Hanya transfer berstatus Menunggu yang dapat diubah.');
            }

            $transfer->update($data);

            if (isset($data['items'])) {
                $transfer->items()->delete();
                foreach ($data['items'] as $itemData) {
                    $transfer->items()->create([
                        'inventory_item_id' => $itemData['inventory_item_id'],
                        'qty' => $itemData['qty'],
                        'qty_received' => 0,
                    ]);
                }
            }

            return $transfer;
        });
    }

    public function approveTransfer(StockTransfer $transfer, User $approver): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $approver) {
            if ($transfer->status !== StockTransferStatus::Pending->value) {
                abort(403, 'Hanya transfer berstatus Menunggu yang dapat disetujui.');
            }

            $this->inventorySodService->assertCanApproveTransfer($transfer, $approver);

            $this->stockFreezeService->assertNotFrozen($transfer->fromOutlet);
            $this->stockFreezeService->assertNotFrozen($transfer->toOutlet);

            $transfer->update([
                'status' => StockTransferStatus::Approved->value,
                'approved_by' => $approver->id,
            ]);

            $this->activityLogService->log(
                $transfer,
                'approved',
                $approver,
                ['message' => 'Transfer disetujui']
            );

            return $transfer;
        });
    }

    public function rejectTransfer(StockTransfer $transfer, array $data, User $rejecter): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $data, $rejecter) {
            if ($transfer->status !== StockTransferStatus::Pending->value) {
                abort(403, 'Hanya transfer berstatus Menunggu yang dapat ditolak.');
            }

            $transfer->update([
                'status' => StockTransferStatus::Rejected->value,
                'notes' => $data['notes'] ?? $transfer->notes,
            ]);

            $this->activityLogService->log(
                $transfer,
                'rejected',
                $rejecter,
                ['message' => 'Transfer ditolak', 'notes' => $data['notes'] ?? null]
            );

            return $transfer;
        });
    }

    public function shipTransfer(StockTransfer $transfer, User $shipper): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $shipper) {
            if ($transfer->status !== StockTransferStatus::Approved->value) {
                abort(403, 'Hanya transfer berstatus Disetujui yang dapat dikirim.');
            }

            $this->stockFreezeService->assertNotFrozen($transfer->fromOutlet);

            $transfer->update([
                'status' => StockTransferStatus::InTransit->value,
            ]);

            $this->activityLogService->log(
                $transfer,
                'shipped',
                $shipper,
                ['message' => 'Transfer dalam perjalanan']
            );

            return $transfer;
        });
    }

    public function completeTransfer(StockTransfer $transfer, array $receivedData, User $receiver): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $receivedData, $receiver) {
            if ($transfer->status !== StockTransferStatus::InTransit->value) {
                abort(403, 'Hanya transfer berstatus Dalam Perjalanan yang dapat diterima.');
            }

            $this->inventorySodService->assertCanReceiveTransfer($transfer, $receiver);

            $this->stockFreezeService->assertNotFrozen($transfer->fromOutlet);
            $this->stockFreezeService->assertNotFrozen($transfer->toOutlet);

            $transfer->load(['items.inventoryItem', 'fromOutlet.business', 'toOutlet.business']);
            $business = $transfer->business ?? $transfer->fromOutlet?->business ?? $receiver->business;
            $fromOutlet = $transfer->fromOutlet;
            $toOutlet = $transfer->toOutlet;

            $itemsMap = collect($receivedData['items'])->keyBy('id');

            foreach ($transfer->items as $transferItem) {
                if ($itemsMap->has($transferItem->id)) {
                    $qtyToReceive = (float) $itemsMap->get($transferItem->id)['qty_received'];
                    $invItem = $transferItem->inventoryItem;

                    if ($qtyToReceive > 0) {
                        $transferItem->qty_received = $qtyToReceive;
                        $transferItem->save();

                        // 1. Potong Stok & Hitung Nilai HPP di Outlet Asal (Transfer Out)
                        $outResult = $this->costingService->recordOutgoingStock(
                            business: $business,
                            outlet: $fromOutlet,
                            item: $invItem,
                            qty: $qtyToReceive,
                            movementType: InventoryMovementType::TransferOut,
                            reference: $transfer,
                            description: 'Transfer keluar ke '.$toOutlet->name.' (TF: '.$transfer->transfer_number.')',
                            user: $receiver
                        );

                        // 2. Tambah Stok & Bawa Nilai HPP ke Outlet Tujuan (Transfer In)
                        $transferUnitCost = $outResult['unit_cogs'] > 0
                            ? $outResult['unit_cogs']
                            : (float) ($invItem->balances()->where('outlet_id', $fromOutlet->id)->value('average_cost') ?? 0);

                        $this->costingService->recordIncomingStock(
                            business: $business,
                            outlet: $toOutlet,
                            item: $invItem,
                            qty: $qtyToReceive,
                            unitCost: $transferUnitCost,
                            movementType: InventoryMovementType::TransferIn,
                            reference: $transfer,
                            description: 'Transfer masuk dari '.$fromOutlet->name.' (TF: '.$transfer->transfer_number.')',
                            user: $receiver
                        );
                    }
                }
            }

            $transfer->status = StockTransferStatus::Completed->value;
            $transfer->received_by = $receiver->id;
            $transfer->save();

            $this->activityLogService->log(
                $transfer,
                'received',
                $receiver,
                ['message' => 'Transfer diterima', 'items_received' => count($receivedData['items'])]
            );

            return $transfer;
        });
    }
}
