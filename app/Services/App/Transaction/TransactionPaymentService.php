<?php

namespace App\Services\App\Transaction;

use App\Contracts\Audit\ActivityLoggerInterface;
use App\Enums\AuditModuleEnum;
use App\Enums\TransactionPaymentStatus;
use App\Enums\TransactionStatus;
use App\Models\Master\PaymentMethod;
use App\Models\Sales\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransactionPaymentService
{
    protected ActivityLoggerInterface $auditLogger;

    public function __construct(
        ?ActivityLoggerInterface $auditLogger = null
    ) {
        $this->auditLogger = $auditLogger ?? app(ActivityLoggerInterface::class);
    }

    public function recordPayment(Transaction $transaction, array $data, User $user): Transaction
    {
        if ($transaction->status === TransactionStatus::Paid) {
            throw new \Exception('Transaksi sudah lunas.');
        }

        if ($transaction->status === TransactionStatus::Draft || $transaction->status === TransactionStatus::Cancel) {
            throw new \Exception('Pembayaran hanya bisa dilakukan untuk transaksi berstatus Unpaid atau Partial.');
        }

        return DB::transaction(function () use ($transaction, $data, $user) {
            $amount = floatval($data['amount'] ?? 0);

            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Nominal pembayaran harus lebih dari 0.']);
            }

            // Verify if payment method belongs to the business/outlet
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

            $transaction->payments()->create([
                'payment_method_id' => $paymentMethod->id,
                'amount' => $amount,
                'change_amount' => $changeAmount,
                'payment_reference' => $data['payment_reference'] ?? null,
                'payment_date' => $data['payment_date'] ?? now(),
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
            ]);

            $newTotalPaid = floatval($transaction->total_paid) + $paymentAmount;
            $newBalanceDue = max(0, $currentBalance - $paymentAmount);

            $targetStatus = $newBalanceDue <= 0 ? TransactionStatus::Paid : $transaction->status;

            $transaction->update([
                'total_paid' => $newTotalPaid,
                'balance_due' => $newBalanceDue,
                'status' => $targetStatus,
                'payment_status' => $newBalanceDue <= 0 ? TransactionPaymentStatus::Paid : TransactionPaymentStatus::Unpaid,
                'updated_by' => $user->id,
            ]);

            if ($transaction->invoice) {
                $transaction->invoice->update([
                    'status' => $targetStatus,
                ]);
            }

            $this->auditLogger->log(
                module: AuditModuleEnum::POS->value,
                action: 'b2b.payment_recorded',
                description: "Recorded payment of {$amount} for Transaction {$transaction->transaction_number}",
                subject: $transaction,
                causer: $user,
                businessId: $user->business_id,
                outletId: $transaction->outlet_id
            );

            return $transaction;
        });
    }
}
