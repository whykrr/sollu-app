<?php

declare(strict_types=1);

namespace App\Services\App\Transaction\Contracts;

use App\DTOs\Transaction\CreateB2bTransactionDTO;
use App\DTOs\Transaction\RecordPaymentDTO;
use App\Models\Sales\Transaction;
use App\Models\User;

interface B2bTransactionServiceInterface
{
    /**
     * Membuat atau menerbitkan transaksi penjualan B2B secara atomik.
     */
    public function createTransaction(CreateB2bTransactionDTO $dto, User $user): Transaction;

    /**
     * Menerbitkan faktur resmi dari draf yang telah tersimpan sebelumnya.
     */
    public function issueInvoice(Transaction $transaction, User $user, array $paymentData = []): Transaction;

    /**
     * Mencatat cicilan pembayaran faktur dan memperbarui sisa tagihan (balance due).
     */
    public function recordPayment(Transaction $transaction, RecordPaymentDTO $dto, User $user): Transaction;

    /**
     * Memperbarui tanggal jatuh tempo faktur dengan pencatatan audit trail.
     */
    public function updateDueDate(Transaction $transaction, string $newDueDate, User $user, ?string $reason = null): Transaction;

    /**
     * Membatalkan transaksi penjualan dan memicu pemulihan saldo serta layer FIFO inventori.
     */
    public function cancelTransaction(Transaction $transaction, User $user, ?string $reason = null): Transaction;
}
