<?php

namespace App\Services\App\Transaction;

use App\Contracts\Audit\ActivityLoggerInterface;
use App\Enums\AuditModuleEnum;
use App\Enums\InvoiceStatus;
use App\Enums\TransactionPaymentStatus;
use App\Enums\TransactionStatus;
use App\Events\Transaction\TransactionCompleted;
use App\Events\Transaction\TransactionReversed;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryItem;
use App\Models\Master\Customer;
use App\Models\Master\ModifierOption;
use App\Models\Master\PaymentMethod;
use App\Models\Master\Product;
use App\Models\Master\ProductItem;
use App\Models\Master\VariantGroupOption;
use App\Models\OutletDevice;
use App\Models\OutletSetting;
use App\Models\Promo;
use App\Models\Sales\Shift;
use App\Models\Sales\Transaction;
use App\Models\Sales\TransactionInvoice;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    protected ActivityLoggerInterface $auditLogger;

    public function __construct(
        protected PriceCalculationService $priceCalculationService,
        ?ActivityLoggerInterface $auditLogger = null
    ) {
        $this->auditLogger = $auditLogger ?? app(ActivityLoggerInterface::class);
    }

    public function createTransaction(array $data, User $user): Transaction
    {
        return DB::transaction(function () use ($data, $user) {
            $promoName = null;
            if (! empty($data['promo_id'])) {
                $promo = Promo::find($data['promo_id']);
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
                    $productItemId = $item['product_item_id'] ?? null;
                    if ($productItemId && ! empty($data['outlet_id'])) {
                        $activePromo = Promo::active()
                            ->whereHas('inventoryItems', fn ($q) => $q->where('product_items.id', $productItemId))
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
                'payment_status' => TransactionPaymentStatus::Draft,
                'status' => TransactionStatus::Draft,
                'notes' => $data['notes'] ?? null,
            ]);

            // Create Extension Invoice Record
            $paymentTerm = in_array($data['payment_term'] ?? '', ['cash', 'credit'])
                ? $data['payment_term']
                : (($data['payment_term'] ?? '') === 'termin' ? 'credit' : 'cash');

            $defaultDueDays = 14;
            if (! empty($data['outlet_id'])) {
                $outlet = Outlet::find($data['outlet_id']);
                if ($outlet) {
                    $setting = $outlet->settings()->where('category', 'sales')->where('key', 'default_due_days_b2b')->first();
                    if ($setting) {
                        $defaultDueDays = (int) $setting->value;
                    }
                }
            }

            $transactionDate = $data['transaction_date'] ?? now()->toDateString();
            $dueDate = $paymentTerm === 'credit'
                ? (! empty($data['due_date']) ? $data['due_date'] : Carbon::parse($transactionDate)->addDays($defaultDueDays)->toDateString())
                : null;

            $transaction->invoice()->create([
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $transactionDate,
                'payment_term' => $paymentTerm,
                'due_date' => $dueDate,
                'status' => TransactionStatus::Draft,
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
                    $productItemId = $item['product_item_id'] ?? null;
                    $productId = $item['product_id'] ?? null;
                    $variantGroupOptionId = $item['variant_group_option_id'] ?? null;

                    $inventoryItem = $productItemId ? InventoryItem::find($productItemId) : null;
                    $product = $productId ? Product::find($productId) : null;

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
                        'product_item_id' => $productItemId,
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

        $allowNegative = OutletSetting::where('outlet_id', $outletId)
            ->whereIn('key', ['allow_negative_stock_b2b', 'allow_negative_stock'])
            ->pluck('value', 'key');

        $isNegativeAllowed = false;
        if (isset($allowNegative['allow_negative_stock_b2b'])) {
            $val = $allowNegative['allow_negative_stock_b2b'];
            $isNegativeAllowed = is_array($val) ? ($val[0] ?? false) : (bool) $val;
        } elseif (isset($allowNegative['allow_negative_stock'])) {
            $val = $allowNegative['allow_negative_stock'];
            $isNegativeAllowed = is_array($val) ? ($val[0] ?? false) : (bool) $val;
        }

        if ($isNegativeAllowed) {
            return;
        }

        foreach ($items as $item) {
            $productItemId = $item['product_item_id'] ?? $item['inventory_item_id'] ?? null;
            $productId = $item['product_id'] ?? null;

            if (! $productItemId && $productId) {
                $product = Product::with('inventoryItems')->find($productId);
                $productItemId = $product?->inventoryItems?->first()?->id;
            }

            if (! $productItemId) {
                continue;
            }

            $inventoryItem = InventoryItem::where('id', $productItemId)
                ->orWhere('product_item_id', $productItemId)
                ->first();

            if (! $inventoryItem || ! $inventoryItem->track_inventory) {
                continue;
            }

            $balance = InventoryBalance::where('outlet_id', $outletId)
                ->where('inventory_item_id', $inventoryItem->id)
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

    public function issueInvoice(Transaction $transaction, User $user, array $paymentData = []): Transaction
    {
        if ($transaction->status !== TransactionStatus::Draft) {
            throw new \Exception('Hanya transaksi draf yang dapat diterbitkan.');
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
                        'created_at' => ! empty($paymentData['payment_date']) ? Carbon::parse($paymentData['payment_date']) : now(),
                    ]);

                    $transaction->update([
                        'total_paid' => $actualPaid,
                        'paid_amount' => $actualPaid,
                        'balance_due' => 0,
                        'status' => TransactionStatus::Paid,
                        'payment_status' => TransactionPaymentStatus::Paid,
                        'updated_by' => $user->id,
                    ]);
                } else {
                    $transaction->update([
                        'total_paid' => 0,
                        'paid_amount' => 0,
                        'balance_due' => $grandTotal,
                        'status' => TransactionStatus::Unpaid,
                        'payment_status' => TransactionPaymentStatus::Unpaid,
                        'updated_by' => $user->id,
                    ]);
                }
            } else {
                // Credit / Termin
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
                    'paid_amount' => $actualPaid,
                    'balance_due' => $balanceDue,
                    'status' => $targetStatus,
                    'payment_status' => $targetPaymentStatus,
                    'updated_by' => $user->id,
                ]);
            }

            if ($transaction->invoice) {
                $transaction->invoice->update([
                    'status' => $transaction->status,
                    'invoice_date' => $transaction->invoice->invoice_date ?? now()->toDateString(),
                    'sent_at' => now(),
                ]);
            }

            // Deduct stock
            TransactionCompleted::dispatch($transaction);

            $this->auditLogger->log(
                module: AuditModuleEnum::POS->value,
                action: 'transaction.invoice_issued',
                description: "Menerbitkan faktur tagihan transaksi #{$transaction->transaction_number}",
                subject: $transaction,
                causer: $user,
                businessId: $user->business_id,
                outletId: $transaction->outlet_id,
                properties: [
                    'transaction_number' => $transaction->transaction_number,
                    'total' => (float) $transaction->total,
                    'total_paid' => (float) $transaction->total_paid,
                    'balance_due' => (float) $transaction->balance_due,
                ]
            );

            return $transaction;
        });
    }

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
                'created_at' => ! empty($data['payment_date']) ? Carbon::parse($data['payment_date']) : now(),
            ]);

            $newTotalPaid = floatval($transaction->total_paid ?? $transaction->paid_amount ?? 0) + $paymentAmount;
            $newBalanceDue = max(0, $currentBalance - $paymentAmount);

            $targetStatus = $newBalanceDue <= 0 ? TransactionStatus::Paid : $transaction->status;
            $targetPaymentStatus = $newBalanceDue <= 0 ? TransactionPaymentStatus::Paid : TransactionPaymentStatus::Unpaid;

            $transaction->update([
                'total_paid' => $newTotalPaid,
                'paid_amount' => $newTotalPaid,
                'balance_due' => $newBalanceDue,
                'status' => $targetStatus,
                'payment_status' => $targetPaymentStatus,
                'updated_by' => $user->id,
            ]);

            if ($transaction->invoice) {
                $transaction->invoice->update([
                    'status' => $targetStatus,
                ]);
            }

            $this->auditLogger->log(
                module: AuditModuleEnum::POS->value,
                action: 'transaction.payment_recorded',
                description: "Mencatat pembayaran transaksi #{$transaction->transaction_number} sebesar Rp ".number_format((float) $data['amount'], 0, ',', '.'),
                subject: $transaction,
                causer: $user,
                businessId: $user->business_id,
                outletId: $transaction->outlet_id,
                properties: [
                    'payment_id' => $payment->id,
                    'amount' => (float) $data['amount'],
                    'paid_total' => (float) $newTotalPaid,
                    'balance_due' => (float) $newBalanceDue,
                ]
            );

            return $transaction;
        });
    }

    public function cancelTransaction(Transaction $transaction, User $user): Transaction
    {
        if (! in_array($transaction->status, [TransactionStatus::Draft, TransactionStatus::Unpaid, TransactionStatus::Partial])) {
            throw new \Exception('Hanya transaksi draf atau belum lunas yang bisa dibatalkan.');
        }

        return DB::transaction(function () use ($transaction, $user) {
            // Jika sebelumnya unpaid/partial, artinya stok sudah terpotong
            if (in_array($transaction->status, [TransactionStatus::Unpaid, TransactionStatus::Partial])) {
                TransactionReversed::dispatch($transaction);
            }

            $transaction->update([
                'status' => TransactionStatus::Cancel,
            ]);

            if ($transaction->invoice) {
                $transaction->invoice->update(['status' => InvoiceStatus::Cancelled]);
            }

            $this->auditLogger->log(
                module: AuditModuleEnum::POS->value,
                action: 'transaction.cancelled',
                description: "Membatalkan transaksi #{$transaction->transaction_number}",
                subject: $transaction,
                causer: $user,
                businessId: $user->business_id,
                outletId: $transaction->outlet_id
            );

            return $transaction;
        });
    }

    public function voidTransaction(Transaction $transaction, User $user): Transaction
    {
        if ($transaction->status !== TransactionStatus::Paid) {
            throw new \Exception('Hanya transaksi lunas yang bisa di-void.');
        }

        return DB::transaction(function () use ($transaction, $user) {
            TransactionReversed::dispatch($transaction);

            $transaction->update([
                'status' => TransactionStatus::Void,
            ]);

            if ($transaction->invoice) {
                $transaction->invoice->update(['status' => InvoiceStatus::Cancelled]);
            }

            $this->auditLogger->log(
                module: AuditModuleEnum::POS->value,
                action: 'transaction.voided',
                description: "Melakukan void pada transaksi #{$transaction->transaction_number}",
                subject: $transaction,
                causer: $user,
                businessId: $user->business_id,
                outletId: $transaction->outlet_id
            );

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
        $last = TransactionInvoice::where('invoice_number', 'like', $prefix.'%')
            ->orderBy('id', 'desc')
            ->first();

        if (! $last) {
            return $prefix.'0001';
        }

        $lastNumber = intval(substr($last->invoice_number, -4));

        return $prefix.str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    public function syncOfflineTransaction(array $data, ?OutletDevice $device = null): Transaction
    {
        return DB::transaction(function () use ($data, $device) {
            $transactionNumber = $data['transaction_number'] ?? $data['receipt_number'] ?? $data['offline_id'] ?? $this->generateTransactionNumber();

            // Check idempotency via transaction_number
            $existing = Transaction::where('transaction_number', $transactionNumber)->first();
            if ($existing) {
                return $existing;
            }

            $isValidUuid = fn ($id) => ! empty($id) && Str::isUuid($id);

            $shiftId = ($isValidUuid($data['shift_id'] ?? null) && Shift::where('id', $data['shift_id'])->exists()) ? $data['shift_id'] : null;
            $customerId = ($isValidUuid($data['customer_id'] ?? null) && Customer::where('id', $data['customer_id'])->exists()) ? $data['customer_id'] : null;

            // Create transaction from offline data
            $transaction = Transaction::create([
                'outlet_id' => $device->outlet_id,
                'shift_id' => $shiftId,
                'customer_id' => $customerId,
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
                'payment_status' => $data['payment_status'] ?? TransactionPaymentStatus::Paid->value,
                'status' => in_array($data['status'], [TransactionStatus::Completed->value, TransactionStatus::Paid->value, TransactionStatus::Hold->value, TransactionStatus::Void->value]) ? $data['status'] : TransactionStatus::Completed->value,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $productId = ($isValidUuid($item['product_id'] ?? null) && Product::where('id', $item['product_id'])->exists()) ? $item['product_id'] : null;
                $productItemId = ($isValidUuid($item['product_item_id'] ?? null) && ProductItem::where('id', $item['product_item_id'])->exists()) ? $item['product_item_id'] : null;
                $variantOptionId = ($isValidUuid($item['variant_group_option_id'] ?? null) && VariantGroupOption::where('id', $item['variant_group_option_id'])->exists()) ? $item['variant_group_option_id'] : null;

                $txItem = $transaction->items()->create([
                    'product_id' => $productId,
                    'product_item_id' => $productItemId,
                    'variant_group_option_id' => $variantOptionId,
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
                        $modOptionId = ($isValidUuid($mod['modifier_option_id'] ?? null) && ModifierOption::where('id', $mod['modifier_option_id'])->exists()) ? $mod['modifier_option_id'] : null;

                        $txItem->modifiers()->create([
                            'modifier_option_id' => $modOptionId,
                            'modifier_name' => $mod['modifier_name'],
                            'price' => $mod['price'],
                            'qty' => $mod['qty'] ?? 1,
                        ]);
                    }
                }
            }

            if (! empty($data['payments'])) {
                foreach ($data['payments'] as $payment) {
                    $paymentMethodId = ($isValidUuid($payment['payment_method_id'] ?? null) && PaymentMethod::where('id', $payment['payment_method_id'])->exists()) ? $payment['payment_method_id'] : null;

                    $transaction->payments()->create([
                        'payment_method_id' => $paymentMethodId,
                        'amount' => $payment['amount'],
                        'change_amount' => $payment['change_amount'] ?? 0,
                        'payment_reference' => $payment['payment_reference'] ?? null,
                    ]);
                }
            }

            if (! empty($data['promos'])) {
                foreach ($data['promos'] as $p) {
                    $promoId = ($isValidUuid($p['promo_id'] ?? null) && Promo::where('id', $p['promo_id'])->exists()) ? $p['promo_id'] : null;

                    $transaction->promos()->create([
                        'promo_id' => $promoId,
                        'promo_name' => $p['promo_name'],
                        'promo_code' => $p['promo_code'] ?? null,
                        'discount_type' => $p['discount_type'] ?? 'fixed',
                        'discount_value' => $p['discount_value'] ?? 0,
                        'discount_amount' => $p['discount_amount'] ?? 0,
                    ]);
                }
            }

            // Deduct stock if completed
            if (in_array($transaction->status, [TransactionStatus::Completed, TransactionStatus::Paid])) {
                TransactionCompleted::dispatch($transaction);
            }

            return $transaction;
        });
    }
}
