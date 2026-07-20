<?php

namespace App\Services\Inventory;

use App\Enums\InventoryMovementType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryMovement;
use App\Models\Inventory\StockTransfer;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StockTransferService
{
    public function createTransfer(array $data, User $creator): StockTransfer
    {
        return DB::transaction(function () use ($data, $creator) {
            $data['business_id']   = $creator->business_id;
            $data['requested_by']  = $creator->id;
            
            $count = StockTransfer::where('business_id', $creator->business_id)
                ->whereMonth('created_at', now()->month)
                ->count();
            $data['transfer_number'] = 'TF-' . now()->format('Ym') . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
            $data['status'] = 'pending';

            $transfer = StockTransfer::create($data);

            foreach ($data['items'] ?? [] as $itemData) {
                $transfer->items()->create([
                    'inventory_item_id' => $itemData['inventory_item_id'],
                    'qty_transferred'   => $itemData['qty_transferred'],
                    'qty_received'      => 0,
                ]);
            }

            return $transfer;
        });
    }

    public function updateTransfer(StockTransfer $transfer, array $data): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $data) {
            if ($transfer->status !== 'pending') {
                abort(403, 'Hanya transfer berstatus Pending yang dapat diubah.');
            }

            $transfer->update($data);

            if (isset($data['items'])) {
                $transfer->items()->delete();
                foreach ($data['items'] as $itemData) {
                    $transfer->items()->create([
                        'inventory_item_id' => $itemData['inventory_item_id'],
                        'qty_transferred'   => $itemData['qty_transferred'],
                        'qty_received'      => 0,
                    ]);
                }
            }

            return $transfer;
        });
    }
    
    public function approveTransfer(StockTransfer $transfer, User $approver): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $approver) {
            if ($transfer->status !== 'pending') {
                abort(403, 'Hanya transfer berstatus Pending yang dapat disetujui.');
            }
            
            // In a real transit system, we'd deduct stock from the source outlet now.
            // But since our plan simplifies it, we just update status.
            $transfer->update([
                'status' => 'in_transit',
                'approved_by' => $approver->id
            ]);
            
            return $transfer;
        });
    }

    public function completeTransfer(StockTransfer $transfer, array $receivedData, User $receiver): StockTransfer
    {
        return DB::transaction(function () use ($transfer, $receivedData, $receiver) {
            if (!in_array($transfer->status, ['pending', 'in_transit'])) {
                abort(403, 'Status transfer tidak valid untuk diterima.');
            }

            $itemsMap = collect($receivedData['items'])->keyBy('id');

            foreach ($transfer->items as $transferItem) {
                if ($itemsMap->has($transferItem->id)) {
                    $qtyToReceive = (float) $itemsMap->get($transferItem->id)['qty_received'];
                    
                    if ($qtyToReceive > 0) {
                        $transferItem->qty_received += $qtyToReceive;
                        $transferItem->save();

                        // Update Source Balance (Deduct)
                        $sourceBalance = InventoryBalance::firstOrCreate([
                            'business_id'       => $transfer->business_id,
                            'outlet_id'         => $transfer->from_outlet_id,
                            'inventory_item_id' => $transferItem->inventory_item_id,
                        ], ['current_stock' => 0]);
                        
                        $sourceStockBefore = $sourceBalance->current_stock;
                        $sourceStockAfter  = $sourceStockBefore - $qtyToReceive;
                        $sourceBalance->update(['current_stock' => $sourceStockAfter]);
                        
                        // Update Destination Balance (Add)
                        $destBalance = InventoryBalance::firstOrCreate([
                            'business_id'       => $transfer->business_id,
                            'outlet_id'         => $transfer->to_outlet_id,
                            'inventory_item_id' => $transferItem->inventory_item_id,
                        ], ['current_stock' => 0]);

                        $destStockBefore = $destBalance->current_stock;
                        $destStockAfter  = $destStockBefore + $qtyToReceive;
                        $destBalance->update(['current_stock' => $destStockAfter]);

                        // Source Movement (Transfer Out)
                        $outMovement = InventoryMovement::create([
                            'business_id'       => $transfer->business_id,
                            'outlet_id'         => $transfer->from_outlet_id,
                            'inventory_item_id' => $transferItem->inventory_item_id,
                            'movement_type'     => InventoryMovementType::TransferOut,
                            'qty_change'        => -$qtyToReceive,
                            'stock_before'      => $sourceStockBefore,
                            'stock_after'       => $sourceStockAfter,
                            'description'       => 'Transfer keluar ke ' . $transfer->toOutlet->name . ' (TF: ' . $transfer->transfer_number . ')',
                            'created_by'        => $receiver->id,
                            'created_at'        => now(),
                        ]);
                        $outMovement->reference_id = $transfer->id;
                        $outMovement->reference_type = StockTransfer::class;
                        $outMovement->save();

                        // Destination Movement (Transfer In)
                        $inMovement = InventoryMovement::create([
                            'business_id'       => $transfer->business_id,
                            'outlet_id'         => $transfer->to_outlet_id,
                            'inventory_item_id' => $transferItem->inventory_item_id,
                            'movement_type'     => InventoryMovementType::TransferIn,
                            'qty_change'        => $qtyToReceive,
                            'stock_before'      => $destStockBefore,
                            'stock_after'       => $destStockAfter,
                            'description'       => 'Transfer masuk dari ' . $transfer->fromOutlet->name . ' (TF: ' . $transfer->transfer_number . ')',
                            'created_by'        => $receiver->id,
                            'created_at'        => now(),
                        ]);
                        $inMovement->reference_id = $transfer->id;
                        $inMovement->reference_type = StockTransfer::class;
                        $inMovement->save();
                    }
                }
            }

            $transfer->status      = 'completed';
            $transfer->received_by = $receiver->id;
            $transfer->save();

            return $transfer;
        });
    }
}
