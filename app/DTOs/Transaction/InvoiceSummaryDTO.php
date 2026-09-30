<?php

declare(strict_types=1);

namespace App\DTOs\Transaction;

use App\Models\Sales\Transaction;
use App\Models\Sales\TransactionInvoice;

readonly class InvoiceSummaryDTO
{
    public function __construct(
        public Transaction $transaction,
        public TransactionInvoice $invoice,
        public float $subtotal,
        public float $totalDiscount,
        public float $taxAmount,
        public float $shippingFee,
        public float $grandTotal,
        public float $totalPaid,
        public float $balanceDue,
    ) {}

    public static function fromTransaction(Transaction $transaction): self
    {
        return new self(
            transaction: $transaction,
            invoice: $transaction->invoice,
            subtotal: (float) $transaction->subtotal,
            totalDiscount: (float) $transaction->discount_amount,
            taxAmount: (float) $transaction->tax_amount,
            shippingFee: (float) $transaction->shipping_fee,
            grandTotal: (float) $transaction->total,
            totalPaid: (float) $transaction->total_paid,
            balanceDue: (float) $transaction->balance_due,
        );
    }
}
