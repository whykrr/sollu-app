<?php

namespace App\Services\Transaction;

use App\Enums\InventoryMovementType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryMovement;
use App\Models\Sales\Transaction;
use Illuminate\Support\Facades\DB;

class InventoryDeductionService
{
    public function deductFromTransaction(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $transaction->load(['items.product.inventoryItems', 'outlet']);

            $outletId = $transaction->outlet_id;
            $businessId = $transaction->outlet?->business_id ?? $transaction->business_id ?? auth()->user()?->business_id;

            foreach ($transaction->items as $item) {
                $inventoryItems = collect();

                if ($item->inventory_item_id) {
                    $invItem = \App\Models\Inventory\InventoryItem::find($item->inventory_item_id);
                    if ($invItem) {
                        $inventoryItems->push($invItem);
                    }
                }

                if ($inventoryItems->isEmpty() && $item->product) {
                    $product = $item->product;
                    if ($product->has_variant && $item->variant_group_option_id) {
                        $inventoryItems = $product->inventoryItems()
                            ->whereHas('variantGroupOptions', function ($q) use ($item) {
                                $q->where('variant_group_options.id', $item->variant_group_option_id);
                            })->get();
                    } else {
                        $inventoryItems = $product->inventoryItems;
                    }
                }

                if ($inventoryItems->isEmpty()) {
                    continue;
                }

                foreach ($inventoryItems as $inventoryItem) {
                    if (! $inventoryItem->track_inventory) {
                        continue;
                    }

                    $balance = InventoryBalance::firstOrCreate(
                        [
                            'business_id' => $businessId,
                            'outlet_id' => $outletId,
                            'inventory_item_id' => $inventoryItem->id,
                        ],
                        [
                            'current_stock' => 0,
                        ]
                    );

                    $stockBefore = floatval($balance->current_stock);
                    $qtyDeducted = floatval($item->qty);
                    $stockAfter = $stockBefore - $qtyDeducted;

                    $balance->update(['current_stock' => $stockAfter]);

                    InventoryMovement::create([
                        'business_id' => $businessId,
                        'outlet_id' => $outletId,
                        'inventory_item_id' => $inventoryItem->id,
                        'movement_type' => InventoryMovementType::Sale->value,
                        'qty_change' => -$qtyDeducted,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'cost' => 0,
                        'reference_id' => $transaction->id,
                        'reference_type' => Transaction::class,
                        'description' => 'Penjualan Invoice: '.($transaction->receipt_number ?? $transaction->id),
                        'created_by' => auth()->id() ?? null,
                        'created_at' => now(),
                    ]);
                }
            }
        });
    }

    public function restoreFromTransaction(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $transaction->load(['items.product.inventoryItems', 'outlet']);

            $outletId = $transaction->outlet_id;
            $businessId = $transaction->outlet?->business_id ?? $transaction->business_id ?? auth()->user()?->business_id;

            foreach ($transaction->items as $item) {
                $inventoryItems = collect();

                if ($item->inventory_item_id) {
                    $invItem = \App\Models\Inventory\InventoryItem::find($item->inventory_item_id);
                    if ($invItem) {
                        $inventoryItems->push($invItem);
                    }
                }

                if ($inventoryItems->isEmpty() && $item->product) {
                    $product = $item->product;
                    if ($product->has_variant && $item->variant_group_option_id) {
                        $inventoryItems = $product->inventoryItems()
                            ->whereHas('variantGroupOptions', function ($q) use ($item) {
                                $q->where('variant_group_options.id', $item->variant_group_option_id);
                            })->get();
                    } else {
                        $inventoryItems = $product->inventoryItems;
                    }
                }

                if ($inventoryItems->isEmpty()) {
                    continue;
                }

                foreach ($inventoryItems as $inventoryItem) {
                    if (! $inventoryItem->track_inventory) {
                        continue;
                    }

                    $balance = InventoryBalance::firstOrCreate(
                        [
                            'business_id' => $businessId,
                            'outlet_id' => $outletId,
                            'inventory_item_id' => $inventoryItem->id,
                        ],
                        [
                            'current_stock' => 0,
                        ]
                    );

                    $stockBefore = floatval($balance->current_stock);
                    $qtyRestored = floatval($item->qty);
                    $stockAfter = $stockBefore + $qtyRestored;

                    $balance->update(['current_stock' => $stockAfter]);

                    InventoryMovement::create([
                        'business_id' => $businessId,
                        'outlet_id' => $outletId,
                        'inventory_item_id' => $inventoryItem->id,
                        'movement_type' => InventoryMovementType::Sale->value,
                        'qty_change' => +$qtyRestored,
                        'stock_before' => $stockBefore,
                        'stock_after' => $stockAfter,
                        'cost' => 0,
                        'reference_id' => $transaction->id,
                        'reference_type' => Transaction::class,
                        'description' => 'Pembatalan Invoice: '.($transaction->receipt_number ?? $transaction->id),
                        'created_by' => auth()->id() ?? null,
                        'created_at' => now(),
                    ]);
                }
            }
        });
    }
}
