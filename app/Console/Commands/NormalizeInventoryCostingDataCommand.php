<?php

namespace App\Console\Commands;

use App\Models\Business;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryCostLayer;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryMovement;
use App\Models\Sales\TransactionItem;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NormalizeInventoryCostingDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'inventory:normalize-costing {--business= : Filter per ID bisnis spesifik}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Normalisasi dan backfill data historis saldo stok, layer FIFO, average cost, mutasi, dan snapshot COGS transaksi.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('Memulai normalisasi data persediaan, cost layers, dan COGS...');

        $businessId = $this->option('business');
        $businesses = Business::when($businessId, fn ($q) => $q->where('id', $businessId))->get();

        if ($businesses->isEmpty()) {
            $this->warn('Tidak ada bisnis yang ditemukan untuk dinormalisasi.');

            return self::SUCCESS;
        }

        foreach ($businesses as $business) {
            $this->line("Memproses bisnis: <info>{$business->name}</info> ({$business->id})");

            DB::transaction(function () use ($business) {
                // 1. Rekonsiliasi business_id pada inventory_cost_layers yang null
                DB::statement('
                    UPDATE inventory_cost_layers
                    SET business_id = outlets.business_id
                    FROM outlets
                    WHERE inventory_cost_layers.outlet_id = outlets.id
                    AND inventory_cost_layers.business_id IS NULL
                    AND outlets.business_id = ?
                ', [$business->id]);

                // 2. Normalisasi inventory_balances dan Cost Layers
                $items = InventoryItem::where('business_id', $business->id)->get();

                foreach ($items as $item) {
                    $baselineCost = $this->resolveBaselineCost($item);

                    $balances = InventoryBalance::where('business_id', $business->id)
                        ->where('inventory_item_id', $item->id)
                        ->get();

                    foreach ($balances as $balance) {
                        $avgCost = (float) $balance->average_cost > 0
                            ? (float) $balance->average_cost
                            : $baselineCost;

                        $lastCost = (float) $balance->last_cost > 0
                            ? (float) $balance->last_cost
                            : $baselineCost;

                        $currentStock = (float) $balance->current_stock;
                        $totalValue = max(0, $currentStock * $avgCost);

                        $balance->average_cost = $avgCost;
                        $balance->last_cost = $lastCost;
                        $balance->total_value = $totalValue;
                        $balance->save();

                        // Cek apakah ada sisa active layer untuk item ini jika stok positif
                        if ($currentStock > 0) {
                            $activeLayerSum = (float) InventoryCostLayer::where('outlet_id', $balance->outlet_id)
                                ->where('inventory_item_id', $item->id)
                                ->where('qty_remaining', '>', 0)
                                ->sum('qty_remaining');

                            $shortfall = $currentStock - $activeLayerSum;
                            if ($shortfall > 0) {
                                InventoryCostLayer::create([
                                    'business_id' => $business->id,
                                    'outlet_id' => $balance->outlet_id,
                                    'inventory_item_id' => $item->id,
                                    'purchase_price' => $avgCost,
                                    'qty_purchased' => $shortfall,
                                    'qty_remaining' => $shortfall,
                                    'reference_type' => 'normalization_baseline',
                                    'created_at' => now(),
                                ]);
                            }
                        }
                    }
                }

                // 3. Normalisasi nilai unit_cost, total_cost, balance_value_after pada inventory_movements historis
                $movements = InventoryMovement::where('business_id', $business->id)
                    ->where(function ($q) {
                        $q->where('unit_cost', 0)
                            ->orWhere('total_cost', 0);
                    })
                    ->get();

                foreach ($movements as $movement) {
                    $itemBalance = InventoryBalance::where('outlet_id', $movement->outlet_id)
                        ->where('inventory_item_id', $movement->inventory_item_id)
                        ->first();

                    $cost = (float) $movement->cost > 0
                        ? (float) $movement->cost
                        : ((float) ($itemBalance?->average_cost ?? $itemBalance?->last_cost ?? 0));

                    $qtyAbs = abs((float) $movement->qty_change);
                    $totalCost = $qtyAbs * $cost;
                    $balanceValueAfter = max(0, ((float) $movement->stock_after) * $cost);

                    $movement->unit_cost = $cost;
                    $movement->total_cost = $totalCost;
                    $movement->balance_value_after = $balanceValueAfter;
                    $movement->save();
                }

                // 4. Backfill snapshot unit_cogs dan cogs_amount pada transaction_items historis
                $outletIds = $business->outlets()->pluck('id');

                $trxItems = TransactionItem::whereHas('transaction', fn ($q) => $q->whereIn('outlet_id', $outletIds))
                    ->with('transaction')
                    ->where(function ($q) {
                        $q->where('cogs_amount', 0)
                            ->orWhereNull('cogs_amount');
                    })
                    ->get();

                foreach ($trxItems as $trxItem) {
                    $unitCogs = 0.0;
                    $outletId = $trxItem->transaction?->outlet_id;

                    if ($trxItem->inventory_item_id) {
                        $bal = InventoryBalance::query()
                            ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId))
                            ->where('inventory_item_id', $trxItem->inventory_item_id)
                            ->first() ?? InventoryBalance::where('business_id', $business->id)
                            ->where('inventory_item_id', $trxItem->inventory_item_id)
                            ->first();

                        $unitCogs = (float) ($bal?->average_cost ?? $bal?->last_cost ?? 0);
                    } elseif ($trxItem->product_id) {
                        // Cek apakah produk memiliki inventory item terhubung
                        $invItem = InventoryItem::where('business_id', $business->id)
                            ->where('product_id', $trxItem->product_id)
                            ->first();

                        if ($invItem) {
                            $bal = InventoryBalance::query()
                                ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId))
                                ->where('inventory_item_id', $invItem->id)
                                ->first() ?? InventoryBalance::where('business_id', $business->id)
                                ->where('inventory_item_id', $invItem->id)
                                ->first();

                            $unitCogs = (float) ($bal?->average_cost ?? $bal?->last_cost ?? 0);
                        }
                    }

                    if ($unitCogs > 0) {
                        $trxItem->unit_cogs = $unitCogs;
                        $trxItem->cogs_amount = ((float) $trxItem->qty) * $unitCogs;
                        $trxItem->save();
                    }
                }
            });

            $this->info("✓ Sukses normalisasi untuk {$business->name}");
        }

        $this->info('Normalisasi seluruh data persediaan dan transaksi selesai!');

        return self::SUCCESS;
    }

    /**
     * Cari estimasi harga pokok dasar per unit dari histori PO, Supplier, atau Adjustment.
     */
    protected function resolveBaselineCost(InventoryItem $item): float
    {
        // 1. Cek riwayat cost layers yang pernah ada
        $layerPrice = InventoryCostLayer::where('inventory_item_id', $item->id)
            ->where('purchase_price', '>', 0)
            ->latest('created_at')
            ->value('purchase_price');

        if ($layerPrice && (float) $layerPrice > 0) {
            return (float) $layerPrice;
        }

        // 2. Cek harga pembelian di purchase order items
        $poPrice = DB::table('purchase_order_items')
            ->where('inventory_item_id', $item->id)
            ->where('purchase_price', '>', 0)
            ->latest('id')
            ->value('purchase_price');

        if ($poPrice && (float) $poPrice > 0) {
            return (float) $poPrice;
        }

        // 3. Cek supplier last purchase price
        $suppPrice = DB::table('supplier_inventory_items')
            ->where('inventory_item_id', $item->id)
            ->where('last_purchase_price', '>', 0)
            ->value('last_purchase_price');

        if ($suppPrice && (float) $suppPrice > 0) {
            return (float) $suppPrice;
        }

        // 4. Cek stock adjustment items
        $adjPrice = DB::table('stock_adjustment_items')
            ->where('inventory_item_id', $item->id)
            ->where('unit_cost', '>', 0)
            ->latest('id')
            ->value('unit_cost');

        if ($adjPrice && (float) $adjPrice > 0) {
            return (float) $adjPrice;
        }

        return 0.0;
    }
}
