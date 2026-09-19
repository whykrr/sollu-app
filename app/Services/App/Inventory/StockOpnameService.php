<?php

namespace App\Services\App\Inventory;

use App\Enums\InventoryMovementType;
use App\Enums\StockOpnameStatus;
use App\Models\Inventory\StockOpname;
use App\Models\User;
use App\Services\App\Master\ActivityLogService;
use Illuminate\Support\Facades\DB;

class StockOpnameService
{
    public function __construct(
        protected ActivityLogService $activityLog,
        protected InventoryCostingService $costingService
    ) {}

    public function createOpname(array $data, User $creator): StockOpname
    {
        return DB::transaction(function () use ($data, $creator) {
            $data['business_id'] = $creator->business_id;
            $data['created_by'] = $creator->id;

            $count = StockOpname::where('business_id', $creator->business_id)
                ->whereMonth('created_at', now()->month)
                ->count();
            $data['opname_number'] = 'OP-'.now()->format('Ym').'-'.str_pad($count + 1, 3, '0', STR_PAD_LEFT);
            $data['status'] = StockOpnameStatus::InProgress;

            $opname = StockOpname::create($data);

            foreach ($data['items'] ?? [] as $itemData) {
                $opname->items()->create([
                    'inventory_item_id' => $itemData['inventory_item_id'],
                    'system_qty' => $itemData['system_qty'],
                    'actual_qty' => $itemData['actual_qty'] ?? $itemData['system_qty'], // Default if not filled yet
                    'difference_qty' => ($itemData['actual_qty'] ?? $itemData['system_qty']) - $itemData['system_qty'],
                ]);
            }

            $this->activityLog->log($opname, 'created', $creator);

            return $opname;
        });
    }

    public function updateOpname(StockOpname $opname, array $data, User $updater): StockOpname
    {
        return DB::transaction(function () use ($opname, $data, $updater) {
            if ($opname->status !== StockOpnameStatus::InProgress) {
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
                        'system_qty' => $systemQty,
                        'actual_qty' => $actualQty,
                        'difference_qty' => $actualQty - $systemQty,
                    ]);
                }
            }

            // Mark as pending approval after update
            $opname->update(['status' => StockOpnameStatus::PendingApproval]);

            $this->activityLog->log($opname, 'submitted', $updater);

            return $opname;
        });
    }

    public function completeOpname(StockOpname $opname, array $data, User $approver): StockOpname
    {
        return DB::transaction(function () use ($opname, $data, $approver) {
            if ($opname->status !== StockOpnameStatus::PendingApproval) {
                abort(403, 'Opname harus dalam status Menunggu Persetujuan.');
            }

            $opname->load(['outlet.business', 'items.inventoryItem']);
            $outlet = $opname->outlet;
            $business = $outlet?->business ?? $approver->business;

            // Optional: Re-update items if they were adjusted during approval
            if (isset($data['items'])) {
                $opname->items()->delete();
                foreach ($data['items'] as $itemData) {
                    $actualQty = (float) $itemData['actual_qty'];
                    $systemQty = (float) $itemData['system_qty'];

                    $opname->items()->create([
                        'inventory_item_id' => $itemData['inventory_item_id'],
                        'system_qty' => $systemQty,
                        'actual_qty' => $actualQty,
                        'difference_qty' => $actualQty - $systemQty,
                    ]);
                }

                $opname->load('items.inventoryItem');
            }

            // Execute balance adjustment via Costing Service
            foreach ($opname->items as $opnameItem) {
                $diffQty = (float) $opnameItem->difference_qty;
                $invItem = $opnameItem->inventoryItem;

                if ($diffQty > 0) {
                    // Selisih Lebih: Masuk stok (+), bentuk layer FIFO dan update moving average
                    $unitCost = (float) ($invItem->balances()->where('outlet_id', $outlet->id)->value('average_cost') ?? 0);
                    $this->costingService->recordIncomingStock(
                        business: $business,
                        outlet: $outlet,
                        item: $invItem,
                        qty: $diffQty,
                        unitCost: $unitCost,
                        movementType: InventoryMovementType::OpnameSurplus,
                        reference: $opname,
                        description: 'Selisih Lebih Opname: '.$opname->opname_number,
                        user: $approver
                    );
                } elseif ($diffQty < 0) {
                    // Selisih Kurang: Keluar stok (-), potong layer FIFO dan catat beban selisih opname
                    $this->costingService->recordOutgoingStock(
                        business: $business,
                        outlet: $outlet,
                        item: $invItem,
                        qty: abs($diffQty),
                        movementType: InventoryMovementType::OpnameDeficit,
                        reference: $opname,
                        description: 'Selisih Kurang Opname: '.$opname->opname_number,
                        user: $approver
                    );
                }
            }

            $opname->status = StockOpnameStatus::Approved;
            $opname->approved_by = $approver->id;
            $opname->save();

            $this->activityLog->log($opname, 'approved', $approver);

            return $opname;
        });
    }

    public function rejectOpname(StockOpname $opname, array $data, User $rejecter): StockOpname
    {
        return DB::transaction(function () use ($opname, $data, $rejecter) {
            if ($opname->status !== StockOpnameStatus::PendingApproval) {
                abort(403, 'Opname harus dalam status Menunggu Persetujuan untuk ditolak.');
            }

            $opname->status = StockOpnameStatus::Rejected;
            $opname->notes = $data['notes'] ?? $opname->notes;
            $opname->approved_by = $rejecter->id;
            $opname->save();

            $this->activityLog->log($opname, 'rejected', $rejecter, ['notes' => $data['notes'] ?? null]);

            return $opname;
        });
    }
}
