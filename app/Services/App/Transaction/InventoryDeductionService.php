<?php

namespace App\Services\App\Transaction;

use App\Enums\InventoryMovementType;
use App\Models\Business;
use App\Models\Inventory\InventoryItem;
use App\Models\Sales\Transaction;
use App\Services\App\Inventory\InventoryCostingService;
use Illuminate\Support\Facades\DB;

class InventoryDeductionService
{
    public function __construct(
        protected InventoryCostingService $costingService
    ) {}

    public function deductFromTransaction(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $transaction->load([
                'items.product.inventoryItems',
                'items.product.activeRecipeVersion.recipeItems.inventoryItem',
                'items.modifiers.modifierOption.modifierRecipeItems.inventoryItem',
                'outlet.business',
            ]);

            $outlet = $transaction->outlet;
            $business = $outlet?->business ?? Business::find($transaction->business_id ?? auth()->user()?->business_id);

            if (! $outlet || ! $business) {
                return;
            }

            foreach ($transaction->items as $item) {
                $itemTotalCogs = 0.0;
                $itemQty = (float) $item->qty;

                // 1. Skenario F&B: Produk Ber-Resep (BOM - Bill of Materials)
                if ($item->product && $item->product->has_recipe && $item->product->activeRecipeVersion) {
                    foreach ($item->product->activeRecipeVersion->recipeItems as $recipeItem) {
                        $rawItem = $recipeItem->inventoryItem;
                        if ($rawItem && $rawItem->track_inventory) {
                            $rawQtyToDeduct = ((float) $recipeItem->qty) * $itemQty;
                            if ($rawQtyToDeduct > 0) {
                                $result = $this->costingService->recordOutgoingStock(
                                    business: $business,
                                    outlet: $outlet,
                                    item: $rawItem,
                                    qty: $rawQtyToDeduct,
                                    movementType: InventoryMovementType::RecipeDeduction,
                                    reference: $transaction,
                                    description: 'Bahan Resep: '.$item->product_name.' ('.($transaction->receipt_number ?? $transaction->id).')',
                                    user: auth()->user()
                                );
                                $itemTotalCogs += $result['total_cogs'];
                            }
                        }
                    }

                    // Deduksi Modifier / Topping Recipe Items jika ada
                    foreach ($item->modifiers as $itemModifier) {
                        if ($itemModifier->modifierOption && $itemModifier->modifierOption->modifierRecipeItems) {
                            foreach ($itemModifier->modifierOption->modifierRecipeItems as $modRecipeItem) {
                                $rawModItem = $modRecipeItem->inventoryItem;
                                if ($rawModItem && $rawModItem->track_inventory) {
                                    $modQtyToDeduct = ((float) $modRecipeItem->qty) * $itemQty;
                                    if ($modQtyToDeduct > 0) {
                                        $result = $this->costingService->recordOutgoingStock(
                                            business: $business,
                                            outlet: $outlet,
                                            item: $rawModItem,
                                            qty: $modQtyToDeduct,
                                            movementType: InventoryMovementType::RecipeDeduction,
                                            reference: $transaction,
                                            description: 'Topping Resep: '.$itemModifier->modifierOption->name.' ('.($transaction->receipt_number ?? $transaction->id).')',
                                            user: auth()->user()
                                        );
                                        $itemTotalCogs += $result['total_cogs'];
                                    }
                                }
                            }
                        }
                    }
                } else {
                    // 2. Skenario Retail: Produk Langsung (Direct Variant SKU / Basic Inventory Item)
                    $inventoryItems = collect();

                    if ($item->inventory_item_id) {
                        $invItem = InventoryItem::find($item->inventory_item_id);
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

                    foreach ($inventoryItems as $inventoryItem) {
                        if (! $inventoryItem->track_inventory) {
                            continue;
                        }

                        if ($itemQty > 0) {
                            $result = $this->costingService->recordOutgoingStock(
                                business: $business,
                                outlet: $outlet,
                                item: $inventoryItem,
                                qty: $itemQty,
                                movementType: InventoryMovementType::Sale,
                                reference: $transaction,
                                description: 'Penjualan: '.$item->product_name.' ('.($transaction->receipt_number ?? $transaction->id).')',
                                user: auth()->user()
                            );
                            $itemTotalCogs += $result['total_cogs'];
                        }
                    }
                }

                // 3. Simpan snapshot COGS pada transaction_items untuk pelaporan Laba Rugi instan
                $unitCogs = $itemQty > 0 ? ($itemTotalCogs / $itemQty) : 0.0;
                $item->unit_cogs = $unitCogs;
                $item->cogs_amount = $itemTotalCogs;
                $item->save();
            }
        });
    }

    public function restoreFromTransaction(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction) {
            $transaction->load([
                'items.product.inventoryItems',
                'items.product.activeRecipeVersion.recipeItems.inventoryItem',
                'items.modifiers.modifierOption.modifierRecipeItems.inventoryItem',
                'outlet.business',
            ]);

            $outlet = $transaction->outlet;
            $business = $outlet?->business ?? Business::find($transaction->business_id ?? auth()->user()?->business_id);

            if (! $outlet || ! $business) {
                return;
            }

            foreach ($transaction->items as $item) {
                $itemQty = (float) $item->qty;

                // Skenario Resep F&B
                if ($item->product && $item->product->has_recipe && $item->product->activeRecipeVersion) {
                    foreach ($item->product->activeRecipeVersion->recipeItems as $recipeItem) {
                        $rawItem = $recipeItem->inventoryItem;
                        if ($rawItem && $rawItem->track_inventory) {
                            $rawQty = ((float) $recipeItem->qty) * $itemQty;
                            if ($rawQty > 0) {
                                $unitCost = (float) ($rawItem->balances()->where('outlet_id', $outlet->id)->value('average_cost') ?? 0);
                                $this->costingService->recordIncomingStock(
                                    business: $business,
                                    outlet: $outlet,
                                    item: $rawItem,
                                    qty: $rawQty,
                                    unitCost: $unitCost,
                                    movementType: InventoryMovementType::RecipeReturn,
                                    reference: $transaction,
                                    description: 'Pembatalan Resep: '.$item->product_name.' ('.($transaction->receipt_number ?? $transaction->id).')',
                                    user: auth()->user()
                                );
                            }
                        }
                    }
                } else {
                    // Skenario Retail
                    $inventoryItems = collect();

                    if ($item->inventory_item_id) {
                        $invItem = InventoryItem::find($item->inventory_item_id);
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

                    foreach ($inventoryItems as $inventoryItem) {
                        if (! $inventoryItem->track_inventory) {
                            continue;
                        }

                        $unitCost = (float) $item->unit_cogs > 0
                            ? (float) $item->unit_cogs
                            : (float) ($inventoryItem->balances()->where('outlet_id', $outlet->id)->value('average_cost') ?? 0);

                        $this->costingService->recordIncomingStock(
                            business: $business,
                            outlet: $outlet,
                            item: $inventoryItem,
                            qty: $itemQty,
                            unitCost: $unitCost,
                            movementType: InventoryMovementType::SaleReturn,
                            reference: $transaction,
                            description: 'Retur/Pembatalan Penjualan: '.$item->product_name.' ('.($transaction->receipt_number ?? $transaction->id).')',
                            user: auth()->user()
                        );
                    }
                }
            }
        });
    }
}
