<?php

declare(strict_types=1);

namespace App\Services\App\Transaction;

use App\Contracts\Audit\ActivityLoggerInterface;
use App\DTOs\Promotion\CartEvaluationDTO;
use App\DTOs\Promotion\CartItemDTO;
use App\DTOs\Transaction\CreateB2bTransactionDTO;
use App\DTOs\Transaction\RecordPaymentDTO;
use App\Enums\AuditModuleEnum;
use App\Enums\TransactionPaymentStatus;
use App\Enums\TransactionStatus;
use App\Models\Inventory\InventoryItem;
use App\Models\Master\Product;
use App\Models\Master\ProductItem;
use App\Models\Outlet;
use App\Models\Sales\Transaction;
use App\Models\Sales\TransactionInvoice;
use App\Models\Sales\TransactionItem;
use App\Models\Sales\TransactionPromo;
use App\Models\User;
use App\Services\App\Promotion\Contracts\PromotionEvaluatorInterface;
use App\Services\App\Transaction\Contracts\B2bTransactionServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class B2bTransactionService implements B2bTransactionServiceInterface
{
    protected ActivityLoggerInterface $auditLogger;

    public function __construct(
        private readonly TransactionService $transactionService,
        private readonly TransactionPaymentService $paymentService,
        private readonly PromotionEvaluatorInterface $promotionEvaluator,
        ?ActivityLoggerInterface $auditLogger = null,
    ) {
        $this->auditLogger = $auditLogger ?? app(ActivityLoggerInterface::class);
    }

    public function createTransaction(CreateB2bTransactionDTO $dto, User $user): Transaction
    {
        return DB::transaction(function () use ($dto, $user) {
            $outlet = Outlet::findOrFail($dto->outletId);

            $transactionNumber = $this->transactionService->generateTransactionNumber($outlet, $dto->transactionDate);

            // Hitung subtotal kotor dari seluruh baris item
            $subtotal = 0.0;
            foreach ($dto->items as $item) {
                $subtotal += ($item->qty * $item->price) - $item->discountAmount;
            }

            $appliedPromotions = [];
            $discountType = $dto->discountType;
            $discountValue = (float) $dto->discountValue;
            $discountAmount = $discountValue;
            $promoName = null;

            // Evaluasi Auto Promo jika diskon bukan manual khusus bernilai > 0
            if ($discountType !== 'manual' || $discountValue <= 0) {
                $cartItems = [];
                foreach ($dto->items as $idx => $itemDto) {
                    $cartItems[] = new CartItemDTO(
                        id: 'item_'.$idx,
                        productId: $itemDto->productId,
                        productItemId: $itemDto->productItemId ?? $itemDto->inventoryItemId ?? $itemDto->productId,
                        categoryId: null,
                        quantity: (float) $itemDto->qty,
                        unitPrice: (float) $itemDto->price,
                        subtotal: (float) (($itemDto->qty * $itemDto->price) - $itemDto->discountAmount)
                    );
                }

                $cartEvaluation = new CartEvaluationDTO(
                    businessId: $outlet->business_id,
                    outletId: $outlet->id,
                    channel: $dto->channel->value,
                    items: $cartItems,
                    evaluatedAt: Carbon::instance($dto->transactionDate),
                    subtotal: $subtotal,
                    promoCode: $dto->promoCode ?? null,
                );

                $evalResult = $this->promotionEvaluator->evaluate($cartEvaluation);

                if ($evalResult->totalDiscount > 0) {
                    $discountType = 'promo';
                    $discountAmount = $evalResult->totalDiscount;
                    $discountValue = $evalResult->totalDiscount;
                    $promoName = ! empty($evalResult->appliedPromotions) ? $evalResult->appliedPromotions[0]->promotionName : 'Promo Otomatis';
                    $appliedPromotions = $evalResult->appliedPromotions;
                }
            }

            $total = max(0.0, $subtotal - $discountAmount + ($dto->taxAmount ?? 0.0) + $dto->shippingFee + $dto->serviceChargeAmount);

            $transaction = new Transaction;
            $transaction->outlet_id = $outlet->id;
            $transaction->customer_id = $dto->customerId;
            $transaction->channel = $dto->channel;
            $transaction->transaction_number = $transactionNumber;
            $transaction->transaction_date = $dto->transactionDate;

            $transaction->subtotal = $subtotal;
            $transaction->discount_type = $discountType;
            $transaction->discount_value = $discountValue;
            $transaction->discount_amount = $discountAmount;
            $transaction->promo_name = $promoName;

            $transaction->tax_amount = $dto->taxAmount ?? 0.0;
            $transaction->shipping_fee = $dto->shippingFee;
            $transaction->service_charge_amount = $dto->serviceChargeAmount;
            $transaction->total = $total;
            $transaction->balance_due = $total;

            $transaction->status = TransactionStatus::Draft;
            $transaction->payment_status = TransactionPaymentStatus::Draft;
            $transaction->notes = $dto->notes;
            $transaction->created_by = $user->id;
            $transaction->updated_by = $user->id;
            $transaction->save();

            // Simpan Invoice (Extension)
            $invoiceNumber = $this->transactionService->generateInvoiceNumber($outlet, $dto->transactionDate);
            $invoice = new TransactionInvoice;
            $invoice->transaction_id = $transaction->id;
            $invoice->invoice_number = $invoiceNumber;
            $invoice->invoice_date = $dto->transactionDate;
            $invoice->due_date = $dto->dueDate;
            $invoice->payment_term = $dto->paymentTerm;
            $invoice->payment_term_code = $dto->paymentTermCode;
            $invoice->status = TransactionStatus::Draft;
            $invoice->created_by = $user->id;
            $invoice->save();

            // Simpan Item Penjualan
            foreach ($dto->items as $itemDto) {
                $productName = 'Produk '.$itemDto->productId;
                $sku = null;
                $uomName = 'Pcs';
                $inventoryItemId = $itemDto->inventoryItemId;

                if ($itemDto->productItemId) {
                    $productItem = ProductItem::with(['product', 'uom', 'inventoryItem'])->find($itemDto->productItemId);
                    if ($productItem) {
                        $productName = $productItem->name ?: ($productItem->product?->name ?? $productName);
                        $sku = $productItem->sku ?: ($productItem->product?->code ?? null);
                        $uomName = $productItem->uom?->name ?? 'Pcs';
                        $inventoryItemId = $inventoryItemId ?? $productItem->inventoryItem?->id;
                    }
                } elseif ($itemDto->productId) {
                    $product = Product::with(['productItems.inventoryItem', 'productItems.uom'])->find($itemDto->productId);
                    if ($product) {
                        $productName = $product->name;
                        $sku = $product->code;
                        $firstItem = $product->productItems->first();
                        if ($firstItem) {
                            $uomName = $firstItem->uom?->name ?? 'Pcs';
                            $inventoryItemId = $inventoryItemId ?? $firstItem->inventoryItem?->id;
                        }
                    }
                }

                if ($inventoryItemId && ! $itemDto->productItemId && ! $itemDto->productId) {
                    $invItem = InventoryItem::with(['product', 'productItem', 'uom'])->find($inventoryItemId);
                    if ($invItem) {
                        $productName = $invItem->name ?? $productName;
                        $sku = $invItem->sku ?? $invItem->productItem?->sku;
                        $uomName = $invItem->uom?->name ?? 'Pcs';
                    }
                }

                $item = new TransactionItem;
                $item->transaction_id = $transaction->id;
                $item->product_id = $itemDto->productId;
                $item->product_item_id = $itemDto->productItemId;
                $item->inventory_item_id = $inventoryItemId;
                $item->product_name = $productName;
                $item->sku = $sku;
                $item->uom_name = $uomName;
                $item->price = $itemDto->price;
                $item->qty = $itemDto->qty;
                $item->discount_amount = $itemDto->discountAmount;
                $item->subtotal = ($itemDto->qty * $itemDto->price) - $itemDto->discountAmount;
                $item->notes = $itemDto->notes;
                $item->save();
            }

            // Simpan Snapshot Promo
            foreach ($appliedPromotions as $appliedPromo) {
                TransactionPromo::create([
                    'transaction_id' => $transaction->id,
                    'promo_id' => $appliedPromo->promotionId,
                    'promo_name' => $appliedPromo->promotionName,
                    'discount_type' => $appliedPromo->discountType ?? 'fixed',
                    'discount_value' => $appliedPromo->discountValue ?? $appliedPromo->discountAmount,
                    'discount_amount' => $appliedPromo->discountAmount,
                ]);
            }

            // Log activity
            $this->auditLogger->log(
                module: AuditModuleEnum::POS->value,
                action: 'transaction.created',
                description: "Draf transaksi penjualan {$transactionNumber} dibuat",
                subject: $transaction,
                causer: $user,
                businessId: $user->business_id,
                outletId: $outlet->id,
            );

            return $transaction->refresh();
        });
    }

    public function issueInvoice(Transaction $transaction, User $user, array $paymentData = []): Transaction
    {
        if ($transaction->status !== TransactionStatus::Draft) {
            throw new InvalidArgumentException('Hanya draf yang dapat diterbitkan menjadi faktur resmi.');
        }

        return DB::transaction(function () use ($transaction, $user, $paymentData) {
            $outlet = $transaction->outlet;

            // 1. Validasi Stok (jika tidak diizinkan minus)
            $itemArray = $transaction->items->map(function ($item) {
                return [
                    'inventory_item_id' => $item->inventory_item_id,
                    'qty' => $item->qty,
                    'product_name' => $item->product_name,
                ];
            })->toArray();

            $this->transactionService->checkStockAvailability($itemArray, $outlet);

            // 2. Pemotongan Stok dan kalkulasi HPP FIFO
            $this->transactionService->deductStockForTransaction($transaction, $user);

            // 3. Update status menjadi unpaid
            $transaction->status = TransactionStatus::Unpaid;
            $transaction->payment_status = TransactionPaymentStatus::Unpaid;
            $transaction->updated_by = $user->id;
            $transaction->save();

            if ($transaction->invoice) {
                $transaction->invoice->status = TransactionStatus::Unpaid;
                $transaction->invoice->save();
            }

            // 4. Jika ada pembayaran awal (misal termin Cash atau ada DP di depan)
            if (! empty($paymentData) && isset($paymentData['amount']) && $paymentData['amount'] > 0) {
                $dto = new RecordPaymentDTO(
                    paymentMethodId: $paymentData['payment_method_id'],
                    amount: (float) $paymentData['amount'],
                    paymentDate: isset($paymentData['payment_date']) ? Carbon::parse($paymentData['payment_date']) : now(),
                    changeAmount: (float) ($paymentData['change_amount'] ?? 0.0),
                    paymentReference: $paymentData['payment_reference'] ?? null,
                    notes: $paymentData['notes'] ?? null,
                );

                // Ini akan mengupdate status ke Partial atau Paid
                $this->paymentService->recordPayment($transaction, $dto, $user);
            }

            // Log activity
            $this->auditLogger->log(
                module: AuditModuleEnum::POS->value,
                action: 'transaction.issued',
                description: "Faktur penjualan {$transaction->invoice?->invoice_number} diterbitkan",
                subject: $transaction,
                causer: $user,
                businessId: $user->business_id,
                outletId: $outlet->id,
            );

            return $transaction->refresh();
        });
    }

    public function recordPayment(Transaction $transaction, RecordPaymentDTO $dto, User $user): Transaction
    {
        return $this->paymentService->recordPayment($transaction, $dto, $user);
    }

    public function updateDueDate(Transaction $transaction, string $newDueDate, User $user, ?string $reason = null): Transaction
    {
        if (! $transaction->invoice) {
            throw new InvalidArgumentException('Faktur tidak ditemukan.');
        }

        if (in_array($transaction->status, [TransactionStatus::Paid, TransactionStatus::Cancel, TransactionStatus::Void], true)) {
            throw new InvalidArgumentException('Tidak dapat mengubah tanggal jatuh tempo pada faktur yang sudah lunas atau batal.');
        }

        return DB::transaction(function () use ($transaction, $newDueDate, $user, $reason) {
            $transaction->invoice->due_date = $newDueDate;
            $transaction->invoice->save();

            $transaction->updated_by = $user->id;
            $transaction->save();

            // Catat activity log
            $this->auditLogger->log(
                module: AuditModuleEnum::POS->value,
                action: 'transaction.due_date_updated',
                description: "Tanggal jatuh tempo diubah menjadi {$newDueDate}. Alasan: ".($reason ?? '-'),
                subject: $transaction,
                causer: $user,
                businessId: $user->business_id,
                outletId: $transaction->outlet_id,
            );

            return $transaction;
        });
    }

    public function cancelTransaction(Transaction $transaction, User $user, ?string $reason = null): Transaction
    {
        if (in_array($transaction->status, [TransactionStatus::Cancel, TransactionStatus::Void, TransactionStatus::Draft], true)) {
            throw new InvalidArgumentException('Transaksi tidak valid untuk dibatalkan.');
        }

        return DB::transaction(function () use ($transaction, $user, $reason) {
            // 1. Reversal Stok dan FIFO cost layer
            $this->transactionService->reverseStockForTransaction($transaction, $user);

            // 2. Update status ke Cancel
            $transaction->status = TransactionStatus::Cancel;
            $transaction->updated_by = $user->id;
            $transaction->save();

            if ($transaction->invoice) {
                $transaction->invoice->status = TransactionStatus::Cancel;
                $transaction->invoice->save();
            }

            // Catat log pembatalan
            $this->auditLogger->log(
                module: AuditModuleEnum::POS->value,
                action: 'transaction.cancelled',
                description: 'Faktur dibatalkan. Alasan: '.($reason ?? '-'),
                subject: $transaction,
                causer: $user,
                businessId: $user->business_id,
                outletId: $transaction->outlet_id,
            );

            return $transaction;
        });
    }
}
