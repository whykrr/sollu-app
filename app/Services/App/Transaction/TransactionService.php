<?php

declare(strict_types=1);

namespace App\Services\App\Transaction;

use App\Enums\InventoryMovementType;
use App\Enums\SalesChannelEnum;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryItem;
use App\Models\Master\Customer;
use App\Models\Master\PaymentMethod;
use App\Models\Master\Product;
use App\Models\Master\ProductItem;
use App\Models\Outlet;
use App\Models\OutletDevice;
use App\Models\OutletSetting;
use App\Models\Promotion\Promotion;
use App\Models\Sales\Shift;
use App\Models\Sales\Transaction;
use App\Models\User;
use App\Services\App\Inventory\InventoryCostingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class TransactionService
{
    public function __construct(
        private readonly InventoryCostingService $costingService,
    ) {}

    /**
     * Resolve short uppercase outlet code for document prefixes.
     */
    protected function resolveOutletCode(Outlet $outlet): string
    {
        $rawCode = $outlet->slug ?: $outlet->name;
        $code = strtoupper(Str::slug(substr($rawCode ?: 'OUTLET', 0, 20)));

        return ! empty($code) ? $code : 'OUTLET';
    }

    /**
     * Generate Nomor Transaksi (TRX/{OUTLET}/{YYYYMM}/{XXXX})
     */
    public function generateTransactionNumber(Outlet $outlet, \DateTimeInterface $date): string
    {
        $outletCode = $this->resolveOutletCode($outlet);
        $prefix = 'TRX/'.$outletCode.'/'.$date->format('Ym').'/';
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
     * Generate Nomor Faktur B2B (INV/{OUTLET}/{YYYYMM}/{XXXX})
     */
    public function generateInvoiceNumber(Outlet $outlet, \DateTimeInterface $date): string
    {
        $outletCode = $this->resolveOutletCode($outlet);
        $prefix = 'INV/'.$outletCode.'/'.$date->format('Ym').'/';

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
    /**
     * Validasi Ketersediaan Stok Fisik vs Toleransi Stok Negatif.
     *
     * @return Collection<string, InventoryBalance>
     */
    public function checkStockAvailability(array $items, Outlet $outlet): Collection
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

        $invItemIds = array_values(array_unique(array_filter(array_column($items, 'inventory_item_id'))));
        if (empty($invItemIds)) {
            return collect();
        }

        // Bulk lock and fetch balances in a single query
        $balances = InventoryBalance::where('outlet_id', $outlet->id)
            ->whereIn('inventory_item_id', $invItemIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('inventory_item_id');

        if ($isNegativeAllowed) {
            return $balances;
        }

        foreach ($items as $item) {
            $invId = $item['inventory_item_id'] ?? null;
            if (empty($invId)) {
                continue;
            }

            // Jika item dalam payload secara eksplisit menyatakan track_inventory === false, lewati
            if (isset($item['track_inventory']) && $item['track_inventory'] === false) {
                continue;
            }

            $balance = $balances->get($invId);
            $currentStock = $balance ? (float) $balance->current_stock : 0.0;
            $qty = (float) ($item['qty_deducted'] ?? $item['qty'] ?? 1);

            if ($currentStock < $qty) {
                // Pastikan item benar-benar dilacak sebelum melempar exception
                $isTracked = DB::table('inventory_items')
                    ->leftJoin('product_items', 'inventory_items.product_item_id', '=', 'product_items.id')
                    ->leftJoin('products', 'product_items.product_id', '=', 'products.id')
                    ->where('inventory_items.id', $invId)
                    ->where('inventory_items.business_id', $outlet->business_id)
                    ->where(function ($q) {
                        $q->where(function ($sq) {
                            $sq->whereNotNull('inventory_items.product_item_id')
                                ->where('product_items.track_inventory', true)
                                ->where(function ($psq) {
                                    $psq->whereNull('products.product_type')
                                        ->orWhere('products.product_type', '!=', 'service');
                                });
                        })->orWhere(function ($sq) {
                            $sq->whereNull('inventory_items.product_item_id')
                                ->where('inventory_items.is_active', true);
                        });
                    })
                    ->exists();

                if (! $isTracked) {
                    continue;
                }

                $productName = $item['product_name'] ?? 'Item';
                throw new InvalidArgumentException("Stok {$productName} tidak mencukupi. Sisa stok: {$currentStock}");
            }
        }

        return $balances;
    }

    /**
     * Potong stok inventori dan alokasikan layer FIFO cost.
     *
     * @param  Collection<string, InventoryBalance>|null  $preloadedBalances
     */
    public function deductStockForTransaction(Transaction $transaction, User $user, ?Collection $preloadedBalances = null): void
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

            // Skip jika relation sudah di-load dan item terbukti tidak melacak inventori
            if ($inventoryItem->relationLoaded('productItem') && $inventoryItem->productItem && ! $inventoryItem->productItem->track_inventory) {
                continue;
            }

            $qty = (float) $item->qty;
            $preloadedBalance = $preloadedBalances?->get($inventoryItem->id);

            // Panggil recordOutgoingStock dengan balance yang telah di-fetch
            $costResult = $this->costingService->recordOutgoingStock(
                $business,
                $outlet,
                $inventoryItem,
                $qty,
                InventoryMovementType::Sale,
                $transaction,
                "Penjualan {$transaction->transaction_number}",
                $user,
                preloadedBalance: $preloadedBalance
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

    /**
     * Sinkronisasi transaksi POS offline/online dengan idempotensi ketat dan pemotongan stok FIFO.
     *
     * @param  array<string, mixed>  $data
     */
    public function syncOfflineTransaction(array $data, ?OutletDevice $device = null): Transaction
    {
        return DB::transaction(function () use ($data, $device) {
            $outletId = $device?->outlet_id ?? $data['outlet_id'] ?? null;
            if (! $outletId) {
                throw new InvalidArgumentException('Outlet ID tidak ditemukan pada device atau data transaksi.');
            }

            $outlet = Outlet::with('business')->findOrFail($outletId);

            $transactionNumber = $data['transaction_number']
                ?? $data['receipt_number']
                ?? $data['offline_id']
                ?? $this->generateTransactionNumber($outlet, now());

            // 1. Idempotensi ketat: cegah duplikasi transaksi dan double deduction
            $existing = Transaction::with(['items', 'payments', 'promos'])
                ->where('outlet_id', $outletId)
                ->where(function ($q) use ($transactionNumber, $data) {
                    $q->where('transaction_number', $transactionNumber);
                    if (! empty($data['offline_id']) && Str::isUuid((string) $data['offline_id'])) {
                        $q->orWhere('id', $data['offline_id']);
                    }
                })
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $isValidUuid = static fn (?string $id): bool => ! empty($id) && Str::isUuid($id);

            // 2. Resolusi Shift, Customer, dan User Penanggung Jawab
            $shiftId = ($isValidUuid($data['shift_id'] ?? null) && Shift::where('id', $data['shift_id'])->where('outlet_id', $outletId)->exists())
                ? $data['shift_id']
                : null;

            $customerId = ($isValidUuid($data['customer_id'] ?? null) && Customer::where('id', $data['customer_id'])->where('business_id', $outlet->business_id)->exists())
                ? $data['customer_id']
                : null;

            // Resolusi created_by (kasir)
            $cashierCandidateId = $data['cashier_id'] ?? $data['user_id'] ?? null;
            $user = null;
            if ($isValidUuid($cashierCandidateId)) {
                $user = User::where('id', $cashierCandidateId)->first();
            }

            if (! $user) {
                $user = $outlet->business->users()->where('is_root_user', true)->first()
                    ?? $outlet->users()->first()
                    ?? User::first();
            }

            if (! $user) {
                throw new InvalidArgumentException('Tidak dapat mengidentifikasi user penanggung jawab transaksi.');
            }

            // 3. Pre-load dan validasi ketersediaan stok fisik
            $preloadedBalances = $this->checkStockAvailability($data['items'], $outlet);

            // 4. Buat Master Transaksi
            $status = in_array($data['status'] ?? '', ['completed', 'paid', 'hold', 'void', 'draft'], true)
                ? $data['status']
                : 'completed';

            $paymentStatus = in_array($data['payment_status'] ?? '', ['unpaid', 'paid', 'partial'], true)
                ? $data['payment_status']
                : 'paid';

            $total = (float) ($data['total'] ?? 0);
            $totalPaid = $paymentStatus === 'paid' ? $total : (float) ($data['total_paid'] ?? 0);
            $balanceDue = max(0.0, $total - $totalPaid);

            $txAttributes = [
                'outlet_id' => $outlet->id,
                'shift_id' => $shiftId,
                'customer_id' => $customerId,
                'channel' => SalesChannelEnum::Direct,
                'transaction_number' => $transactionNumber,
                'transaction_date' => now(),
                'subtotal' => $data['subtotal'] ?? 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'discount_type' => $data['discount_type'] ?? null,
                'discount_value' => $data['discount_value'] ?? 0,
                'promo_name' => $data['promo_name'] ?? null,
                'tax_amount' => $data['tax_amount'] ?? 0,
                'shipping_fee' => $data['shipping_fee'] ?? 0,
                'service_charge_amount' => $data['service_charge_amount'] ?? 0,
                'total' => $total,
                'total_paid' => $totalPaid,
                'balance_due' => $balanceDue,
                'payment_status' => $paymentStatus,
                'status' => $status,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ];

            $transaction = new Transaction($txAttributes);
            if (! empty($data['offline_id']) && Str::isUuid((string) $data['offline_id'])) {
                $transaction->id = $data['offline_id'];
            }
            $transaction->save();

            // 5. Simpan Item Transaksi
            foreach ($data['items'] as $item) {
                $product = ($isValidUuid($item['product_id'] ?? null))
                    ? Product::where('id', $item['product_id'])->where('business_id', $outlet->business_id)->first()
                    : null;
                $productId = $product?->id;

                $productItemId = ($isValidUuid($item['product_item_id'] ?? null) && ProductItem::where('id', $item['product_item_id'])->exists())
                    ? $item['product_item_id']
                    : null;

                $inventoryItemId = ($isValidUuid($item['inventory_item_id'] ?? null) && InventoryItem::where('id', $item['inventory_item_id'])->where('business_id', $outlet->business_id)->exists())
                    ? $item['inventory_item_id']
                    : null;

                // Jasa / Service items atau produk non-tracked tidak memiliki inventory_item_id
                $isTrackInventory = $product ? (bool) $product->track_inventory : true;
                if ($product && ($product->isService() || ! $isTrackInventory)) {
                    $inventoryItemId = null;
                } elseif (! $inventoryItemId && $product && $product->track_inventory) {
                    // Fallback otomatis inventory_item_id untuk barang fisik jika belum terisi
                    if ($productItemId) {
                        $inventoryItemId = InventoryItem::where('product_item_id', $productItemId)
                            ->where('business_id', $outlet->business_id)
                            ->value('id');
                    }
                    if (! $inventoryItemId) {
                        $inventoryItemId = $product->inventoryItems()
                            ->where('inventory_items.business_id', $outlet->business_id)
                            ->value('inventory_items.id');
                    }
                }

                $itemQty = (float) ($item['qty_deducted'] ?? $item['qty'] ?? 1);

                $transaction->items()->create([
                    'product_id' => $productId,
                    'product_item_id' => $productItemId,
                    'inventory_item_id' => $inventoryItemId,
                    'product_name' => $item['product_name'] ?? 'Item POS',
                    'price' => $item['price'] ?? 0,
                    'qty' => $itemQty,
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'subtotal' => $item['subtotal'] ?? 0,
                    'promo_name' => $item['promo_name'] ?? null,
                    'notes' => $item['notes'] ?? null,
                ]);
            }

            // 6. Simpan Pembayaran
            if (! empty($data['payments'])) {
                foreach ($data['payments'] as $payment) {
                    $paymentMethodId = ($isValidUuid($payment['payment_method_id'] ?? null) && PaymentMethod::where('id', $payment['payment_method_id'])->where('business_id', $outlet->business_id)->exists())
                        ? $payment['payment_method_id']
                        : null;

                    $transaction->payments()->create([
                        'payment_method_id' => $paymentMethodId,
                        'amount' => $payment['amount'] ?? 0,
                        'change_amount' => $payment['change_amount'] ?? 0,
                        'payment_reference' => $payment['payment_reference'] ?? null,
                        'payment_date' => now(),
                        'created_by' => $user->id,
                    ]);
                }
            }

            // 7. Simpan Promosi Terkait
            if (! empty($data['promos'])) {
                foreach ($data['promos'] as $promo) {
                    $promoId = ($isValidUuid($promo['promo_id'] ?? null) && Promotion::where('id', $promo['promo_id'])->where('business_id', $outlet->business_id)->exists())
                        ? $promo['promo_id']
                        : null;

                    $transaction->promos()->create([
                        'promo_id' => $promoId,
                        'promo_name' => $promo['promo_name'] ?? 'Promo POS',
                        'discount_type' => $promo['discount_type'] ?? 'fixed',
                        'discount_value' => $promo['discount_value'] ?? 0,
                        'discount_amount' => $promo['discount_amount'] ?? 0,
                    ]);
                }
            }

            // Reload relasi items untuk keperluan pemotongan stok
            $transaction->load(['items.inventoryItem.productItem.product', 'items.product', 'outlet.business']);

            // 8. Pemotongan Stok Fisik & Alokasi Layer FIFO jika Transaksi Selesai/Lunas
            if (in_array($transaction->status->value, ['completed', 'paid'], true)) {
                $this->deductStockForTransaction($transaction, $user, $preloadedBalances);
            }

            return $transaction;
        });
    }
}
