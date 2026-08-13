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

            $transaction = Transaction::create([
                'outlet_id' => $data['outlet_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'channel' => $data['channel'] ?? 'direct',
                'subtotal' => collect($data['items'])->sum('subtotal'),
                'discount_amount' => floatval($data['manual_discount_amount'] ?? 0) + floatval($data['promo_discount_amount'] ?? 0),
                'discount_type' => ! empty($data['promo_id']) ? 'promo' : null,
                'discount_value' => floatval($data['promo_discount_amount'] ?? 0),
                'promo_name' => $promoName,
                'tax_amount' => floatval($data['tax_amount'] ?? 0),
                'shipping_fee' => floatval($data['shipping_fee'] ?? 0),
                'service_charge_amount' => floatval($data['service_charge_amount'] ?? 0),
                'total' => 0, // calculated below
                'payment_status' => 'draft',
                'status' => 'draft',
                'is_offline' => false,
                'transaction_date' => $data['transaction_date'] ?? now()->toDateString(),
                'notes' => $data['notes'] ?? null,
                'receipt_number' => $this->generateInvoiceNumber(),
                'transaction_number' => $this->generateInvoiceNumber(),
            ]);

            $total = $transaction->subtotal
                - $transaction->discount_amount
                + $transaction->tax_amount
                + $transaction->shipping_fee
                + $transaction->service_charge_amount;

            $transaction->update(['total' => $total > 0 ? $total : 0]);

            // Create Extension Invoice Record
            $transaction->invoice()->create([
                'invoice_number' => $transaction->receipt_number,
                'invoice_date' => $data['transaction_date'] ?? now()->toDateString(),
                'payment_term' => $data['payment_term'] ?? 'tunai',
                'due_date' => ($data['payment_term'] ?? 'tunai') === 'termin' ? ($data['due_date'] ?? null) : null,
                'status' => 'draft',
                'terms_and_conditions' => $data['terms_and_conditions'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            if (! empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    $inventoryItemId = $item['inventory_item_id'] ?? null;
                    $productId = $item['product_id'] ?? null;
                    $variantGroupOptionId = $item['variant_group_option_id'] ?? null;

                    $inventoryItem = $inventoryItemId ? \App\Models\Inventory\InventoryItem::find($inventoryItemId) : null;
                    $product = $productId ? \App\Models\Master\Product::find($productId) : null;

                    $productName = $inventoryItem?->name ?? $product?->name ?? $item['product_name'] ?? '';

                    $transaction->items()->create([
                        'product_id' => $productId,
                        'inventory_item_id' => $inventoryItemId,
                        'variant_group_option_id' => $variantGroupOptionId,
                        'product_name' => $productName,
                        'price' => $item['price'],
                        'qty' => $item['qty'],
                        'discount_amount' => $item['discount_amount'] ?? 0,
                        'promo_name' => $item['promo_name'] ?? null,
                        'subtotal' => ($item['qty'] * $item['price']) - ($item['discount_amount'] ?? 0),
                    ]);
                }
            }

            return $transaction;
        });
    }

    public function issueInvoice(Transaction $transaction, User $user): Transaction
    {
        if ($transaction->status !== 'draft') {
            throw new \Exception('Hanya transaksi draf yang dapat diterbitkan.');
        }

        return DB::transaction(function () use ($transaction) {
            $transaction->update([
                'status' => 'unpaid',
                'payment_status' => 'unpaid',
            ]);

            if ($transaction->invoice) {
                $transaction->invoice->update([
                    'status' => 'unpaid',
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

    // (createB2bInvoice telah digantikan dengan createTransaction untuk V1)
    protected function generateInvoiceNumber(): string
    {
        $prefix = 'INV/'.date('Y/m/');
        $last = Transaction::where('receipt_number', 'like', $prefix.'%')
            ->orderBy('id', 'desc')
            ->first();

        if (! $last) {
            return $prefix.'0001';
        }

        $lastNumber = intval(substr($last->receipt_number, -4));

        return $prefix.str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    public function syncOfflineTransaction(array $data, ?\App\Models\OutletDevice $device = null): Transaction
    {
        return DB::transaction(function () use ($data, $device) {
            // Check idempotency
            $offlineId = $data['offline_id'] ?? null;
            if ($offlineId) {
                $existing = Transaction::where('offline_id', $offlineId)->first();
                if ($existing) {
                    return $existing;
                }
            }

            // Create transaction from offline data
            $transaction = Transaction::create([
                'outlet_id' => $device->outlet_id,
                'customer_id' => $data['customer_id'] ?? null,
                'channel' => 'pos',
                'subtotal' => $data['subtotal'],
                'discount_amount' => $data['discount_amount'],
                'tax_amount' => $data['tax_amount'],
                'service_charge_amount' => $data['service_charge_amount'],
                'total' => $data['total'],
                'payment_status' => $data['payment_status'],
                'status' => $data['status'],
                'is_offline' => true,
                'offline_id' => $offlineId,
                'receipt_number' => $data['receipt_number'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $txItem = $transaction->items()->create([
                    'product_id' => $item['product_id'],
                    'variant_group_option_id' => $item['variant_group_option_id'] ?? null,
                    'product_name' => $item['product_name'],
                    'price' => $item['price'],
                    'qty' => $item['qty'],
                    'discount_amount' => $item['discount_amount'],
                    'subtotal' => $item['subtotal'],
                ]);

                if (! empty($item['modifiers'])) {
                    foreach ($item['modifiers'] as $mod) {
                        $txItem->modifiers()->create([
                            'modifier_option_id' => $mod['modifier_option_id'],
                            'modifier_name' => $mod['modifier_name'],
                            'price' => $mod['price'],
                            'qty' => $mod['qty'],
                        ]);
                    }
                }
            }

            if (! empty($data['payments'])) {
                foreach ($data['payments'] as $payment) {
                    $transaction->payments()->create([
                        'payment_method_id' => $payment['payment_method_id'],
                        'amount' => $payment['amount'],
                        'change_amount' => $payment['change_amount'],
                        'payment_reference' => $payment['payment_reference'] ?? null,
                    ]);
                }
            }

            // Deduct stock if completed
            if ($transaction->status === 'completed') {
                $this->inventoryDeductionService->deductFromTransaction($transaction);
            }

            return $transaction;
        });
    }
}
