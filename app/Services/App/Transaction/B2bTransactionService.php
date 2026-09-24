<?php

namespace App\Services\App\Transaction;

use App\Contracts\Audit\ActivityLoggerInterface;
use App\Enums\AuditModuleEnum;
use App\Enums\TransactionPaymentStatus;
use App\Enums\TransactionStatus;
use App\Models\Sales\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class B2bTransactionService
{
    protected ActivityLoggerInterface $auditLogger;

    public function __construct(
        protected PriceCalculationService $priceCalculationService,
        protected TransactionService $baseTransactionService,
        ?ActivityLoggerInterface $auditLogger = null
    ) {
        $this->auditLogger = $auditLogger ?? app(ActivityLoggerInterface::class);
    }

    public function createTransaction(array $data, User $user): Transaction
    {
        return DB::transaction(function () use ($data, $user) {
            // Kita bisa menggunakan logic kalkulasi dari PriceCalculationService,
            // namun untuk sekarang kita gunakan simulasi standar B2B.

            $calculatedSubtotal = 0;
            $totalDiscount = floatval($data['manual_discount_amount'] ?? 0) + floatval($data['promo_discount_amount'] ?? 0);

            if (! empty($data['items'])) {
                foreach ($data['items'] as &$item) {
                    $itemQty = floatval($item['qty'] ?? 0);
                    $itemPrice = floatval($item['price'] ?? 0);
                    $itemDisc = floatval($item['discount_amount'] ?? 0);

                    $item['subtotal'] = ($itemQty * $itemPrice) - $itemDisc;
                    $calculatedSubtotal += max(0, $item['subtotal']);
                }
                unset($item);
            }

            $taxAmount = floatval($data['tax_amount'] ?? 0);
            $shippingFee = floatval($data['shipping_fee'] ?? 0);
            $serviceChargeAmount = floatval($data['service_charge_amount'] ?? 0);

            $grandTotal = max(0, $calculatedSubtotal - $totalDiscount + $taxAmount + $shippingFee + $serviceChargeAmount);

            // Generate Numbers (Assuming there's a helper or we reuse base service, but let's just make it simple or use trait if available.
            // Since we need to reuse generateTransactionNumber, we might call it from baseTransactionService if public.
            // However, they are protected in TransactionService. We will just use Str::uuid for now if we can't access it, or we need to make them public).
            $transactionNumber = 'B2B-'.strtoupper(uniqid());
            $invoiceNumber = 'INV-'.strtoupper(uniqid());

            $transactionDate = $data['transaction_date'] ?? now();

            $transaction = Transaction::create([
                'outlet_id' => $data['outlet_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'channel' => $data['channel'] ?? 'wholesale',
                'transaction_number' => $transactionNumber,
                'transaction_date' => $transactionDate,
                'subtotal' => $calculatedSubtotal,
                'discount_amount' => $totalDiscount,
                'discount_type' => ! empty($data['promo_id']) ? 'promo' : null,
                'discount_value' => floatval($data['promo_discount_amount'] ?? 0),
                'promo_name' => null,
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

            $paymentTerm = in_array($data['payment_term'] ?? '', ['cash', 'credit'])
                ? $data['payment_term']
                : (($data['payment_term'] ?? '') === 'termin' ? 'credit' : 'cash');

            $transaction->invoice()->create([
                'invoice_number' => $invoiceNumber,
                'invoice_date' => $transactionDate,
                'payment_term' => $paymentTerm,
                'due_date' => $paymentTerm === 'credit' ? ($data['due_date'] ?? null) : null,
                'status' => TransactionStatus::Draft,
            ]);

            // Save items
            if (! empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    $transaction->items()->create([
                        'product_id' => $item['product_id'] ?? null,
                        'inventory_item_id' => $item['inventory_item_id'] ?? null,
                        'product_name' => $item['product_name'] ?? 'Item',
                        'qty' => floatval($item['qty'] ?? 0),
                        'price' => floatval($item['price'] ?? 0),
                        'discount_amount' => floatval($item['discount_amount'] ?? 0),
                        'subtotal' => floatval($item['subtotal'] ?? 0),
                    ]);
                }
            }

            if (($data['action'] ?? '') === 'issue') {
                $this->baseTransactionService->checkStockAvailability($data['items'] ?? [], $data['outlet_id'] ?? null);
            }

            $this->auditLogger->log(
                module: AuditModuleEnum::POS->value,
                action: 'b2b.created',
                description: "Created B2B Transaction {$transaction->transaction_number}",
                subject: $transaction,
                causer: $user,
                businessId: $user->business_id,
                outletId: $transaction->outlet_id
            );

            return $transaction;
        });
    }

    public function issueInvoice(Transaction $transaction, User $user): Transaction
    {
        if ($transaction->status !== TransactionStatus::Draft) {
            throw new \Exception('Hanya transaksi draf yang dapat diterbitkan.');
        }

        $transaction->load(['items', 'outlet']);
        $this->baseTransactionService->checkStockAvailability($transaction->items->toArray(), $transaction->outlet_id);

        return DB::transaction(function () use ($transaction, $user) {
            $paymentTerm = $transaction->invoice?->payment_term ?? 'cash';
            $targetStatus = $paymentTerm === 'cash' ? 'paid' : 'unpaid';

            $transaction->update([
                'status' => $targetStatus,
                'payment_status' => $targetStatus,
                'updated_by' => $user->id,
            ]);

            if ($transaction->invoice) {
                $transaction->invoice->update([
                    'status' => $targetStatus,
                ]);
            }

            // Deduct stock if unpaid/paid (issued)
            // (Assumes deduction happens on issue, or we could call InventoryDeductionService here)

            $this->auditLogger->log(
                module: AuditModuleEnum::POS->value,
                action: 'b2b.issued',
                description: "Issued B2B Invoice {$transaction->transaction_number}",
                subject: $transaction,
                causer: $user,
                businessId: $user->business_id,
                outletId: $transaction->outlet_id
            );

            return $transaction;
        });
    }
}
