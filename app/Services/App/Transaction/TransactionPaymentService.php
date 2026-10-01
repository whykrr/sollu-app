<?php

declare(strict_types=1);

namespace App\Services\App\Transaction;

use App\DTOs\Transaction\RecordPaymentDTO;
use App\Enums\TransactionPaymentStatus;
use App\Enums\TransactionStatus;
use App\Models\Sales\Transaction;
use App\Models\Sales\TransactionPayment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TransactionPaymentService
{
    /**
     * Catat pembayaran, hitung ulang total paid & balance due, lalu sesuaikan status.
     */
    public function recordPayment(Transaction $transaction, RecordPaymentDTO $dto, User $user): Transaction
    {
        if ($transaction->status === TransactionStatus::Draft) {
            throw new InvalidArgumentException('Tidak dapat mencatat pembayaran pada draf.');
        }

        if (in_array($transaction->status, [TransactionStatus::Cancel, TransactionStatus::Void], true)) {
            throw new InvalidArgumentException('Transaksi sudah dibatalkan.');
        }

        if ($transaction->payment_status === TransactionPaymentStatus::Paid) {
            throw new InvalidArgumentException('Transaksi sudah lunas.');
        }

        if ($dto->amount <= 0) {
            throw new InvalidArgumentException('Nominal pembayaran harus lebih dari 0.');
        }

        DB::transaction(function () use ($transaction, $dto, $user) {
            $payment = new TransactionPayment;
            $payment->transaction_id = $transaction->id;
            $payment->payment_method_id = $dto->paymentMethodId;
            $payment->amount = $dto->amount;
            $payment->change_amount = $dto->changeAmount;
            $payment->payment_reference = $dto->paymentReference;
            $payment->payment_date = $dto->paymentDate;
            $payment->notes = $dto->notes;
            $payment->created_by = $user->id;
            $payment->save();

            // Recalculate using a single aggregate query
            $totals = $transaction->payments()
                ->selectRaw('COALESCE(SUM(amount), 0) as total_paid, COALESCE(SUM(change_amount), 0) as total_change')
                ->first();

            $totalPaid = (float) ($totals->total_paid ?? 0.0);
            $changeAmountTotal = (float) ($totals->total_change ?? 0.0);

            $netPaid = $totalPaid - $changeAmountTotal;
            $balanceDue = max(0.0, (float) $transaction->total - $netPaid);

            $paymentStatus = TransactionPaymentStatus::Partial;
            if ($balanceDue <= 0.0) {
                $paymentStatus = TransactionPaymentStatus::Paid;
            } elseif ($netPaid <= 0.0) {
                $paymentStatus = TransactionPaymentStatus::Unpaid;
            }

            $transaction->total_paid = $netPaid;
            $transaction->balance_due = $balanceDue;
            $transaction->payment_status = $paymentStatus;

            // Sync status
            if ($paymentStatus === TransactionPaymentStatus::Paid) {
                $transaction->status = TransactionStatus::Paid;
            } else {
                $transaction->status = TransactionStatus::from($paymentStatus->value);
            }

            $transaction->updated_by = $user->id;
            $transaction->save();

            if ($transaction->invoice) {
                $transaction->invoice->status = $transaction->status;
                $transaction->invoice->save();
            }
        });

        $transaction->syncOriginal();

        return $transaction;
    }
}
