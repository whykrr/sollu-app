<?php

namespace App\Services\App\Inventory;

use App\Enums\InventoryMovementType;
use App\Enums\StockTransferMode;
use App\Enums\StockTransferStatus;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\StockTransfer;
use App\Models\Outlet;
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
            $fromOutlet = Outlet::findOrFail($data['from_outlet_id'] ?? null);
            $this->stockFreezeService->assertNotFrozen($fromOutlet);

            $toOutlet = Outlet::findOrFail($data['to_outlet_id'] ?? null);
            $this->stockFreezeService->assertNotFrozen($toOutlet);

            $data['business_id'] = $creator->business_id;
            $data['requested_by'] = $creator->id;

            $transferMode = isset($data['transfer_mode'])
                ? ($data['transfer_mode'] instanceof StockTransferMode
                    ? $data['transfer_mode']
                    : (StockTransferMode::tryFrom($data['transfer_mode']) ?? StockTransferMode::DIRECT))
                : StockTransferMode::DIRECT;
            $data['transfer_mode'] = $transferMode->value;

            $count = StockTransfer::where('business_id', $creator->business_id)
                ->whereYear('created_at', now()->year)
                ->whereMonth('created_at', now()->month)
                ->count();
            $data['transfer_number'] = 'TF-'.now()->format('Ym').'-'.str_pad($count + 1, 3, '0', STR_PAD_LEFT);

            $requiresSupervision = $this->inventorySodService->isSupervisionEnabled($creator->business);

            if ($requiresSupervision) {
                $data['status'] = StockTransferStatus::Pending->value;
                $transfer = StockTransfer::create($data);

                foreach ($data['items'] ?? [] as $itemData) {
                    $transfer->items()->create([
                        'inventory_item_id' => $itemData['inventory_item_id'],
                        'qty' => $itemData['qty'],
                        'qty_received' => 0,
                        'qty_difference' => 0,
                    ]);
                }

                $this->activityLogService->log(
                    $transfer,
                    'created',
                    $creator,
                    ['message' => 'Permintaan transfer dibuat (menunggu persetujuan supervisi)']
                );

                return $transfer;
            }

            // Flexible Mode (Supervision Disabled)
            if ($transferMode === StockTransferMode::DIRECT) {
                $data['status'] = StockTransferStatus::Completed->value;
                $data['approved_by'] = $creator->id;
                $data['received_by'] = $creator->id;
                $data['received_at'] = now();

                $transfer = StockTransfer::create($data);

                foreach ($data['items'] ?? [] as $itemData) {
                    $invItem = InventoryItem::findOrFail($itemData['inventory_item_id']);
                    $qty = (float) $itemData['qty'];

                    // 1. Potong Stok & Hitung Nilai HPP di Outlet Asal (Transfer Out)
                    $outResult = $this->costingService->recordOutgoingStock(
                        business: $creator->business,
                        outlet: $fromOutlet,
                        item: $invItem,
                        qty: $qty,
                        movementType: InventoryMovementType::TransferOut,
                        reference: $transfer,
                        description: 'Transfer langsung ke '.$toOutlet->name.' (TF: '.$transfer->transfer_number.')',
                        user: $creator
                    );

                    $unitCost = $outResult['unit_cogs'] > 0
                        ? $outResult['unit_cogs']
                        : (float) ($invItem->balances()->where('outlet_id', $fromOutlet->id)->value('average_cost') ?? 0);

                    // 2. Tambah Stok di Outlet Tujuan (Transfer In)
                    $this->costingService->recordIncomingStock(
                        business: $creator->business,
                        outlet: $toOutlet,
                        item: $invItem,
                        qty: $qty,
                        unitCost: $unitCost,
                        movementType: InventoryMovementType::TransferIn,
                        reference: $transfer,
                        description: 'Transfer langsung dari '.$fromOutlet->name.' (TF: '.$transfer->transfer_number.')',
                        user: $creator
                    );

                    $transfer->items()->create([
                        'inventory_item_id' => $invItem->id,
                        'qty' => $qty,
                        'qty_received' => $qty,
                        'unit_cost' => $unitCost,
                        'qty_difference' => 0,
                    ]);
                }

                $this->activityLogService->log(
                    $transfer,
                    'created_and_completed',
                    $creator,
                    ['message' => 'Transfer langsung 1-langkah dibuat dan diselesaikan seketika']
                );

                return $transfer;
            }

            // TWO_STEP Mode: Potong stok pengirim langsung (InTransit)
            $data['status'] = StockTransferStatus::InTransit->value;
            $data['approved_by'] = $creator->id;
            $data['shipped_by'] = $creator->id;
            $data['shipped_at'] = now();

            $transfer = StockTransfer::create($data);

            foreach ($data['items'] ?? [] as $itemData) {
                $invItem = InventoryItem::findOrFail($itemData['inventory_item_id']);
                $qty = (float) $itemData['qty'];

                $outResult = $this->costingService->recordOutgoingStock(
                    business: $creator->business,
                    outlet: $fromOutlet,
                    item: $invItem,
                    qty: $qty,
                    movementType: InventoryMovementType::TransferOut,
                    reference: $transfer,
                    description: 'Pengiriman transfer ke '.$toOutlet->name.' (TF: '.$transfer->transfer_number.')',
                    user: $creator
                );

                $unitCost = $outResult['unit_cogs'] > 0
                    ? $outResult['unit_cogs']
                    : (float) ($invItem->balances()->where('outlet_id', $fromOutlet->id)->value('average_cost') ?? 0);

                $transfer->items()->create([
                    'inventory_item_id' => $invItem->id,
                    'qty' => $qty,
                    'qty_received' => 0,
                    'unit_cost' => $unitCost,
                    'qty_difference' => 0,
                ]);
            }

            $this->activityLogService->log(
                $transfer,
                'created_and_shipped',
                $creator,
                ['message' => 'Transfer 2-langkah dibuat dan barang dikirim (dalam perjalanan)']
            );

            return $transfer;
        });
    }

    public function updateTransfer(StockTransfer $transfer, array $data): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $data) {
            if ($transfer->status !== StockTransferStatus::Pending) {
                abort(403, 'Hanya transfer berstatus Menunggu yang dapat diubah.');
            }

            $fromOutletId = $data['from_outlet_id'] ?? $transfer->from_outlet_id;
            $toOutletId = $data['to_outlet_id'] ?? $transfer->to_outlet_id;

            $fromOutlet = Outlet::find($fromOutletId);
            if ($fromOutlet) {
                $this->stockFreezeService->assertNotFrozen($fromOutlet);
            }
            $toOutlet = Outlet::find($toOutletId);
            if ($toOutlet) {
                $this->stockFreezeService->assertNotFrozen($toOutlet);
            }

            if (isset($data['transfer_mode']) && is_string($data['transfer_mode'])) {
                $data['transfer_mode'] = StockTransferMode::tryFrom($data['transfer_mode'])?->value ?? $data['transfer_mode'];
            }

            $transfer->update($data);

            if (isset($data['items'])) {
                $transfer->items()->delete();
                foreach ($data['items'] as $itemData) {
                    $transfer->items()->create([
                        'inventory_item_id' => $itemData['inventory_item_id'],
                        'qty' => $itemData['qty'],
                        'qty_received' => 0,
                        'qty_difference' => 0,
                    ]);
                }
            }

            return $transfer;
        });
    }

    public function approveTransfer(StockTransfer $transfer, User $approver): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $approver) {
            if ($transfer->status !== StockTransferStatus::Pending) {
                abort(403, 'Hanya transfer berstatus Menunggu yang dapat disetujui.');
            }

            $this->inventorySodService->assertCanApproveTransfer($transfer, $approver);

            $this->stockFreezeService->assertNotFrozen($transfer->fromOutlet);
            $this->stockFreezeService->assertNotFrozen($transfer->toOutlet);

            $business = $transfer->business ?? $transfer->fromOutlet?->business ?? $approver->business;

            // Mode DIRECT: Langsung mutasi keluar & masuk secara atomik -> status Completed
            if ($transfer->transfer_mode === StockTransferMode::DIRECT) {
                $transfer->load(['items.inventoryItem', 'fromOutlet', 'toOutlet']);

                foreach ($transfer->items as $transferItem) {
                    $invItem = $transferItem->inventoryItem;
                    $qty = (float) $transferItem->qty;

                    // 1. Potong Stok Asal (TransferOut)
                    $outResult = $this->costingService->recordOutgoingStock(
                        business: $business,
                        outlet: $transfer->fromOutlet,
                        item: $invItem,
                        qty: $qty,
                        movementType: InventoryMovementType::TransferOut,
                        reference: $transfer,
                        description: 'Transfer keluar ke '.$transfer->toOutlet->name.' (TF: '.$transfer->transfer_number.')',
                        user: $approver
                    );

                    $unitCost = $outResult['unit_cogs'] > 0
                        ? $outResult['unit_cogs']
                        : (float) ($invItem->balances()->where('outlet_id', $transfer->fromOutlet->id)->value('average_cost') ?? 0);

                    // 2. Tambah Stok Tujuan (TransferIn)
                    $this->costingService->recordIncomingStock(
                        business: $business,
                        outlet: $transfer->toOutlet,
                        item: $invItem,
                        qty: $qty,
                        unitCost: $unitCost,
                        movementType: InventoryMovementType::TransferIn,
                        reference: $transfer,
                        description: 'Transfer masuk dari '.$transfer->fromOutlet->name.' (TF: '.$transfer->transfer_number.')',
                        user: $approver
                    );

                    $transferItem->update([
                        'qty_received' => $qty,
                        'unit_cost' => $unitCost,
                        'qty_difference' => 0,
                    ]);
                }

                $transfer->update([
                    'status' => StockTransferStatus::Completed,
                    'approved_by' => $approver->id,
                    'received_by' => $approver->id,
                    'received_at' => now(),
                ]);

                $this->activityLogService->log(
                    $transfer,
                    'approved_and_completed',
                    $approver,
                    ['message' => 'Transfer langsung 1-langkah disetujui dan diselesaikan seketika']
                );

                return $transfer;
            }

            // Mode TWO_STEP: Status Approved, menunggu pengiriman oleh staf gudang/outlet
            $transfer->update([
                'status' => StockTransferStatus::Approved,
                'approved_by' => $approver->id,
            ]);

            $this->activityLogService->log(
                $transfer,
                'approved',
                $approver,
                ['message' => 'Transfer disetujui (siap dikirim)']
            );

            return $transfer;
        });
    }

    public function rejectTransfer(StockTransfer $transfer, array $data, User $rejecter): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $data, $rejecter) {
            if ($transfer->status !== StockTransferStatus::Pending) {
                abort(403, 'Hanya transfer berstatus Menunggu yang dapat ditolak.');
            }

            $transfer->update([
                'status' => StockTransferStatus::Rejected,
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
            if ($transfer->status !== StockTransferStatus::Approved) {
                abort(403, 'Hanya transfer berstatus Disetujui yang dapat dikirim.');
            }

            $this->stockFreezeService->assertNotFrozen($transfer->fromOutlet);

            $transfer->load(['items.inventoryItem', 'fromOutlet.business', 'toOutlet']);
            $business = $transfer->business ?? $transfer->fromOutlet?->business ?? $shipper->business;

            foreach ($transfer->items as $transferItem) {
                $invItem = $transferItem->inventoryItem;
                $qty = (float) $transferItem->qty;

                // Potong Stok Asal & Rekam snapshot unit_cost
                $outResult = $this->costingService->recordOutgoingStock(
                    business: $business,
                    outlet: $transfer->fromOutlet,
                    item: $invItem,
                    qty: $qty,
                    movementType: InventoryMovementType::TransferOut,
                    reference: $transfer,
                    description: 'Pengiriman transfer keluar ke '.$transfer->toOutlet->name.' (TF: '.$transfer->transfer_number.')',
                    user: $shipper
                );

                $unitCost = $outResult['unit_cogs'] > 0
                    ? $outResult['unit_cogs']
                    : (float) ($invItem->balances()->where('outlet_id', $transfer->fromOutlet->id)->value('average_cost') ?? 0);

                $transferItem->update(['unit_cost' => $unitCost]);
            }

            $transfer->update([
                'status' => StockTransferStatus::InTransit,
                'shipped_at' => now(),
                'shipped_by' => $shipper->id,
            ]);

            $this->activityLogService->log(
                $transfer,
                'shipped',
                $shipper,
                ['message' => 'Transfer dikirim dan dalam perjalanan, stok outlet asal telah dipotong']
            );

            return $transfer;
        });
    }

    public function completeTransfer(StockTransfer $transfer, array $receivedData, User $receiver): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $receivedData, $receiver) {
            if ($transfer->status !== StockTransferStatus::InTransit) {
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
                $itemInput = $itemsMap->get($transferItem->id);
                $qtyToReceive = $itemInput ? (float) $itemInput['qty_received'] : (float) $transferItem->qty;
                $qtyToReceive = max(0, min($qtyToReceive, (float) $transferItem->qty));
                $qtyDifference = (float) max(0, $transferItem->qty - $qtyToReceive);
                $diffReason = ($qtyDifference > 0 && $itemInput) ? ($itemInput['difference_reason'] ?? null) : null;

                $transferItem->qty_received = $qtyToReceive;
                $transferItem->qty_difference = $qtyDifference;
                $transferItem->difference_reason = $diffReason;
                $transferItem->save();

                if ($qtyToReceive > 0) {
                    $invItem = $transferItem->inventoryItem;
                    $unitCost = ($transferItem->unit_cost && $transferItem->unit_cost > 0)
                        ? (float) $transferItem->unit_cost
                        : (float) ($invItem->balances()->where('outlet_id', $fromOutlet->id)->value('average_cost') ?? 0);

                    // Tambah Stok di Outlet Tujuan menggunakan unit_cost pengiriman
                    $this->costingService->recordIncomingStock(
                        business: $business,
                        outlet: $toOutlet,
                        item: $invItem,
                        qty: $qtyToReceive,
                        unitCost: $unitCost,
                        movementType: InventoryMovementType::TransferIn,
                        reference: $transfer,
                        description: 'Transfer masuk dari '.$fromOutlet->name.' (TF: '.$transfer->transfer_number.')',
                        user: $receiver
                    );
                }
            }

            $transfer->status = StockTransferStatus::Completed;
            $transfer->received_by = $receiver->id;
            $transfer->received_at = now();
            $transfer->save();

            $this->activityLogService->log(
                $transfer,
                'received',
                $receiver,
                [
                    'message' => 'Transfer diterima di outlet tujuan',
                    'items_received' => count($receivedData['items']),
                ]
            );

            return $transfer;
        });
    }

    public function voidTransfer(StockTransfer $transfer, array $data, User $user): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $data, $user) {
            if (in_array($transfer->status, [StockTransferStatus::Void, StockTransferStatus::Rejected], true)) {
                abort(422, 'Mutasi transfer dengan status ini tidak dapat dibatalkan.');
            }

            $transfer->load(['items.inventoryItem', 'fromOutlet.business', 'toOutlet.business']);
            $business = $transfer->business ?? $transfer->fromOutlet?->business ?? $user->business;
            $fromOutlet = $transfer->fromOutlet;
            $toOutlet = $transfer->toOutlet;

            // Kondisi 2: Status InTransit (Stok asal sudah terpotong, barang belum sampai di tujuan)
            if ($transfer->status === StockTransferStatus::InTransit) {
                $this->stockFreezeService->assertNotFrozen($fromOutlet);

                foreach ($transfer->items as $transferItem) {
                    $qty = (float) $transferItem->qty;
                    if ($qty > 0) {
                        $invItem = $transferItem->inventoryItem;
                        $unitCost = ($transferItem->unit_cost && $transferItem->unit_cost > 0)
                            ? (float) $transferItem->unit_cost
                            : (float) ($invItem->balances()->where('outlet_id', $fromOutlet->id)->value('average_cost') ?? 0);

                        // Kembalikan stok ke outlet asal
                        $this->costingService->recordIncomingStock(
                            business: $business,
                            outlet: $fromOutlet,
                            item: $invItem,
                            qty: $qty,
                            unitCost: $unitCost,
                            movementType: InventoryMovementType::TransferIn,
                            reference: $transfer,
                            description: 'Pembatalan Pengiriman Transfer TF: '.$transfer->transfer_number,
                            user: $user
                        );
                    }
                }
            }

            // Kondisi 3: Status Completed (Stok sudah diterima di outlet tujuan)
            if ($transfer->status === StockTransferStatus::Completed) {
                $this->stockFreezeService->assertNotFrozen($fromOutlet);
                $this->stockFreezeService->assertNotFrozen($toOutlet);
                // Guard: Periksa saldo berjalan di toOutlet >= qty_received (mencegah stok negatif jika barang tujuan sudah terjual)
                foreach ($transfer->items as $transferItem) {
                    $qtyReceived = (float) $transferItem->qty_received;
                    if ($qtyReceived > 0) {
                        $invItem = $transferItem->inventoryItem;
                        $currentStock = (float) ($invItem->balances()->where('outlet_id', $toOutlet->id)->value('current_stock') ?? 0);

                        if ($currentStock < $qtyReceived) {
                            $name = $invItem->name ?? 'Item';
                            throw new \Exception("Stok barang {$name} di outlet tujuan ({$toOutlet->name}) tidak mencukupi untuk dibatalkan. Sisa stok: {$currentStock}, Diperlukan: {$qtyReceived}.");
                        }
                    }
                }

                // Mutasi pembalik simetris (storno):
                // 1. Kurangi dari toOutlet (TransferOut)
                // 2. Tambah kembali ke fromOutlet (TransferIn)
                foreach ($transfer->items as $transferItem) {
                    $qtyReceived = (float) $transferItem->qty_received;
                    if ($qtyReceived > 0) {
                        $invItem = $transferItem->inventoryItem;

                        $outResult = $this->costingService->recordOutgoingStock(
                            business: $business,
                            outlet: $toOutlet,
                            item: $invItem,
                            qty: $qtyReceived,
                            movementType: InventoryMovementType::TransferOut,
                            reference: $transfer,
                            description: 'Void Transfer TF: '.$transfer->transfer_number,
                            user: $user
                        );

                        $unitCost = $outResult['unit_cogs'] > 0
                            ? $outResult['unit_cogs']
                            : (($transferItem->unit_cost && $transferItem->unit_cost > 0) ? (float) $transferItem->unit_cost : 0);

                        $this->costingService->recordIncomingStock(
                            business: $business,
                            outlet: $fromOutlet,
                            item: $invItem,
                            qty: $qtyReceived,
                            unitCost: $unitCost,
                            movementType: InventoryMovementType::TransferIn,
                            reference: $transfer,
                            description: 'Void Transfer TF: '.$transfer->transfer_number,
                            user: $user
                        );
                    }
                }
            }

            // Kondisi 1 (Pending / Approved): Belum ada mutasi stok, cukup perbarui status

            $transfer->status = StockTransferStatus::Void;
            $transfer->voided_at = now();
            $transfer->voided_by = $user->id;
            $transfer->void_reason = $data['void_reason'] ?? $data['reason'] ?? null;
            $transfer->save();

            $this->activityLogService->log(
                $transfer,
                'voided',
                $user,
                [
                    'message' => 'Mutasi transfer dibatalkan (void)',
                    'void_reason' => $transfer->void_reason,
                ]
            );

            return $transfer;
        });
    }
}
