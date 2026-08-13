<?php

namespace App\Services\Inventory;

use App\Enums\InventoryMovementType;
use App\Enums\StockTransferStatus;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryMovement;
use App\Models\Inventory\StockTransfer;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;

class StockTransferService
{
    public function __construct(
        protected ActivityLogService $activityLogService,
        protected StockFreezeService $stockFreezeService
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

            if (! $approver->can('business.*') && $approver->id === $transfer->requested_by) {
                abort(403, 'Anda tidak dapat menyetujui transfer yang Anda buat sendiri.');
            }

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

            $this->stockFreezeService->assertNotFrozen($transfer->fromOutlet);
            $this->stockFreezeService->assertNotFrozen($transfer->toOutlet);

            $transfer->load('items.inventoryItem');

            $itemsMap = collect($receivedData['items'])->keyBy('id');

            foreach ($transfer->items as $transferItem) {
                if ($itemsMap->has($transferItem->id)) {
                    $qtyToReceive = (float) $itemsMap->get($transferItem->id)['qty_received'];

                    if ($qtyToReceive > 0) {
                        $transferItem->qty_received = $qtyToReceive;
                        $transferItem->save();

                        // Source Balance (Deduct)
                        $sourceBalance = InventoryBalance::firstOrCreate([
                            'business_id' => $transfer->business_id,
                            'outlet_id' => $transfer->from_outlet_id,
                            'inventory_item_id' => $transferItem->inventory_item_id,
                        ], ['current_stock' => 0]);

                        $sourceStockBefore = $sourceBalance->current_stock;
                        $sourceStockAfter = $sourceStockBefore - $qtyToReceive;

                        if ($sourceStockAfter < 0) {
                            abort(403, "Stok outlet asal tidak mencukupi untuk item {$transferItem->inventoryItem->name}. Stok saat ini: {$sourceStockBefore}, dikurangi: {$qtyToReceive}.");
                        }

                        $sourceBalance->update(['current_stock' => $sourceStockAfter]);

                        // Destination Balance (Add)
                        $destBalance = InventoryBalance::firstOrCreate([
                            'business_id' => $transfer->business_id,
                            'outlet_id' => $transfer->to_outlet_id,
                            'inventory_item_id' => $transferItem->inventory_item_id,
                        ], ['current_stock' => 0]);

                        $destStockBefore = $destBalance->current_stock;
                        $destStockAfter = $destStockBefore + $qtyToReceive;
                        $destBalance->update(['current_stock' => $destStockAfter]);

                        // Source Movement (Transfer Out)
                        $outMovement = InventoryMovement::create([
                            'business_id' => $transfer->business_id,
                            'outlet_id' => $transfer->from_outlet_id,
                            'inventory_item_id' => $transferItem->inventory_item_id,
                            'movement_type' => InventoryMovementType::TransferOut,
                            'qty_change' => -$qtyToReceive,
                            'stock_before' => $sourceStockBefore,
                            'stock_after' => $sourceStockAfter,
                            'description' => 'Transfer keluar ke '.$transfer->toOutlet->name.' (TF: '.$transfer->transfer_number.')',
                            'created_by' => $receiver->id,
                            'created_at' => now(),
                        ]);
                        $outMovement->reference_id = $transfer->id;
                        $outMovement->reference_type = StockTransfer::class;
                        $outMovement->save();

                        // Destination Movement (Transfer In)
                        $inMovement = InventoryMovement::create([
                            'business_id' => $transfer->business_id,
                            'outlet_id' => $transfer->to_outlet_id,
                            'inventory_item_id' => $transferItem->inventory_item_id,
                            'movement_type' => InventoryMovementType::TransferIn,
                            'qty_change' => $qtyToReceive,
                            'stock_before' => $destStockBefore,
                            'stock_after' => $destStockAfter,
                            'description' => 'Transfer masuk dari '.$transfer->fromOutlet->name.' (TF: '.$transfer->transfer_number.')',
                            'created_by' => $receiver->id,
                            'created_at' => now(),
                        ]);
                        $inMovement->reference_id = $transfer->id;
                        $inMovement->reference_type = StockTransfer::class;
                        $inMovement->save();
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
