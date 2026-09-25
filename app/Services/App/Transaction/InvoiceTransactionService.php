<?php

namespace App\Services\App\Transaction;

use App\Contracts\Audit\ActivityLoggerInterface;
use App\Enums\AuditModuleEnum;
use App\Enums\TransactionPaymentStatus;
use App\Enums\TransactionStatus;
use App\Events\Transaction\TransactionCompleted;
use App\Events\Transaction\TransactionReversed;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryItem;
use App\Models\Master\PaymentMethod;
use App\Models\Master\Product;
use App\Models\Outlet;
use App\Models\OutletSetting;
use App\Models\Promo;
use App\Models\Sales\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class InvoiceTransactionService
{
    protected ActivityLoggerInterface $auditLogger;

    public function __construct(
        protected PriceCalculationService $priceCalculationService,
        ?ActivityLoggerInterface $auditLogger = null
    ) {
        $this->auditLogger = $auditLogger ?? app(ActivityLoggerInterface::class);
    }

    /**
     * Membuat transaksi faktur / B2B baru (Status awal: Draft).
     */
    public function createTransaction(array $data, User $user): Transaction
    {
        return DB::transaction(function () use ($data, $user) {
            $promoName = null;
            if (! empty($data['promo_id'])) {
                $promo = Promo::find($data['promo_id']);
                $promoName = $promo?->name;
            }

            // 1. Kalkulasi Subtotal & Item Discount
            $calculatedSubtotal = 0;
            if (! empty($data['items'])) {
                foreach ($data['items'] as &$item) {
                    $itemQty = floatval($item['qty'] ?? 0);
                    $itemPrice = floatval($item['price'] ?? 0);
                    $itemDisc = floatval($item['discount_amount'] ?? 0);

                    // Clamp promo discount jika ada promo terikat pada item
                    $inventoryItemId = $item['inventory_item_id'] ?? null;
                    if ($inventoryItemId && ! empty($data['outlet_id'])) {
                        $activePromo = Promo::active()
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

            $transactionNumber = $this->generateTransactionNumber($data['outlet_id'] ?? null);
            $invoiceNumber = $this->generateInvoiceNumber($data['outlet_id'] ?? null);
            $transactionDate = ! empty($data['transaction_date']) ? Carbon::parse($data['transaction_date']) : now();

            $transaction = Transaction::create([
                'outlet_id' => $data['outlet_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'channel' => $data['channel'] ?? 'wholesale',
                'transaction_number' => $transactionNumber,
                'transaction_date' => $transactionDate,
                'subtotal' => $calculatedSubtotal,
                'discount_amount' => $totalDiscount,
                'discount_type' => ! empty($data['promo_id']) ? 'promo' : null,
                'discount_value' => $promoDiscount,
                'promo_name' => $promoName,
                'tax_amount' => $taxAmount,
                'shipping_fee' => $shippingFee,
                'service_charge_amount' => $serviceChargeAmount,
                'total' => $grandTotal,
                'total_paid' => 0,
                'balance_due' => $grandTotal,
                'payment_status' => TransactionPaymentStatus::Draft,
                'status' => TransactionStatus::Draft,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ]);

            // Default Due Date dari Outlet Sales Settings
            $defaultDueDays = 14;
            if (! empty($data['outlet_id'])) {
                $outlet = Outlet::find($data['outlet_id']);
                if ($outlet) {
                    $setting = $outlet->settings()
                        ->where('category', 'sales')
                        ->whereIn('key', ['default_due_days_invoice', 'default_due_days_b2b'])
                        ->first();
                    if ($setting) {
                        $defaultDueDays = (int) $setting->value;
                    }
                }
            }

            $paymentTerm = in_array($data['payment_term'] ?? '', ['cash', 'credit'])
                ? $data['payment_term']
                : (($data['payment_term'] ?? '') === 'termin' ? 'credit' : 'cash');

            $dueDate = $paymentTerm === 'credit'
                ? (! empty($data['due_date']) ? $data['due_date'] : $transactionDate->copy()->addDays($defaultDueDays)->toDateString())
                : null;

            $transaction->invoice()->create([
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $transactionDate->toDateString(),
                'payment_term' => $paymentTerm,
                'due_date' => $dueDate,
                'status' => TransactionStatus::Draft->value,
                'terms_and_conditions' => $data['terms_and_conditions'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            if (! empty($data['promo_id'])) {
                $promo = Promo::find($data['promo_id']);
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

                    $inventoryItem = $inventoryItemId ? InventoryItem::find($inventoryItemId) : null;
                    $product = $productId ? Product::find($productId) : null;

                    if (! $product && $inventoryItem) {
                        $product = $inventoryItem->product;
                        $productId = $product?->id;
                    }

                    $productName = $inventoryItem?->name ?? $product?->name ?? $item['product_name'] ?? 'Produk';
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

            return $transaction;
        });
    }

    /**
     * Memvalidasi ketersediaan stok fisik di outlet berdasarkan setting allow_negative_stock.
     */
    public function checkStockAvailability(array $items, ?string $outletId): void
    {
        if (! $outletId || empty($items)) {
            return;
        }

        $allowNegativeSetting = OutletSetting::where('outlet_id', $outletId)
            ->where(function ($q) {
                $q->where(function ($sq) {
                    $sq->where('category', 'pos')->where('key', 'allow_negative_stock');
                })->orWhere(function ($sq) {
                    $sq->where('category', 'sales')->whereIn('key', ['allow_negative_stock', 'allow_negative_stock_b2b']);
                });
            })
            ->pluck('value', 'key');

        $isNegativeAllowed = false;
        foreach (['allow_negative_stock', 'allow_negative_stock_b2b'] as $k) {
            if (isset($allowNegativeSetting[$k])) {
                $val = $allowNegativeSetting[$k];
                $isNegativeAllowed = is_array($val) ? ($val[0] ?? false) : (bool) $val;
                break;
            }
        }

        if ($isNegativeAllowed) {
            return;
        }

        foreach ($items as $item) {
            $inventoryItemId = $item['inventory_item_id'] ?? null;
            $productId = $item['product_id'] ?? null;

            if (! $inventoryItemId && $productId) {
                $product = Product::with('inventoryItems')->find($productId);
                $inventoryItemId = $product?->inventoryItems?->first()?->id;
            }

            if (! $inventoryItemId) {
                continue;
            }

            $inventoryItem = InventoryItem::find($inventoryItemId);
            if (! $inventoryItem || ! $inventoryItem->track_inventory) {
                continue;
            }

            $balance = InventoryBalance::where('outlet_id', $outletId)
                ->where('inventory_item_id', $inventoryItemId)
                ->first();

            $currentStock = floatval($balance?->current_stock ?? 0);
            $requestedQty = floatval($item['qty'] ?? 0);

            if ($requestedQty > $currentStock) {
                $itemName = $inventoryItem->name ?: ($item['product_name'] ?? 'Item');

                throw ValidationException::withMessages([
                    'items' => "Stok produk '{$itemName}' tidak mencukupi di outlet ini. Stok tersedia: {$currentStock}, dibutuhkan: {$requestedQty}.",
                ]);
            }
        }
    }

    /**
     * Menerbitkan faktur resmi dari status Draft dan memicu pengurangan stok.
     */
    public function issueInvoice(Transaction $transaction, User $user, array $paymentData = []): Transaction
    {
        if ($transaction->status !== TransactionStatus::Draft) {
            throw new \Exception('Hanya transaksi faktur berstatus draf yang dapat diterbitkan.');
        }

        $transaction->load(['items', 'outlet', 'invoice']);
        $this->checkStockAvailability($transaction->items->toArray(), $transaction->outlet_id);

        return DB::transaction(function () use ($transaction, $user, $paymentData) {
            $paymentTerm = $transaction->invoice?->payment_term ?? 'cash';
            $paymentMethodId = $paymentData['payment_method_id'] ?? null;
            $grandTotal = floatval($transaction->total);

            if ($paymentTerm === 'cash') {
                if ($paymentMethodId) {
                    $paidAmount = isset($paymentData['paid_amount']) && floatval($paymentData['paid_amount']) > 0
                        ? floatval($paymentData['paid_amount'])
                        : $grandTotal;
                    $changeAmount = max(0, $paidAmount - $grandTotal);
                    $actualPaid = min($paidAmount, $grandTotal);

                    $transaction->payments()->create([
                        'payment_method_id' => $paymentMethodId,
                        'amount' => $paidAmount,
                        'change_amount' => $changeAmount,
                        'payment_reference' => $paymentData['payment_reference'] ?? null,
                        'notes' => $paymentData['payment_notes'] ?? 'Pembayaran Tunai',
                        'created_by' => $user->id,
                        'payment_date' => ! empty($paymentData['payment_date']) ? Carbon::parse($paymentData['payment_date']) : now(),
                        'created_at' => ! empty($paymentData['payment_date']) ? Carbon::parse($paymentData['payment_date']) : now(),
                    ]);

                    $transaction->update([
                        'total_paid' => $actualPaid,
                        'balance_due' => 0,
                        'status' => TransactionStatus::Paid,
                        'payment_status' => TransactionPaymentStatus::Paid,
                        'updated_by' => $user->id,
                    ]);
                } else {
                    $transaction->update([
                        'total_paid' => 0,
                        'balance_due' => $grandTotal,
                        'status' => TransactionStatus::Unpaid,
                        'payment_status' => TransactionPaymentStatus::Unpaid,
                        'updated_by' => $user->id,
                    ]);
                }
            } else {
                // Skema Kredit / Termin
                $dpAmount = floatval($paymentData['paid_amount'] ?? 0);
                $actualPaid = 0;

                if ($dpAmount > 0 && $paymentMethodId) {
                    $changeAmount = max(0, $dpAmount - $grandTotal);
                    $actualPaid = min($dpAmount, $grandTotal);

                    $transaction->payments()->create([
                        'payment_method_id' => $paymentMethodId,
                        'amount' => $dpAmount,
                        'change_amount' => $changeAmount,
                        'payment_reference' => $paymentData['payment_reference'] ?? null,
                        'notes' => $paymentData['payment_notes'] ?? 'Uang Muka (DP)',
                        'created_by' => $user->id,
                        'payment_date' => ! empty($paymentData['payment_date']) ? Carbon::parse($paymentData['payment_date']) : now(),
                        'created_at' => ! empty($paymentData['payment_date']) ? Carbon::parse($paymentData['payment_date']) : now(),
                    ]);
                }

                $balanceDue = max(0, $grandTotal - $actualPaid);
                $targetStatus = $balanceDue <= 0 ? TransactionStatus::Paid : TransactionStatus::Unpaid;
                $targetPaymentStatus = $balanceDue <= 0
                    ? TransactionPaymentStatus::Paid
                    : ($actualPaid > 0 ? TransactionPaymentStatus::Partial : TransactionPaymentStatus::Unpaid);

                $transaction->update([
                    'total_paid' => $actualPaid,
                    'balance_due' => $balanceDue,
                    'status' => $targetStatus,
                    'payment_status' => $targetPaymentStatus,
                    'updated_by' => $user->id,
                ]);
            }

            if ($transaction->invoice) {
                $transaction->invoice->update([
                    'status' => $transaction->status->value,
                    'invoice_date' => $transaction->invoice->invoice_date ?? now()->toDateString(),
                    'sent_at' => now(),
                ]);
            }

            // Pengurangan stok modular via Event
            TransactionCompleted::dispatch($transaction);

            $this->auditLogger->log(
                module: AuditModuleEnum::POS->value,
                action: 'transaction.invoice_issued',
                description: "Menerbitkan faktur tagihan #{$transaction->invoice?->invoice_number} (Ref #{$transaction->transaction_number})",
                subject: $transaction,
                causer: $user,
                businessId: $user->business_id,
                outletId: $transaction->outlet_id,
                properties: [
                    'transaction_number' => $transaction->transaction_number,
                    'invoice_number' => $transaction->invoice?->invoice_number,
                    'total' => (float) $transaction->total,
                    'total_paid' => (float) $transaction->total_paid,
                    'balance_due' => (float) $transaction->balance_due,
                    'payment_term' => $paymentTerm,
                ]
            );

            return $transaction;
        });
    }

    /**
     * Mencatat pembayaran pelunasan bertahap pada faktur yang belum lunas.
     */
    public function recordPayment(Transaction $transaction, array $data, User $user): Transaction
    {
        if (in_array($transaction->status, [TransactionStatus::Draft, TransactionStatus::Cancel, TransactionStatus::Void, TransactionStatus::Paid])) {
            throw new \Exception('Transaksi tidak valid untuk pelunasan.');
        }

        return DB::transaction(function () use ($transaction, $data, $user) {
            $amount = floatval($data['amount'] ?? 0);
            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Nominal pembayaran harus lebih dari 0.']);
            }

            $paymentMethod = PaymentMethod::find($data['payment_method_id'] ?? null);
            if (! $paymentMethod) {
                throw ValidationException::withMessages(['payment_method_id' => 'Metode pembayaran tidak valid.']);
            }

            $currentBalance = floatval($transaction->balance_due);
            if ($amount > $currentBalance) {
                $changeAmount = $amount - $currentBalance;
                $paymentAmount = $currentBalance;
            } else {
                $changeAmount = 0;
                $paymentAmount = $amount;
            }

            $payment = $transaction->payments()->create([
                'payment_method_id' => $paymentMethod->id,
                'amount' => $amount,
                'change_amount' => $changeAmount,
                'payment_reference' => $data['payment_reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
                'payment_date' => ! empty($data['payment_date']) ? Carbon::parse($data['payment_date']) : now(),
                'created_at' => ! empty($data['payment_date']) ? Carbon::parse($data['payment_date']) : now(),
            ]);

            $newTotalPaid = floatval($transaction->total_paid ?? 0) + $paymentAmount;
            $newBalanceDue = max(0, $currentBalance - $paymentAmount);

            $targetStatus = $newBalanceDue <= 0 ? TransactionStatus::Paid : $transaction->status;
            $targetPaymentStatus = $newBalanceDue <= 0 ? TransactionPaymentStatus::Paid : TransactionPaymentStatus::Partial;

            $transaction->update([
                'total_paid' => $newTotalPaid,
                'balance_due' => $newBalanceDue,
                'status' => $targetStatus,
                'payment_status' => $targetPaymentStatus,
                'updated_by' => $user->id,
            ]);

            if ($transaction->invoice) {
                $transaction->invoice->update([
                    'status' => $targetStatus->value,
                ]);
            }

            $this->auditLogger->log(
                module: AuditModuleEnum::POS->value,
                action: 'transaction.payment_recorded',
                description: "Mencatat pembayaran faktur tagihan #{$transaction->invoice?->invoice_number} sebesar Rp ".number_format($amount, 0, ',', '.'),
                subject: $payment,
                causer: $user,
                businessId: $user->business_id,
                outletId: $transaction->outlet_id,
                properties: [
                    'transaction_number' => $transaction->transaction_number,
                    'invoice_number' => $transaction->invoice?->invoice_number,
                    'amount' => $amount,
                    'total_paid' => $newTotalPaid,
                    'balance_due' => $newBalanceDue,
                ]
            );

            return $transaction;
        });
    }

    /**
     * Membatalkan transaksi faktur (hanya draft atau unpaid tanpa pelunasan penuh).
     */
    public function cancelInvoice(Transaction $transaction, User $user): Transaction
    {
        if (in_array($transaction->status, [TransactionStatus::Paid, TransactionStatus::Cancel, TransactionStatus::Void])) {
            throw new \Exception('Transaksi dengan status ini tidak dapat dibatalkan.');
        }

        return DB::transaction(function () use ($transaction, $user) {
            $previousStatus = $transaction->status;

            $transaction->update([
                'status' => TransactionStatus::Cancel,
                'payment_status' => TransactionPaymentStatus::Unpaid,
                'updated_by' => $user->id,
            ]);

            if ($transaction->invoice) {
                $transaction->invoice->update([
                    'status' => TransactionStatus::Cancel->value,
                ]);
            }

            // Jika faktur sebelumnya sudah berstatus Unpaid / Partial (artinya stok sudah sempat dipotong), pulihkan stok
            if ($previousStatus !== TransactionStatus::Draft) {
                TransactionReversed::dispatch($transaction);
            }

            $this->auditLogger->log(
                module: AuditModuleEnum::POS->value,
                action: 'transaction.cancelled',
                description: "Membatalkan faktur transaksi #{$transaction->transaction_number}",
                subject: $transaction,
                causer: $user,
                businessId: $user->business_id,
                outletId: $transaction->outlet_id,
            );

            return $transaction;
        });
    }

    /**
     * Melakukan Void pada faktur yang telah lunas / terbit.
     */
    public function voidInvoice(Transaction $transaction, User $user): Transaction
    {
        if ($transaction->status !== TransactionStatus::Paid) {
            throw new \Exception('Hanya transaksi yang sudah lunas yang dapat di-void.');
        }

        return DB::transaction(function () use ($transaction, $user) {
            $transaction->update([
                'status' => TransactionStatus::Void,
                'payment_status' => TransactionPaymentStatus::Unpaid,
                'updated_by' => $user->id,
            ]);

            if ($transaction->invoice) {
                $transaction->invoice->update([
                    'status' => TransactionStatus::Void->value,
                ]);
            }

            // Pulihkan stok via Event
            TransactionReversed::dispatch($transaction);

            $this->auditLogger->log(
                module: AuditModuleEnum::POS->value,
                action: 'transaction.voided',
                description: "Melakukan void pada transaksi faktur #{$transaction->transaction_number}",
                subject: $transaction,
                causer: $user,
                businessId: $user->business_id,
                outletId: $transaction->outlet_id,
            );

            return $transaction;
        });
    }

    /**
     * Generate format nomor transaksi faktur.
     */
    public function generateTransactionNumber(?string $outletId = null): string
    {
        $dateStr = now()->format('Ymd');
        $random = strtoupper(Str::random(4));

        return "TRX-{$dateStr}-{$random}";
    }

    /**
     * Generate format nomor faktur resmi (menggunakan prefix outlet jika ada).
     */
    public function generateInvoiceNumber(?string $outletId = null): string
    {
        $prefix = 'INV';

        if ($outletId) {
            $outlet = Outlet::find($outletId);
            if ($outlet) {
                $setting = $outlet->settings()
                    ->where('category', 'sales')
                    ->whereIn('key', ['transaction_invoice_prefix', 'b2b_invoice_prefix'])
                    ->first();
                if ($setting && ! empty($setting->value)) {
                    $prefix = strtoupper((string) $setting->value);
                }
            }
        }

        $dateStr = now()->format('Ym');
        $random = strtoupper(Str::random(5));

        return "{$prefix}/{$dateStr}/{$random}";
    }
}
