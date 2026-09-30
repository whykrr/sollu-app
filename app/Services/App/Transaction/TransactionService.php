<?php

declare(strict_types=1);

namespace App\Services\App\Transaction;

use App\Enums\InventoryMovementType;
use App\Models\Outlet;
use App\Models\OutletSetting;
use App\Models\Sales\Transaction;
use App\Models\User;
use App\Services\App\Inventory\InventoryCostingService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TransactionService
{
    public function __construct(
        private readonly InventoryCostingService $costingService,
    ) {}

    /**
     * Generate Nomor Transaksi (TRX/YYYYMM/XXXX)
     */
    public function generateTransactionNumber(Outlet $outlet, \DateTimeInterface $date): string
    {
        $prefix = 'TRX/'.$date->format('Ym').'/';
        $lastTx = Transaction::where('outlet_id', $outlet->id)
            ->where('transaction_number', 'like', $prefix.'%')
            ->orderBy('transaction_number', 'desc')
            ->lockForUpdate()
            ->first();

        $sequence = 1;
        if ($lastTx) {
            $lastSequence = (int) substr($lastTx->transaction_number, -4);
            $sequence = $lastSequence + 1;
        }

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate Nomor Faktur B2B (INV/YYYYMM/XXXX)
     */
    public function generateInvoiceNumber(Outlet $outlet, \DateTimeInterface $date): string
    {
        $prefix = 'INV/'.$date->format('Ym').'/';

        $lastInvoice = DB::table('transaction_invoices')
            ->join('transactions', 'transaction_invoices.transaction_id', '=', 'transactions.id')
            ->where('transactions.outlet_id', $outlet->id)
            ->where('transaction_invoices.invoice_number', 'like', $prefix.'%')
            ->orderBy('transaction_invoices.invoice_number', 'desc')
            ->lockForUpdate()
            ->select('transaction_invoices.invoice_number')
            ->first();

        $sequence = 1;
        if ($lastInvoice) {
            $lastSequence = (int) substr($lastInvoice->invoice_number, -4);
            $sequence = $lastSequence + 1;
        }

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Validasi Ketersediaan Stok Fisik vs Toleransi Stok Negatif.
     */
    public function checkStockAvailability(array $items, Outlet $outlet): void
    {
        $allowNegativeSetting = OutletSetting::where('outlet_id', $outlet->id)
            ->where(function ($q) {
                $q->where(function ($sq) {
                    $sq->where('category', 'pos')->where('key', 'allow_negative_stock');
                })->orWhere(function ($sq) {
                    $sq->where('category', 'sales')->whereIn('key', ['allow_negative_stock', 'allow_negative_stock_b2b']);
                });
            })
            ->pluck('value', 'key');

        $isNegativeAllowed = false;
        foreach (['allow_negative_stock_b2b', 'allow_negative_stock'] as $k) {
            if (isset($allowNegativeSetting[$k])) {
                $val = $allowNegativeSetting[$k];
                $isNegativeAllowed = is_array($val) ? ($val[0] ?? false) : (bool) $val;
                break;
            }
        }

        if ($isNegativeAllowed) {
            return; // Bebas terbitkan walau stok kurang
        }

        foreach ($items as $item) {
            // Hanya periksa item fisik (inventory_item_id != null)
            if (empty($item['inventory_item_id'])) {
                continue;
            }

            $balance = DB::table('inventory_balances')
                ->where('outlet_id', $outlet->id)
                ->where('inventory_item_id', $item['inventory_item_id'])
                ->lockForUpdate()
                ->first();

            $currentStock = $balance ? (float) $balance->current_stock : 0.0;

            if ($currentStock < (float) $item['qty']) {
                $productName = $item['product_name'] ?? 'Item';
                throw new InvalidArgumentException("Stok {$productName} tidak mencukupi. Sisa stok: {$currentStock}");
            }
        }
    }

    /**
     * Potong stok inventori dan alokasikan layer FIFO cost.
     */
    public function deductStockForTransaction(Transaction $transaction, User $user): void
    {
        $outlet = $transaction->outlet;
        $business = $outlet->business;

        foreach ($transaction->items as $item) {
            if (! $item->inventory_item_id) {
                continue; // Skip jasa / non-inventory item
            }

            $inventoryItem = $item->inventoryItem;
            if (! $inventoryItem) {
                continue;
            }

            $qty = (float) $item->qty;

            // Panggil recordOutgoingStock
            $costResult = $this->costingService->recordOutgoingStock(
                $business,
                $outlet,
                $inventoryItem,
                $qty,
                InventoryMovementType::Sale,
                $transaction,
                "Penjualan {$transaction->transaction_number}",
                $user
            );

            // Simpan snapshot HPP ke baris item
            $item->unit_cogs = $costResult['unit_cogs'];
            $item->cogs_amount = $costResult['total_cogs'];
            $item->save();
        }
    }

    /**
     * Reversal / Restorasi stok dan FIFO layer saat transaksi dibatalkan.
     */
    public function reverseStockForTransaction(Transaction $transaction, User $user): void
    {
        $outlet = $transaction->outlet;
        $business = $outlet->business;

        foreach ($transaction->items as $item) {
            if (! $item->inventory_item_id) {
                continue;
            }

            $inventoryItem = $item->inventoryItem;
            if (! $inventoryItem) {
                continue;
            }

            $qty = (float) $item->qty;
            $unitCogs = (float) $item->unit_cogs;

            if ($qty > 0) {
                $this->costingService->restoreFifoCostLayer(
                    $business,
                    $outlet,
                    $inventoryItem,
                    $qty,
                    $unitCogs,
                    $transaction,
                    $user
                );
            }
        }
    }
}
