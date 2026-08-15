<?php

namespace App\Services\Transaction;

use App\Models\Sales\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    protected PriceCalculationService $priceCalculationService;

    protected InventoryDeductionService $inventoryDeductionService;

    public function __construct(
        PriceCalculationService $priceCalculationService,
        InventoryDeductionService $inventoryDeductionService
    ) {
        $this->priceCalculationService = $priceCalculationService;
        $this->inventoryDeductionService = $inventoryDeductionService;
    }

    public function createTransaction(array $data, User $user): Transaction
    {
        return DB::transaction(function () use ($data, $user) {
            $promoName = null;
            if (! empty($data['promo_id'])) {
                $promo = \App\Models\Promo::find($data['promo_id']);
                $promoName = $promo?->name;
            }

            // 1. Calculate subtotal from items first
            $calculatedSubtotal = 0;
            if (! empty($data['items'])) {
                foreach ($data['items'] as &$item) {
                    $itemQty = floatval($item['qty'] ?? 0);
                    $itemPrice = floatval($item['price'] ?? 0);
                    $itemDisc = floatval($item['discount_amount'] ?? 0);

                    // Clamp discount if promo exists for this inventory item
                    $inventoryItemId = $item['inventory_item_id'] ?? null;
                    if ($inventoryItemId && ! empty($data['outlet_id'])) {
                        $activePromo = \App\Models\Promo::active()
                            ->whereHas('inventoryItems', fn ($q) => $q->where('inventory_items.id', $inventoryItemId))
                            ->where(function ($q) use ($data) {
                                $q->whereHas('outlets', fn ($q) => $q->where('outlets.id', $data['outlet_id']))
                                    ->orWhere('applies_to_all_outlets', true);
                            })
                            ->first();

                        if ($activePromo) {
                            $maxAllowedDiscount = 0;
                            $promoType = is_object($activePromo->promo_type) ? $activePromo->promo_type->value : $activePromo->promo_type;

                            if ($promoType === 'percentage') {
                                $maxAllowedDiscount = ($itemPrice * floatval($activePromo->discount_value)) / 100;
                                if ($activePromo->max_discount && $maxAllowedDiscount > floatval($activePromo->max_discount)) {
                                    $maxAllowedDiscount = floatval($activePromo->max_discount);
                                }
                                $maxAllowedDiscount *= $itemQty;
                            } elseif ($promoType === 'fixed') {
                                $maxAllowedDiscount = min(floatval($activePromo->discount_value), $itemPrice) * $itemQty;
                            }

                            if ($itemDisc > $maxAllowedDiscount) {
                                $itemDisc = $maxAllowedDiscount;
                            }
                        }
                    }

                    $item['discount_amount'] = $itemDisc;
                    $item['subtotal'] = ($itemQty * $itemPrice) - $itemDisc;

                    $calculatedSubtotal += max(0, $item['subtotal']);
                }
                unset($item);
            }

            $manualDiscount = floatval($data['manual_discount_amount'] ?? 0);
            $promoDiscount = floatval($data['promo_discount_amount'] ?? 0);
            $totalDiscount = $manualDiscount + $promoDiscount;

            $taxAmount = floatval($data['tax_amount'] ?? 0);
            $shippingFee = floatval($data['shipping_fee'] ?? 0);
            $serviceChargeAmount = floatval($data['service_charge_amount'] ?? 0);

            $grandTotal = isset($data['total'])
                ? floatval($data['total'])
                : max(0, $calculatedSubtotal - $totalDiscount + $taxAmount + $shippingFee + $serviceChargeAmount);

            $transactionNumber = $this->generateTransactionNumber();
            $invoiceNumber = $this->generateInvoiceNumber();

            $transaction = Transaction::create([
                'outlet_id' => $data['outlet_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'channel' => $data['channel'] ?? 'direct',
                'transaction_number' => $transactionNumber,
                'subtotal' => $calculatedSubtotal,
                'discount_amount' => $totalDiscount,
                'discount_type' => ! empty($data['promo_id']) ? 'promo' : null,
                'discount_value' => $promoDiscount,
                'promo_name' => $promoName,
                'tax_amount' => $taxAmount,
                'shipping_fee' => $shippingFee,
                'service_charge_amount' => $serviceChargeAmount,
                'total' => $grandTotal,
                'payment_status' => 'draft',
                'status' => 'draft',
                'notes' => $data['notes'] ?? null,
            ]);

            // Create Extension Invoice Record
            $paymentTerm = in_array($data['payment_term'] ?? '', ['cash', 'credit'])
                ? $data['payment_term']
                : (($data['payment_term'] ?? '') === 'termin' ? 'credit' : 'cash');

            $transaction->invoice()->create([
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $data['transaction_date'] ?? now()->toDateString(),
                'payment_term' => $paymentTerm,
                'due_date' => $paymentTerm === 'credit' ? ($data['due_date'] ?? null) : null,
                'status' => 'draft',
                'terms_and_conditions' => $data['terms_and_conditions'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            if (! empty($data['promo_id'])) {
                $promo = \App\Models\Promo::find($data['promo_id']);
                if ($promo) {
                    $transaction->promos()->create([
                        'promo_id' => $promo->id,
                        'promo_name' => $promo->name,
                        'promo_code' => $promo->code ?? null,
                        'discount_type' => is_object($promo->promo_type) ? $promo->promo_type->value : $promo->promo_type,
                        'discount_value' => floatval($promo->discount_value),
                        'discount_amount' => $promoDiscount,
                    ]);
                }
            }

            if (! empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    $inventoryItemId = $item['inventory_item_id'] ?? null;
                    $productId = $item['product_id'] ?? null;
                    $variantGroupOptionId = $item['variant_group_option_id'] ?? null;

                    $inventoryItem = $inventoryItemId ? \App\Models\Inventory\InventoryItem::find($inventoryItemId) : null;
                    $product = $productId ? \App\Models\Master\Product::find($productId) : null;

                    if (! $product && $inventoryItem) {
                        $product = $inventoryItem->product;
                        $productId = $product?->id;
                    }

                    $productName = $inventoryItem?->name ?? $product?->name ?? $item['product_name'] ?? '';
                    $itemQty = floatval($item['qty'] ?? 0);
                    $itemPrice = floatval($item['price'] ?? 0);
                    $itemDisc = floatval($item['discount_amount'] ?? 0);
                    $itemSubtotal = isset($item['subtotal']) ? floatval($item['subtotal']) : (($itemQty * $itemPrice) - $itemDisc);

                    $transaction->items()->create([
                        'product_id' => $productId,
                        'inventory_item_id' => $inventoryItemId,
                        'variant_group_option_id' => $variantGroupOptionId,
                        'product_name' => $productName,
                        'price' => $itemPrice,
                        'qty' => $itemQty,
                        'discount_amount' => $itemDisc,
                        'promo_name' => $item['promo_name'] ?? null,
                        'subtotal' => max(0, $itemSubtotal),
                    ]);
                }
            }

            // Check stock availability if action is 'issue'
            if (($data['action'] ?? '') === 'issue') {
                $this->checkStockAvailability($data['items'] ?? [], $data['outlet_id'] ?? null);
            }

            return $transaction;
        });
    }

    public function checkStockAvailability(array $items, ?string $outletId): void
    {
        if (! $outletId || empty($items)) {
            return;
        }

        foreach ($items as $item) {
            $inventoryItemId = $item['inventory_item_id'] ?? null;
            $productId = $item['product_id'] ?? null;

            if (! $inventoryItemId && $productId) {
                $product = \App\Models\Master\Product::with('inventoryItems')->find($productId);
                $inventoryItemId = $product?->inventoryItems?->first()?->id;
            }

            if (! $inventoryItemId) {
                continue;
            }

            $inventoryItem = \App\Models\Inventory\InventoryItem::find($inventoryItemId);
            if (! $inventoryItem || ! $inventoryItem->track_inventory) {
                continue;
            }

            $balance = \App\Models\Inventory\InventoryBalance::where('outlet_id', $outletId)
                ->where('inventory_item_id', $inventoryItemId)
                ->first();

            $currentStock = floatval($balance?->current_stock ?? 0);
            $requestedQty = floatval($item['qty'] ?? 0);

            if ($requestedQty > $currentStock) {
                $itemName = $inventoryItem->name ?: ($item['product_name'] ?? 'Item');

                throw \Illuminate\Validation\ValidationException::withMessages([
                    'items' => "Stok produk '{$itemName}' tidak mencukupi di outlet ini. Stok tersedia: {$currentStock}, dibutuhkan: {$requestedQty}.",
                ]);
            }
        }
    }

    public function issueInvoice(Transaction $transaction, User $user): Transaction
    {
        if ($transaction->status !== 'draft') {
            throw new \Exception('Hanya transaksi draf yang dapat diterbitkan.');
        }

        $transaction->load(['items', 'outlet']);
        $this->checkStockAvailability($transaction->items->toArray(), $transaction->outlet_id);

        return DB::transaction(function () use ($transaction) {
            $paymentTerm = $transaction->invoice?->payment_term ?? 'cash';
            $targetStatus = $paymentTerm === 'cash' ? 'paid' : 'unpaid';

            $transaction->update([
                'status' => $targetStatus,
                'payment_status' => $targetStatus,
            ]);

            if ($transaction->invoice) {
                $transaction->invoice->update([
                    'status' => $targetStatus,
                    'invoice_date' => now()->toDateString(),
                    'sent_at' => now(),
                ]);
            }

            // Integrate with stock deduction service.
            // Requirement: "pengurangan stok berlaku jika tracking inventori di nyalakan pada inventory item"
            // We will let the deduction service or here handle the track_stock check.
            $this->inventoryDeductionService->deductFromTransaction($transaction);

            return $transaction;
        });
    }

    public function recordPayment(Transaction $transaction, array $data, User $user): Transaction
    {
        if (in_array($transaction->status, ['draft', 'cancel', 'void', 'paid'])) {
            throw new \Exception('Transaksi tidak valid untuk pelunasan.');
        }

        return DB::transaction(function () use ($transaction, $data) {
            $transaction->payments()->create([
                'payment_method_id' => $data['payment_method_id'],
                'amount' => $data['amount'],
                'notes' => $data['notes'] ?? null,
                'created_at' => $data['payment_date'] ? \Carbon\Carbon::parse($data['payment_date']) : now(),
            ]);

            $paidAmount = $transaction->payments()->sum('amount');
            $transaction->paid_amount = $paidAmount;

            if ($paidAmount >= $transaction->total) {
                $transaction->status = 'paid';
                $transaction->payment_status = 'paid';
            } else {
                $transaction->status = 'partial';
                $transaction->payment_status = 'partial';
            }

            $transaction->save();

            return $transaction;
        });
    }

    public function cancelTransaction(Transaction $transaction, User $user): Transaction
    {
        if (! in_array($transaction->status, ['draft', 'unpaid', 'partial'])) {
            throw new \Exception('Hanya transaksi draf atau belum lunas yang bisa dibatalkan.');
        }

        return DB::transaction(function () use ($transaction) {
            // Jika sebelumnya unpaid/partial, artinya stok sudah terpotong
            if (in_array($transaction->status, ['unpaid', 'partial'])) {
                $this->inventoryDeductionService->restoreFromTransaction($transaction);
            }

            $transaction->update([
                'status' => 'cancel',
            ]);

            if ($transaction->invoice) {
                $transaction->invoice->update(['status' => 'cancel']);
            }

            return $transaction;
        });
    }

    public function voidTransaction(Transaction $transaction, User $user): Transaction
    {
        if ($transaction->status !== 'paid') {
            throw new \Exception('Hanya transaksi lunas yang bisa di-void.');
        }

        return DB::transaction(function () use ($transaction) {
            $this->inventoryDeductionService->restoreFromTransaction($transaction);

            $transaction->update([
                'status' => 'void',
            ]);

            if ($transaction->invoice) {
                $transaction->invoice->update(['status' => 'cancel']);
            }

            return $transaction;
        });
    }

    protected function generateTransactionNumber(): string
    {
        $prefix = 'TRX/'.date('Y/m/');
        $last = Transaction::where('transaction_number', 'like', $prefix.'%')
            ->orderBy('id', 'desc')
            ->first();

        if (! $last) {
            return $prefix.'0001';
        }

        $lastNumber = intval(substr($last->transaction_number, -4));

        return $prefix.str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    protected function generateInvoiceNumber(): string
    {
        $prefix = 'INV/'.date('Y/m/');
        $last = \App\Models\Sales\TransactionInvoice::where('invoice_number', 'like', $prefix.'%')
            ->orderBy('id', 'desc')
            ->first();

        if (! $last) {
            return $prefix.'0001';
        }

        $lastNumber = intval(substr($last->invoice_number, -4));

        return $prefix.str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    public function syncOfflineTransaction(array $data, ?\App\Models\OutletDevice $device = null): Transaction
    {
        return DB::transaction(function () use ($data, $device) {
            $transactionNumber = $data['transaction_number'] ?? $data['receipt_number'] ?? $data['offline_id'] ?? $this->generateTransactionNumber();

            // Check idempotency via transaction_number
            $existing = Transaction::where('transaction_number', $transactionNumber)->first();
            if ($existing) {
                return $existing;
            }

            // Create transaction from offline data
            $transaction = Transaction::create([
                'outlet_id' => $device->outlet_id,
                'shift_id' => $data['shift_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'channel' => 'pos',
                'transaction_number' => $transactionNumber,
                'subtotal' => $data['subtotal'],
                'discount_amount' => $data['discount_amount'],
                'discount_type' => $data['discount_type'] ?? null,
                'discount_value' => $data['discount_value'] ?? null,
                'promo_name' => $data['promo_name'] ?? null,
                'tax_amount' => $data['tax_amount'],
                'service_charge_amount' => $data['service_charge_amount'],
                'total' => $data['total'],
                'payment_status' => $data['payment_status'] ?? 'paid',
                'status' => in_array($data['status'], ['completed', 'paid', 'hold', 'void']) ? $data['status'] : 'completed',
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $txItem = $transaction->items()->create([
                    'product_id' => $item['product_id'] ?? null,
                    'inventory_item_id' => $item['inventory_item_id'] ?? null,
                    'variant_group_option_id' => $item['variant_group_option_id'] ?? null,
                    'product_name' => $item['product_name'],
                    'price' => $item['price'],
                    'qty' => $item['qty'],
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'discount_type' => $item['discount_type'] ?? null,
                    'discount_value' => $item['discount_value'] ?? null,
                    'promo_name' => $item['promo_name'] ?? null,
                    'subtotal' => $item['subtotal'],
                    'notes' => $item['notes'] ?? null,
                ]);

                if (! empty($item['modifiers'])) {
                    foreach ($item['modifiers'] as $mod) {
                        $txItem->modifiers()->create([
                            'modifier_option_id' => $mod['modifier_option_id'] ?? null,
                            'modifier_name' => $mod['modifier_name'],
                            'price' => $mod['price'],
                            'qty' => $mod['qty'] ?? 1,
                        ]);
                    }
                }
            }

            if (! empty($data['payments'])) {
                foreach ($data['payments'] as $payment) {
                    $transaction->payments()->create([
                        'payment_method_id' => $payment['payment_method_id'] ?? null,
                        'amount' => $payment['amount'],
                        'change_amount' => $payment['change_amount'] ?? 0,
                        'payment_reference' => $payment['payment_reference'] ?? null,
                    ]);
                }
            }

            if (! empty($data['promos'])) {
                foreach ($data['promos'] as $p) {
                    $transaction->promos()->create([
                        'promo_id' => $p['promo_id'] ?? null,
                        'promo_name' => $p['promo_name'],
                        'promo_code' => $p['promo_code'] ?? null,
                        'discount_type' => $p['discount_type'] ?? 'fixed',
                        'discount_value' => $p['discount_value'] ?? 0,
                        'discount_amount' => $p['discount_amount'] ?? 0,
                    ]);
                }
            }

            // Deduct stock if completed
            if (in_array($transaction->status, ['completed', 'paid'])) {
                $this->inventoryDeductionService->deductFromTransaction($transaction);
            }

            return $transaction;
        });
    }
}
