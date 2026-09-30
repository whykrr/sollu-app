<?php

declare(strict_types=1);

namespace App\Services\App\Transaction;

use App\Contracts\Audit\ActivityLoggerInterface;
use App\DTOs\Promotion\CartEvaluationDTO;
use App\DTOs\Promotion\CartItemDTO;
use App\DTOs\Transaction\CreateB2bTransactionDTO;
use App\DTOs\Transaction\CreateTransactionItemDTO;
use App\DTOs\Transaction\RecordPaymentDTO;
use App\Enums\AuditModuleEnum;
use App\Enums\TransactionPaymentStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionTypeEnum;
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
use Illuminate\Support\Collection;
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

            $masterData = $this->prefetchMasterCatalogData($dto->items, $outlet);
            $productItemsMap = $masterData['productItemsMap'];
            $productsMap = $masterData['productsMap'];
            $inventoryItemsMap = $masterData['inventoryItemsMap'];
            $catalogPrices = $masterData['catalogPrices'];

            $transactionNumber = $this->transactionService->generateTransactionNumber($outlet, $dto->transactionDate);

            // 1. Siapkan Cart Items untuk evaluasi promo (dengan harga katalog terverifikasi)
            $cartItems = [];
            $grossSubtotal = 0.0;
            $resolvedPrices = [];

            foreach ($dto->items as $idx => $itemDto) {
                $unitPrice = $this->resolveOfficialUnitPrice($itemDto, $outlet->id, $catalogPrices);
                $resolvedPrices[$idx] = $unitPrice;

                $lineGross = (float) ($itemDto->qty * $unitPrice);
                $grossSubtotal += $lineGross;
                $cartItems[] = new CartItemDTO(
                    id: 'item_'.$idx,
                    productId: $itemDto->productId,
                    productItemId: $itemDto->productItemId ?? $itemDto->inventoryItemId ?? $itemDto->productId,
                    categoryId: null,
                    quantity: (float) $itemDto->qty,
                    unitPrice: $unitPrice,
                    subtotal: max(0.0, $lineGross - (float) $itemDto->discountAmount)
                );
            }

            // 2. Evaluasi Promosi (jika bukan manual override khusus bernilai > 0 tanpa promoCode)
            $appliedPromotions = [];
            $evalResult = null;
            if ($dto->discountType !== 'manual' || $dto->discountValue <= 0 || ! empty($dto->promoCode)) {
                $cartEvaluation = new CartEvaluationDTO(
                    businessId: $outlet->business_id,
                    outletId: $outlet->id,
                    channel: $dto->channel->value,
                    items: $cartItems,
                    evaluatedAt: Carbon::instance($dto->transactionDate),
                    subtotal: $grossSubtotal,
                    promoCode: $dto->promoCode ?? null,
                );

                $evalResult = $this->promotionEvaluator->evaluate($cartEvaluation);
                if (! empty($evalResult->appliedPromotions)) {
                    $appliedPromotions = $evalResult->appliedPromotions;
                }
            }

            // 3. Hitung Item Line Discounts (Hanya dari promo level item atau diskon manual baris)
            $itemLevelPromos = array_values(array_filter(
                $appliedPromotions,
                fn ($p) => $p->allocationLevel === 'item'
            ));

            $txAppliedPromos = array_values(array_filter(
                $appliedPromotions,
                fn ($p) => $p->allocationLevel === 'transaction'
            ));

            $itemsData = [];
            $computedSubtotal = 0.0;

            foreach ($dto->items as $idx => $itemDto) {
                $unitPrice = $resolvedPrices[$idx] ?? (float) $itemDto->price;
                $lineKey = 'item_'.$idx;
                $itemDiscountAmount = (float) $itemDto->discountAmount;
                $itemAppliedPromo = null;

                // Jika item tidak memiliki diskon manual, hitung alokasi dari auto promo level item
                if ($itemDiscountAmount <= 0 && ! empty($itemLevelPromos)) {
                    foreach ($itemLevelPromos as $promo) {
                        if (in_array($lineKey, $promo->affectedItemIds, true)) {
                            $itemAppliedPromo = $promo;
                            $matchingCartItems = array_filter($cartItems, fn ($ci) => in_array($ci->id, $promo->affectedItemIds, true));
                            $matchingSubtotal = (float) array_sum(array_map(fn ($ci) => $ci->subtotal, $matchingCartItems));
                            $itemSub = (float) ($cartItems[$idx]->subtotal ?? 0.0);
                            $lineRatio = $matchingSubtotal > 0 ? ($itemSub / $matchingSubtotal) : 1.0;
                            $itemDiscountAmount += round($promo->discountAmount * $lineRatio, 4);
                        }
                    }
                }

                $lineSubtotal = max(0.0, ((float) $itemDto->qty * $unitPrice) - $itemDiscountAmount);
                $computedSubtotal += $lineSubtotal;

                $itemsData[] = [
                    'dto' => $itemDto,
                    'price' => $unitPrice,
                    'discount_amount' => $itemDiscountAmount,
                    'subtotal' => $lineSubtotal,
                    'applied_promo' => $itemAppliedPromo,
                ];
            }

            // 4. Hitung Diskon Level Transaksi (Header)
            $txDiscountType = $dto->discountType;
            $txDiscountValue = (float) $dto->discountValue;
            $txDiscountAmount = 0.0;
            $txPromoName = null;

            if ($dto->discountType === 'manual' && $txDiscountValue > 0) {
                $txDiscountAmount = $txDiscountValue;
            } elseif ($dto->discountType === 'fixed' && $txDiscountValue > 0 && empty($dto->promoCode) && empty($txAppliedPromos)) {
                $txDiscountAmount = $txDiscountValue;
            } elseif ($dto->discountType === 'percentage' && $txDiscountValue > 0 && empty($dto->promoCode) && empty($txAppliedPromos)) {
                $txDiscountAmount = round(($computedSubtotal * $txDiscountValue) / 100.0, 4);
            } elseif (! empty($txAppliedPromos)) {
                $txDiscountType = 'promo';
                $txDiscountAmount = (float) array_sum(array_map(fn ($p) => $p->discountAmount, $txAppliedPromos));
                $txDiscountValue = $txDiscountAmount;
                $txPromoName = implode(', ', array_map(fn ($p) => $p->promotionName, $txAppliedPromos));
            } elseif ($dto->discountValue > 0) {
                $txDiscountAmount = $txDiscountValue;
            }

            // Pastikan diskon transaksi tidak melebihi computed subtotal
            $txDiscountAmount = min($txDiscountAmount, $computedSubtotal);

            // Grand Total = Subtotal Net Item - Diskon Dokumen + Pajak + Ongkir + Layanan
            $total = max(0.0, $computedSubtotal - $txDiscountAmount + ($dto->taxAmount ?? 0.0) + $dto->shippingFee + $dto->serviceChargeAmount);

            // 5. Buat Header Transaksi
            $transaction = new Transaction;
            $transaction->outlet_id = $outlet->id;
            $transaction->customer_id = $dto->customerId;
            $transaction->channel = $dto->channel;
            $transaction->type = TransactionTypeEnum::Invoice;
            $transaction->transaction_number = $transactionNumber;
            $transaction->transaction_date = $dto->transactionDate;

            $transaction->subtotal = $computedSubtotal;
            $transaction->discount_type = $txDiscountType;
            $transaction->discount_value = $txDiscountValue;
            $transaction->discount_amount = $txDiscountAmount;
            $transaction->promo_name = $txPromoName;

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

            // 6. Simpan Invoice (Extension)
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

            // 7. Simpan Baris Item Penjualan & Snapshot Promo Per-Item
            foreach ($itemsData as $itemData) {
                $itemDto = $itemData['dto'];
                $productName = 'Produk '.$itemDto->productId;
                $sku = null;
                $uomName = 'Pcs';
                $inventoryItemId = $itemDto->inventoryItemId;

                if ($itemDto->productItemId && $productItemsMap->has($itemDto->productItemId)) {
                    $productItem = $productItemsMap->get($itemDto->productItemId);
                    $productName = $productItem->name ?: ($productItem->product?->name ?? $productName);
                    $sku = $productItem->sku ?: ($productItem->product?->code ?? null);
                    $uomName = $productItem->uom?->name ?? 'Pcs';
                    $inventoryItemId = $inventoryItemId ?? $productItem->inventoryItem?->id;
                } elseif ($itemDto->productId && $productsMap->has($itemDto->productId)) {
                    $product = $productsMap->get($itemDto->productId);
                    $productName = $product->name;
                    $sku = $product->code;
                    $firstItem = $product->productItems->first();
                    if ($firstItem) {
                        $uomName = $firstItem->uom?->name ?? 'Pcs';
                        $inventoryItemId = $inventoryItemId ?? $firstItem->inventoryItem?->id;
                    }
                }

                if ($inventoryItemId && ! $itemDto->productItemId && ! $itemDto->productId && $inventoryItemsMap->has($inventoryItemId)) {
                    $invItem = $inventoryItemsMap->get($inventoryItemId);
                    $productName = $invItem->name ?? $productName;
                    $sku = $invItem->sku ?? $invItem->productItem?->sku;
                    $uomName = $invItem->uom?->name ?? 'Pcs';
                }

                $appliedPromo = $itemData['applied_promo'];
                $itemPromoName = $appliedPromo ? $appliedPromo->promotionName : null;

                $item = new TransactionItem;
                $item->transaction_id = $transaction->id;
                $item->product_id = $itemDto->productId;
                $item->product_item_id = $itemDto->productItemId;
                $item->inventory_item_id = $inventoryItemId;
                $item->product_name = $productName;
                $item->sku = $sku;
                $item->uom_name = $uomName;
                $item->price = $itemData['price'];
                $item->qty = $itemDto->qty;
                $item->discount_amount = $itemData['discount_amount'];
                $item->subtotal = $itemData['subtotal'];
                $item->promo_name = $itemPromoName;
                $item->notes = $itemDto->notes;
                $item->save();

                // Snapshot Promo Per-Item (jika ada promo item terapply)
                if ($appliedPromo) {
                    TransactionPromo::create([
                        'transaction_id' => $transaction->id,
                        'transaction_item_id' => $item->id,
                        'promo_id' => $appliedPromo->promotionId,
                        'promo_name' => $appliedPromo->promotionName,
                        'promo_code' => $appliedPromo->promoCode,
                        'target_scope' => $appliedPromo->targetScope,
                        'discount_type' => $appliedPromo->discountType ?? 'fixed',
                        'discount_value' => $appliedPromo->discountValue ?? $itemData['discount_amount'],
                        'discount_amount' => $itemData['discount_amount'],
                    ]);
                }
            }

            // 8. Snapshot Promo Dokumen / Transaksi (transaction_item_id = null)
            foreach ($txAppliedPromos as $appliedPromo) {
                TransactionPromo::create([
                    'transaction_id' => $transaction->id,
                    'transaction_item_id' => null,
                    'promo_id' => $appliedPromo->promotionId,
                    'promo_name' => $appliedPromo->promotionName,
                    'promo_code' => $appliedPromo->promoCode,
                    'target_scope' => $appliedPromo->targetScope,
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
            $transaction->loadMissing(['items.inventoryItem', 'outlet.business']);

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

    public function updateDraftTransaction(Transaction $transaction, CreateB2bTransactionDTO $dto, User $user): Transaction
    {
        if ($transaction->status !== TransactionStatus::Draft) {
            throw new InvalidArgumentException('Hanya draf transaksi yang dapat diubah.');
        }

        return DB::transaction(function () use ($transaction, $dto, $user) {
            $outlet = Outlet::findOrFail($dto->outletId);

            $masterData = $this->prefetchMasterCatalogData($dto->items, $outlet);
            $productItemsMap = $masterData['productItemsMap'];
            $productsMap = $masterData['productsMap'];
            $inventoryItemsMap = $masterData['inventoryItemsMap'];
            $catalogPrices = $masterData['catalogPrices'];

            // 1. Siapkan Cart Items untuk evaluasi promo (dengan harga katalog terverifikasi)
            $cartItems = [];
            $grossSubtotal = 0.0;
            $resolvedPrices = [];

            foreach ($dto->items as $idx => $itemDto) {
                $unitPrice = $this->resolveOfficialUnitPrice($itemDto, $outlet->id, $catalogPrices);
                $resolvedPrices[$idx] = $unitPrice;

                $lineGross = (float) ($itemDto->qty * $unitPrice);
                $grossSubtotal += $lineGross;
                $cartItems[] = new CartItemDTO(
                    id: 'item_'.$idx,
                    productId: $itemDto->productId,
                    productItemId: $itemDto->productItemId ?? $itemDto->inventoryItemId ?? $itemDto->productId,
                    categoryId: null,
                    quantity: (float) $itemDto->qty,
                    unitPrice: $unitPrice,
                    subtotal: max(0.0, $lineGross - (float) $itemDto->discountAmount)
                );
            }

            // 2. Evaluasi Promosi (jika bukan manual override khusus bernilai > 0 tanpa promoCode)
            $appliedPromotions = [];
            $evalResult = null;
            if ($dto->discountType !== 'manual' || $dto->discountValue <= 0 || ! empty($dto->promoCode)) {
                $cartEvaluation = new CartEvaluationDTO(
                    businessId: $outlet->business_id,
                    outletId: $outlet->id,
                    channel: $dto->channel->value,
                    items: $cartItems,
                    evaluatedAt: Carbon::instance($dto->transactionDate),
                    subtotal: $grossSubtotal,
                    promoCode: $dto->promoCode ?? null,
                );

                $evalResult = $this->promotionEvaluator->evaluate($cartEvaluation);
                if (! empty($evalResult->appliedPromotions)) {
                    $appliedPromotions = $evalResult->appliedPromotions;
                }
            }

            // 3. Hitung Item Line Discounts
            $itemLevelPromos = array_values(array_filter(
                $appliedPromotions,
                fn ($p) => $p->allocationLevel === 'item'
            ));

            $txAppliedPromos = array_values(array_filter(
                $appliedPromotions,
                fn ($p) => $p->allocationLevel === 'transaction'
            ));

            $itemsData = [];
            $computedSubtotal = 0.0;

            foreach ($dto->items as $idx => $itemDto) {
                $unitPrice = $resolvedPrices[$idx] ?? (float) $itemDto->price;
                $lineKey = 'item_'.$idx;
                $itemDiscountAmount = (float) $itemDto->discountAmount;
                $itemAppliedPromo = null;

                if ($itemDiscountAmount <= 0 && ! empty($itemLevelPromos)) {
                    foreach ($itemLevelPromos as $promo) {
                        if (in_array($lineKey, $promo->affectedItemIds, true)) {
                            $itemAppliedPromo = $promo;
                            $matchingCartItems = array_filter($cartItems, fn ($ci) => in_array($ci->id, $promo->affectedItemIds, true));
                            $matchingSubtotal = (float) array_sum(array_map(fn ($ci) => $ci->subtotal, $matchingCartItems));
                            $itemSub = (float) ($cartItems[$idx]->subtotal ?? 0.0);
                            $lineRatio = $matchingSubtotal > 0 ? ($itemSub / $matchingSubtotal) : 1.0;
                            $itemDiscountAmount += round($promo->discountAmount * $lineRatio, 4);
                        }
                    }
                }

                $lineSubtotal = max(0.0, ((float) $itemDto->qty * $unitPrice) - $itemDiscountAmount);
                $computedSubtotal += $lineSubtotal;

                $itemsData[] = [
                    'dto' => $itemDto,
                    'price' => $unitPrice,
                    'discount_amount' => $itemDiscountAmount,
                    'subtotal' => $lineSubtotal,
                    'applied_promo' => $itemAppliedPromo,
                ];
            }

            // 4. Hitung Diskon Level Transaksi (Header)
            $txDiscountType = $dto->discountType;
            $txDiscountValue = (float) $dto->discountValue;
            $txDiscountAmount = 0.0;
            $txPromoName = null;

            if ($dto->discountType === 'manual' && $txDiscountValue > 0) {
                $txDiscountAmount = $txDiscountValue;
            } elseif ($dto->discountType === 'fixed' && $txDiscountValue > 0 && empty($dto->promoCode) && empty($txAppliedPromos)) {
                $txDiscountAmount = $txDiscountValue;
            } elseif ($dto->discountType === 'percentage' && $txDiscountValue > 0 && empty($dto->promoCode) && empty($txAppliedPromos)) {
                $txDiscountAmount = round(($computedSubtotal * $txDiscountValue) / 100.0, 4);
            } elseif (! empty($txAppliedPromos)) {
                $txDiscountType = 'promo';
                $txDiscountAmount = (float) array_sum(array_map(fn ($p) => $p->discountAmount, $txAppliedPromos));
                $txDiscountValue = $txDiscountAmount;
                $txPromoName = implode(', ', array_map(fn ($p) => $p->promotionName, $txAppliedPromos));
            } elseif ($dto->discountValue > 0) {
                $txDiscountAmount = $txDiscountValue;
            }

            $txDiscountAmount = min($txDiscountAmount, $computedSubtotal);
            $total = max(0.0, $computedSubtotal - $txDiscountAmount + ($dto->taxAmount ?? 0.0) + $dto->shippingFee + $dto->serviceChargeAmount);

            // Update Header
            $transaction->outlet_id = $outlet->id;
            $transaction->customer_id = $dto->customerId;
            $transaction->channel = $dto->channel;
            $transaction->transaction_date = $dto->transactionDate;
            $transaction->subtotal = $computedSubtotal;
            $transaction->discount_type = $txDiscountType;
            $transaction->discount_value = $txDiscountValue;
            $transaction->discount_amount = $txDiscountAmount;
            $transaction->promo_name = $txPromoName;
            $transaction->tax_amount = $dto->taxAmount ?? 0.0;
            $transaction->shipping_fee = $dto->shippingFee;
            $transaction->service_charge_amount = $dto->serviceChargeAmount;
            $transaction->total = $total;
            $transaction->balance_due = $total;
            $transaction->notes = $dto->notes;
            $transaction->updated_by = $user->id;
            $transaction->save();

            // Update Invoice
            if ($transaction->invoice) {
                $transaction->invoice->due_date = $dto->dueDate;
                $transaction->invoice->payment_term = $dto->paymentTerm;
                $transaction->invoice->payment_term_code = $dto->paymentTermCode;
                $transaction->invoice->save();
            }

            // Hapus promo dan item lama
            $transaction->promos()->delete();
            $transaction->items()->delete();

            // Re-create items & promos
            foreach ($itemsData as $itemData) {
                $itemDto = $itemData['dto'];
                $productName = 'Produk '.$itemDto->productId;
                $sku = null;
                $uomName = 'Pcs';
                $inventoryItemId = $itemDto->inventoryItemId;

                if ($itemDto->productItemId && $productItemsMap->has($itemDto->productItemId)) {
                    $productItem = $productItemsMap->get($itemDto->productItemId);
                    $productName = $productItem->name ?: ($productItem->product?->name ?? $productName);
                    $sku = $productItem->sku ?: ($productItem->product?->code ?? null);
                    $uomName = $productItem->uom?->name ?? 'Pcs';
                    $inventoryItemId = $inventoryItemId ?? $productItem->inventoryItem?->id;
                } elseif ($itemDto->productId && $productsMap->has($itemDto->productId)) {
                    $product = $productsMap->get($itemDto->productId);
                    $productName = $product->name;
                    $sku = $product->code;
                    $firstItem = $product->productItems->first();
                    if ($firstItem) {
                        $uomName = $firstItem->uom?->name ?? 'Pcs';
                        $inventoryItemId = $inventoryItemId ?? $firstItem->inventoryItem?->id;
                    }
                }

                if ($inventoryItemId && ! $itemDto->productItemId && ! $itemDto->productId && $inventoryItemsMap->has($inventoryItemId)) {
                    $invItem = $inventoryItemsMap->get($inventoryItemId);
                    $productName = $invItem->name ?? $productName;
                    $sku = $invItem->sku ?? $invItem->productItem?->sku;
                    $uomName = $invItem->uom?->name ?? 'Pcs';
                }

                $appliedPromo = $itemData['applied_promo'];
                $itemPromoName = $appliedPromo ? $appliedPromo->promotionName : null;

                $item = new TransactionItem;
                $item->transaction_id = $transaction->id;
                $item->product_id = $itemDto->productId;
                $item->product_item_id = $itemDto->productItemId;
                $item->inventory_item_id = $inventoryItemId;
                $item->product_name = $productName;
                $item->sku = $sku;
                $item->uom_name = $uomName;
                $item->price = $itemData['price'];
                $item->qty = $itemDto->qty;
                $item->discount_amount = $itemData['discount_amount'];
                $item->subtotal = $itemData['subtotal'];
                $item->promo_name = $itemPromoName;
                $item->notes = $itemDto->notes;
                $item->save();

                if ($appliedPromo) {
                    TransactionPromo::create([
                        'transaction_id' => $transaction->id,
                        'transaction_item_id' => $item->id,
                        'promo_id' => $appliedPromo->promotionId,
                        'promo_name' => $appliedPromo->promotionName,
                        'promo_code' => $appliedPromo->promoCode,
                        'target_scope' => $appliedPromo->targetScope,
                        'discount_type' => $appliedPromo->discountType ?? 'fixed',
                        'discount_value' => $appliedPromo->discountValue ?? $itemData['discount_amount'],
                        'discount_amount' => $itemData['discount_amount'],
                    ]);
                }
            }

            foreach ($txAppliedPromos as $appliedPromo) {
                TransactionPromo::create([
                    'transaction_id' => $transaction->id,
                    'transaction_item_id' => null,
                    'promo_id' => $appliedPromo->promotionId,
                    'promo_name' => $appliedPromo->promotionName,
                    'promo_code' => $appliedPromo->promoCode,
                    'target_scope' => $appliedPromo->targetScope,
                    'discount_type' => $appliedPromo->discountType ?? 'fixed',
                    'discount_value' => $appliedPromo->discountValue ?? $appliedPromo->discountAmount,
                    'discount_amount' => $appliedPromo->discountAmount,
                ]);
            }

            $this->auditLogger->log(
                module: AuditModuleEnum::POS->value,
                action: 'transaction.draft_updated',
                description: "Draf transaksi penjualan {$transaction->transaction_number} diperbarui",
                subject: $transaction,
                causer: $user,
                businessId: $user->business_id,
                outletId: $outlet->id,
            );

            return $transaction->refresh();
        });
    }

    public function deleteDraftTransaction(Transaction $transaction, User $user): void
    {
        if ($transaction->status !== TransactionStatus::Draft) {
            throw new InvalidArgumentException('Hanya faktur berstatus draf yang dapat dihapus.');
        }

        DB::transaction(function () use ($transaction, $user) {
            $transactionNumber = $transaction->transaction_number;
            $outletId = $transaction->outlet_id;

            $this->auditLogger->log(
                module: AuditModuleEnum::POS->value,
                action: 'transaction.draft_deleted',
                description: "Draf transaksi penjualan {$transactionNumber} dihapus",
                subject: $transaction,
                causer: $user,
                businessId: $user->business_id,
                outletId: $outletId,
            );

            $transaction->delete();
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

    /**
     * Pre-fetch data master produk, varian, inventori, dan harga resmi secara massal untuk menghindari N+1 kueri.
     *
     * @param  array<CreateTransactionItemDTO>  $items
     * @return array{productItemsMap: Collection, productsMap: Collection, inventoryItemsMap: Collection, catalogPrices: Collection}
     */
    protected function prefetchMasterCatalogData(array $items, Outlet $outlet): array
    {
        $productItemIds = array_values(array_filter(array_map(fn ($item) => $item->productItemId, $items)));
        $productIds = array_values(array_filter(array_map(fn ($item) => $item->productId, $items)));
        $invItemIds = array_values(array_filter(array_map(fn ($item) => $item->inventoryItemId, $items)));

        $productItemsMap = ! empty($productItemIds)
            ? ProductItem::whereIn('id', $productItemIds)
                ->with(['product', 'uom', 'inventoryItem'])
                ->get()
                ->keyBy('id')
            : collect();

        $productsMap = ! empty($productIds)
            ? Product::whereIn('id', $productIds)
                ->with(['productItems.inventoryItem', 'productItems.uom'])
                ->get()
                ->keyBy('id')
            : collect();

        $inventoryItemsMap = ! empty($invItemIds)
            ? InventoryItem::whereIn('id', $invItemIds)
                ->with(['product', 'productItem', 'uom'])
                ->get()
                ->keyBy('id')
            : collect();

        $catalogPrices = DB::table('product_prices')
            ->where(function ($q) use ($productItemIds, $productIds) {
                if (! empty($productItemIds)) {
                    $q->whereIn('product_item_id', $productItemIds);
                }
                if (! empty($productIds)) {
                    $q->orWhereIn('product_id', $productIds);
                }
            })
            ->where(function ($q) use ($outlet) {
                $q->where('outlet_id', $outlet->id)->orWhereNull('outlet_id');
            })
            ->get();

        return [
            'productItemsMap' => $productItemsMap,
            'productsMap' => $productsMap,
            'inventoryItemsMap' => $inventoryItemsMap,
            'catalogPrices' => $catalogPrices,
        ];
    }

    /**
     * Resolusi harga satuan resmi dari katalog master outlet/produk untuk mencegah manipulasi client-side.
     */
    protected function resolveOfficialUnitPrice(
        CreateTransactionItemDTO $itemDto,
        string $outletId,
        Collection $catalogPrices
    ): float {
        if ($catalogPrices->isNotEmpty()) {
            $foundPrice = null;

            if ($itemDto->productItemId) {
                $foundPrice = $catalogPrices->where('product_item_id', $itemDto->productItemId)
                    ->firstWhere('outlet_id', $outletId)?->amount
                    ?? $catalogPrices->where('product_item_id', $itemDto->productItemId)
                        ->firstWhere('outlet_id', null)?->amount;
            }

            if ($foundPrice === null && $itemDto->productId) {
                $foundPrice = $catalogPrices->where('product_id', $itemDto->productId)
                    ->whereNull('product_item_id')
                    ->firstWhere('outlet_id', $outletId)?->amount
                    ?? $catalogPrices->where('product_id', $itemDto->productId)
                        ->whereNull('product_item_id')
                        ->firstWhere('outlet_id', null)?->amount;
            }

            if ($foundPrice !== null && (float) $foundPrice > 0) {
                return (float) $foundPrice;
            }
        }

        return (float) $itemDto->price;
    }
}
