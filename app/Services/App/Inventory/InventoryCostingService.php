<?php

namespace App\Services\App\Inventory;

use App\Enums\InventoryCostingMethod;
use App\Enums\InventoryMovementType;
use App\Models\Business;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryCostLayer;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryMovement;
use App\Models\Outlet;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class InventoryCostingService
{
    /**
     * Catat mutasi stok masuk (+) dan perbarui biaya perolehan (FIFO layer & Moving Average).
     */
    public function recordIncomingStock(
        Business $business,
        Outlet $outlet,
        InventoryItem $item,
        float $qty,
        float $unitCost,
        InventoryMovementType $movementType,
        ?Model $reference = null,
        ?string $description = null,
        ?User $user = null
    ): InventoryMovement {
        if ($qty <= 0) {
            throw new \InvalidArgumentException('Kuantitas stok masuk harus lebih besar dari 0.');
        }

        return DB::transaction(function () use (
            $business,
            $outlet,
            $item,
            $qty,
            $unitCost,
            $movementType,
            $reference,
            $description,
            $user
        ) {
            // 1. Ambil atau inisialisasi saldo stok berjalan
            $balance = InventoryBalance::firstOrCreate([
                'business_id' => $business->id,
                'outlet_id' => $outlet->id,
                'inventory_item_id' => $item->id,
            ], [
                'current_stock' => 0,
                'average_cost' => $unitCost,
                'last_cost' => $unitCost,
                'total_value' => 0,
            ]);

            $stockBefore = (float) $balance->current_stock;
            $stockAfter = $stockBefore + $qty;

            // 2. Hitung Moving Average baru
            $newAverageCost = $this->calculateNewMovingAverage($balance, $qty, $unitCost);
            $totalValueAfter = $stockAfter * $newAverageCost;

            // 3. Update Balance
            $balance->current_stock = $stockAfter;
            $balance->average_cost = $newAverageCost;
            $balance->last_cost = $unitCost;
            $balance->total_value = $totalValueAfter;
            $balance->save();

            // 4. Buat Layer Biaya Baru (FIFO tracking)
            InventoryCostLayer::create([
                'business_id' => $business->id,
                'outlet_id' => $outlet->id,
                'inventory_item_id' => $item->id,
                'purchase_price' => $unitCost,
                'qty_purchased' => $qty,
                'qty_remaining' => $qty,
                'reference_id' => $reference?->id,
                'reference_type' => $reference ? get_class($reference) : null,
                'created_at' => now(),
            ]);

            // 5. Catat Buku Besar Mutasi (Inventory Movement)
            $movement = InventoryMovement::create([
                'business_id' => $business->id,
                'outlet_id' => $outlet->id,
                'inventory_item_id' => $item->id,
                'movement_type' => $movementType,
                'qty_change' => +$qty,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'cost' => $unitCost,
                'unit_cost' => $unitCost,
                'total_cost' => $qty * $unitCost,
                'balance_value_after' => $totalValueAfter,
                'reference_id' => $reference?->id,
                'reference_type' => $reference ? get_class($reference) : null,
                'description' => $description ?? ($movementType->label().' '.$item->name),
                'created_by' => $user?->id,
                'created_at' => now(),
            ]);

            return $movement;
        });
    }

    /**
     * Catat mutasi stok keluar (-) dan alokasikan HPP/COGS (FIFO vs Moving Average).
     *
     * @return array{unit_cogs: float, total_cogs: float, movement: InventoryMovement}
     */
    public function recordOutgoingStock(
        Business $business,
        Outlet $outlet,
        InventoryItem $item,
        float $qty,
        InventoryMovementType $movementType,
        ?Model $reference = null,
        ?string $description = null,
        ?User $user = null,
        ?InventoryCostingMethod $costingMethodOverride = null,
        ?InventoryBalance $preloadedBalance = null,
    ): array {
        if ($qty <= 0) {
            throw new \InvalidArgumentException('Kuantitas stok keluar harus lebih besar dari 0.');
        }

        return DB::transaction(function () use (
            $business,
            $outlet,
            $item,
            $qty,
            $movementType,
            $reference,
            $description,
            $user,
            $costingMethodOverride,
            $preloadedBalance
        ) {
            $balance = $preloadedBalance ?? InventoryBalance::firstOrCreate([
                'business_id' => $business->id,
                'outlet_id' => $outlet->id,
                'inventory_item_id' => $item->id,
            ], [
                'current_stock' => 0,
                'average_cost' => 0,
                'last_cost' => 0,
                'total_value' => 0,
            ]);

            $stockBefore = (float) $balance->current_stock;
            $stockAfter = $stockBefore - $qty;

            $method = $costingMethodOverride ?? $business->getCostingMethod();

            $totalCogs = 0.0;
            $unitCogs = 0.0;

            if ($method === InventoryCostingMethod::FIFO) {
                // Alokasikan HPP berdasarkan konsumsi layer FIFO tertua
                $fifoResult = $this->consumeFifoLayers($business, $outlet, $item, $qty, $reference);
                $totalCogs = $fifoResult['total_cogs'];
                $unitCogs = $fifoResult['unit_cogs'];

                $totalValueAfter = max(0, $balance->total_value - $totalCogs);
                $balance->current_stock = $stockAfter;
                $balance->total_value = $totalValueAfter;
                if ($stockAfter > 0) {
                    $balance->average_cost = $totalValueAfter / $stockAfter;
                }
            } else {
                // Moving Average: gunakan average_cost saat ini
                $currentAvgCost = (float) $balance->average_cost;
                if ($currentAvgCost <= 0) {
                    $currentAvgCost = (float) $balance->last_cost;
                }
                $unitCogs = $currentAvgCost;
                $totalCogs = $qty * $unitCogs;

                $totalValueAfter = max(0, $stockAfter * $currentAvgCost);
                $balance->current_stock = $stockAfter;
                $balance->total_value = $totalValueAfter;

                // Tetap kurangi layer FIFO di latar belakang agar antrean FIFO selalu sinkron jika user beralih mode
                $this->consumeFifoLayers($business, $outlet, $item, $qty, $reference);
            }

            $balance->save();

            // Catat movement buku besar
            $movement = InventoryMovement::create([
                'business_id' => $business->id,
                'outlet_id' => $outlet->id,
                'inventory_item_id' => $item->id,
                'movement_type' => $movementType,
                'qty_change' => -$qty,
                'stock_before' => $stockBefore,
                'stock_after' => $stockAfter,
                'cost' => $unitCogs,
                'unit_cost' => $unitCogs,
                'total_cost' => $totalCogs,
                'balance_value_after' => $totalValueAfter,
                'reference_id' => $reference?->id,
                'reference_type' => $reference ? get_class($reference) : null,
                'description' => $description ?? ($movementType->label().' '.$item->name),
                'created_by' => $user?->id,
                'created_at' => now(),
            ]);

            return [
                'unit_cogs' => $unitCogs,
                'total_cogs' => $totalCogs,
                'movement' => $movement,
            ];
        });
    }

    /**
     * Konsumsi layer FIFO tertua dan kembalikan total HPP yang teralokasi.
     *
     * @return array{total_cogs: float, unit_cogs: float, layers_used: array}
     */
    public function consumeFifoLayers(
        Business $business,
        Outlet $outlet,
        InventoryItem $item,
        float $qtyToDeduct,
        ?Model $reference = null
    ): array {
        $remainingToDeduct = $qtyToDeduct;
        $totalCogs = 0.0;
        $layersUsed = [];

        $layers = InventoryCostLayer::query()
            ->where('outlet_id', $outlet->id)
            ->where('inventory_item_id', $item->id)
            ->where('qty_remaining', '>', 0)
            ->orderBy('created_at', 'asc')
            ->get();

        $lastLayerPrice = 0.0;

        foreach ($layers as $layer) {
            if ($remainingToDeduct <= 0) {
                break;
            }

            $layerRemaining = (float) $layer->qty_remaining;
            $layerPrice = (float) $layer->purchase_price;
            $lastLayerPrice = $layerPrice;

            if ($layerRemaining <= $remainingToDeduct) {
                // Layer habis terpakai seluruhnya
                $cogsForLayer = $layerRemaining * $layerPrice;
                $totalCogs += $cogsForLayer;
                $remainingToDeduct -= $layerRemaining;

                $layersUsed[] = [
                    'layer_id' => $layer->id,
                    'qty_used' => $layerRemaining,
                    'price' => $layerPrice,
                ];

                $layer->qty_remaining = 0;
                $layer->save();
            } else {
                // Layer terpakai sebagian
                $cogsForLayer = $remainingToDeduct * $layerPrice;
                $totalCogs += $cogsForLayer;
                $layer->qty_remaining = $layerRemaining - $remainingToDeduct;
                $layer->save();

                $layersUsed[] = [
                    'layer_id' => $layer->id,
                    'qty_used' => $remainingToDeduct,
                    'price' => $layerPrice,
                ];

                $remainingToDeduct = 0;
            }
        }

        // Jika kuantitas yang diminta melebihi sisa seluruh layer aktif (edge case short inventory),
        // gunakan harga layer terakhir atau average_cost balance
        if ($remainingToDeduct > 0) {
            $fallbackPrice = $lastLayerPrice > 0
                ? $lastLayerPrice
                : (float) (InventoryBalance::where('outlet_id', $outlet->id)->where('inventory_item_id', $item->id)->value('average_cost') ?? 0);

            $totalCogs += ($remainingToDeduct * $fallbackPrice);
        }

        $unitCogs = $qtyToDeduct > 0 ? ($totalCogs / $qtyToDeduct) : 0.0;

        return [
            'total_cogs' => $totalCogs,
            'unit_cogs' => $unitCogs,
            'layers_used' => $layersUsed,
        ];
    }

    /**
     * Memperbarui stok awal untuk barang yang belum memiliki mutasi operasional lanjutan.
     */
    public function updateInitialStock(
        Business $business,
        Outlet $outlet,
        InventoryItem $item,
        float $newQty,
        float $newUnitCost,
        User $user
    ): InventoryMovement {
        if ($newQty <= 0) {
            throw new \InvalidArgumentException('Kuantitas stok awal harus lebih besar dari 0.');
        }
        if ($newUnitCost < 0) {
            throw new \InvalidArgumentException('Harga modal beli tidak boleh bernilai negatif.');
        }

        return DB::transaction(function () use (
            $business,
            $outlet,
            $item,
            $newQty,
            $newUnitCost,
            $user
        ) {
            // Validasi: pastikan tidak ada mutasi selain InitialStock
            $hasOtherMovements = InventoryMovement::where('inventory_item_id', $item->id)
                ->where('outlet_id', $outlet->id)
                ->where('movement_type', '!=', InventoryMovementType::InitialStock)
                ->exists();

            if ($hasOtherMovements) {
                throw new \InvalidArgumentException("Stok awal '{$item->name}' tidak dapat diubah karena sudah memiliki riwayat mutasi operasional.");
            }

            $balance = InventoryBalance::firstOrCreate([
                'business_id' => $business->id,
                'outlet_id' => $outlet->id,
                'inventory_item_id' => $item->id,
            ], [
                'current_stock' => 0,
                'average_cost' => $newUnitCost,
                'last_cost' => $newUnitCost,
                'total_value' => 0,
            ]);

            $totalValueAfter = $newQty * $newUnitCost;
            $balance->current_stock = $newQty;
            $balance->average_cost = $newUnitCost;
            $balance->last_cost = $newUnitCost;
            $balance->total_value = $totalValueAfter;
            $balance->save();

            // Cari layer FIFO awal atau buat baru jika belum ada
            $layer = InventoryCostLayer::where('outlet_id', $outlet->id)
                ->where('inventory_item_id', $item->id)
                ->orderBy('created_at', 'asc')
                ->first();

            if ($layer) {
                $layer->qty_purchased = $newQty;
                $layer->qty_remaining = $newQty;
                $layer->purchase_price = $newUnitCost;
                $layer->business_id = $business->id;
                $layer->save();
            } else {
                $layer = InventoryCostLayer::create([
                    'business_id' => $business->id,
                    'outlet_id' => $outlet->id,
                    'inventory_item_id' => $item->id,
                    'purchase_price' => $newUnitCost,
                    'qty_purchased' => $newQty,
                    'qty_remaining' => $newQty,
                    'reference_type' => 'initial_stock_update',
                    'created_at' => now(),
                ]);
            }

            // Cari movement awal atau buat baru
            $movement = InventoryMovement::where('inventory_item_id', $item->id)
                ->where('outlet_id', $outlet->id)
                ->where('movement_type', InventoryMovementType::InitialStock)
                ->first();

            if ($movement) {
                $movement->qty_change = +$newQty;
                $movement->stock_before = 0;
                $movement->stock_after = $newQty;
                $movement->cost = $newUnitCost;
                $movement->unit_cost = $newUnitCost;
                $movement->total_cost = $totalValueAfter;
                $movement->balance_value_after = $totalValueAfter;
                $movement->description = 'Input Stok Awal (Diperbarui)';
                $movement->created_by = $user->id;
                $movement->save();
            } else {
                $movement = InventoryMovement::create([
                    'business_id' => $business->id,
                    'outlet_id' => $outlet->id,
                    'inventory_item_id' => $item->id,
                    'movement_type' => InventoryMovementType::InitialStock,
                    'qty_change' => +$newQty,
                    'stock_before' => 0,
                    'stock_after' => $newQty,
                    'cost' => $newUnitCost,
                    'unit_cost' => $newUnitCost,
                    'total_cost' => $totalValueAfter,
                    'balance_value_after' => $totalValueAfter,
                    'description' => 'Input Stok Awal '.$item->name,
                    'created_by' => $user->id,
                    'created_at' => now(),
                ]);
            }

            return $movement;
        });
    }

    /**
     * Hitung harga rata-rata bergerak (Moving Average) baru setelah stok masuk.
     */
    public function calculateNewMovingAverage(InventoryBalance $balance, float $incomingQty, float $incomingCost): float
    {
        $currentStock = (float) $balance->current_stock;
        $currentAvgCost = (float) $balance->average_cost;

        if ($currentStock <= 0 || $currentAvgCost <= 0) {
            return $incomingCost;
        }

        $currentTotalValue = $currentStock * $currentAvgCost;
        $incomingTotalValue = $incomingQty * $incomingCost;
        $newTotalStock = $currentStock + $incomingQty;

        if ($newTotalStock <= 0) {
            return $incomingCost;
        }

        return ($currentTotalValue + $incomingTotalValue) / $newTotalStock;
    }

    /**
     * Mengembalikan layer FIFO dengan nilai unit_cogs awal saat pembatalan/retur faktur.
     */
    public function restoreFifoCostLayer(
        Business $business,
        Outlet $outlet,
        InventoryItem $item,
        float $qty,
        float $unitCogs,
        ?Model $reference = null,
        ?User $user = null
    ): InventoryMovement {
        return $this->recordIncomingStock(
            $business,
            $outlet,
            $item,
            $qty,
            $unitCogs,
            InventoryMovementType::SaleReturn,
            $reference,
            'Restorasi Layer Cost FIFO - Pembatalan Transaksi',
            $user
        );
    }

    /**
     * Alihkan metode costing bisnis secara aman tanpa merusak riwayat masa lalu.
     */
    public function switchCostingMethod(Business $business, InventoryCostingMethod $newMethod): void
    {
        DB::transaction(function () use ($business, $newMethod) {
            if ($newMethod === InventoryCostingMethod::FIFO) {
                // Saat beralih ke FIFO: pastikan item dengan current_stock > 0 memiliki baseline active layer
                $balances = InventoryBalance::where('business_id', $business->id)
                    ->where('current_stock', '>', 0)
                    ->get();

                foreach ($balances as $balance) {
                    $activeRemaining = (float) InventoryCostLayer::where('outlet_id', $balance->outlet_id)
                        ->where('inventory_item_id', $balance->inventory_item_id)
                        ->where('qty_remaining', '>', 0)
                        ->sum('qty_remaining');

                    $shortage = ((float) $balance->current_stock) - $activeRemaining;

                    if ($shortage > 0) {
                        $cost = (float) $balance->average_cost > 0
                            ? (float) $balance->average_cost
                            : (float) $balance->last_cost;

                        InventoryCostLayer::create([
                            'business_id' => $business->id,
                            'outlet_id' => $balance->outlet_id,
                            'inventory_item_id' => $balance->inventory_item_id,
                            'purchase_price' => $cost,
                            'qty_purchased' => $shortage,
                            'qty_remaining' => $shortage,
                            'reference_type' => 'method_switch_baseline',
                            'created_at' => now(),
                        ]);
                    }
                }
            }

            $settings = $business->settings ?? [];
            $settings['inventory_costing_method'] = $newMethod->value;
            $business->settings = $settings;
            $business->save();
        });
    }
}
