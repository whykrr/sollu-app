<?php

namespace App\Services\Inventory;

use App\Enums\InventoryMovementType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryMovement;
use App\Models\Inventory\StockOpname;
use App\Models\User;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\DB;

class StockOpnameService
{
    public function __construct(
        protected ActivityLogService $activityLog
    ) {}

    public function createOpname(array $data, User $creator): StockOpname
    {
        return DB::transaction(function () use ($data, $creator) {
            $data['business_id'] = $creator->business_id;
            $data['created_by']  = $creator->id;
            
            $count = StockOpname::where('business_id', $creator->business_id)
                ->whereMonth('created_at', now()->month)
                ->count();
            $data['opname_number'] = 'OP-' . now()->format('Ym') . '-' . str_pad($count + 1, 3, '0', STR_PAD_LEFT);
            $data['status'] = 'in_progress';

            $opname = StockOpname::create($data);

            foreach ($data['items'] ?? [] as $itemData) {
                $opname->items()->create([
                    'inventory_item_id' => $itemData['inventory_item_id'],
                    'system_qty'        => $itemData['system_qty'],
                    'actual_qty'        => $itemData['actual_qty'] ?? $itemData['system_qty'], // Default if not filled yet
                    'difference_qty'    => ($itemData['actual_qty'] ?? $itemData['system_qty']) - $itemData['system_qty'],
                ]);
            }

            $this->activityLog->log($opname, 'created', $creator);

            return $opname;
        });
    }

    public function updateOpname(StockOpname $opname, array $data, User $updater): StockOpname
    {
        return DB::transaction(function () use ($opname, $data, $updater) {
            if ($opname->status !== 'in_progress') {
                abort(403, 'Hanya opname berstatus In Progress yang dapat diubah.');
            }

            $opname->update(['notes' => $data['notes'] ?? $opname->notes]);

            if (isset($data['items'])) {
                $opname->items()->delete();
                foreach ($data['items'] as $itemData) {
                    $actualQty = (float) $itemData['actual_qty'];
                    $systemQty = (float) $itemData['system_qty'];
                    
                    $opname->items()->create([
                        'inventory_item_id' => $itemData['inventory_item_id'],
                        'system_qty'        => $systemQty,
                        'actual_qty'        => $actualQty,
                        'difference_qty'    => $actualQty - $systemQty,
                    ]);
                }
            }
            
            // Mark as pending approval after update
            $opname->update(['status' => 'pending_approval']);

            $this->activityLog->log($opname, 'submitted', $updater);

            return $opname;
        });
    }

    public function completeOpname(StockOpname $opname, array $data, User $approver): StockOpname
    {
        return DB::transaction(function () use ($opname, $data, $approver) {
            if ($opname->status !== 'pending_approval') {
                abort(403, 'Opname harus dalam status Menunggu Persetujuan.');
            }
            
            // Optional: Re-update items if they were adjusted during approval
            if (isset($data['items'])) {
                $opname->items()->delete();
                foreach ($data['items'] as $itemData) {
                    $actualQty = (float) $itemData['actual_qty'];
                    $systemQty = (float) $itemData['system_qty'];
                    
                    $opname->items()->create([
                        'inventory_item_id' => $itemData['inventory_item_id'],
                        'system_qty'        => $systemQty,
                        'actual_qty'        => $actualQty,
                        'difference_qty'    => $actualQty - $systemQty,
                    ]);
                }
            }

            // Execute balance adjustment
            foreach ($opname->items as $opnameItem) {
                if ($opnameItem->difference_qty != 0) {
                    $balance = InventoryBalance::firstOrCreate([
                        'business_id'       => $opname->business_id,
                        'outlet_id'         => $opname->outlet_id,
                        'inventory_item_id' => $opnameItem->inventory_item_id,
                    ], ['current_stock' => 0]);

                    $stockBefore = $balance->current_stock;
                    $stockAfter  = $opnameItem->actual_qty;

                    $balance->update(['current_stock' => $stockAfter]);

                    $movement = InventoryMovement::create([
                        'business_id'       => $opname->business_id,
                        'outlet_id'         => $opname->outlet_id,
                        'inventory_item_id' => $opnameItem->inventory_item_id,
                        'movement_type'     => InventoryMovementType::Opname,
                        'qty_change'        => $opnameItem->difference_qty,
                        'stock_before'      => $stockBefore,
                        'stock_after'       => $stockAfter,
                        'description'       => 'Penyesuaian stok dari Opname: ' . $opname->opname_number,
                        'reference_id'      => $opname->id,
                        'reference_type'    => StockOpname::class,
                        'created_by'        => $approver->id,
                        'created_at'        => now(),
                    ]);
                }
            }

            $opname->status = 'approved';
            $opname->approved_by = $approver->id;
            $opname->save();

            $this->activityLog->log($opname, 'approved', $approver);

            return $opname;
        });
    }

    public function rejectOpname(StockOpname $opname, array $data, User $rejecter): StockOpname
    {
        return DB::transaction(function () use ($opname, $data, $rejecter) {
            if ($opname->status !== 'pending_approval') {
                abort(403, 'Opname harus dalam status Menunggu Persetujuan untuk ditolak.');
            }

            $opname->status = 'rejected';
            $opname->notes = $data['notes'] ?? $opname->notes;
            $opname->approved_by = $rejecter->id;
            $opname->save();

            $this->activityLog->log($opname, 'rejected', $rejecter, ['notes' => $data['notes'] ?? null]);

            return $opname;
        });
    }
}
