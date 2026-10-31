<?php

declare(strict_types=1);

namespace App\Services\App\Transaction;

use App\Contracts\Audit\ActivityLoggerInterface;
use App\Enums\AuditModuleEnum;
use App\Enums\InventoryMovementType;
use App\Enums\SalesChannelEnum;
use App\Enums\TransactionStatus;
use App\Enums\TransactionTypeEnum;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryMovement;
use App\Models\Master\Customer;
use App\Models\Master\ModifierOption;
use App\Models\Master\PaymentMethod;
use App\Models\Master\Product;
use App\Models\Master\ProductItem;
use App\Models\Outlet;
use App\Models\OutletDevice;
use App\Models\OutletSetting;
use App\Models\Promotion\Promotion;
use App\Models\Sales\Shift;
use App\Models\Sales\Transaction;
use App\Models\User;
use App\Services\App\Inventory\InventoryCostingService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class TransactionService
{
    public function __construct(
        private readonly InventoryCostingService $costingService,
        private readonly ?ActivityLoggerInterface $auditLogger = null,
    ) {}

    /**
    /**
     * Resolve deterministik uppercase outlet initial (>= 2 kata: inisial huruf depan tiap kata, 1 kata: 3 huruf pertama).
     */
    public function resolveOutletInitial(Outlet $outlet): string
    {
        $name = trim($outlet->name ?? $outlet->slug ?? 'OUTLET');
        $words = preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if (count($words) >= 2) {
            $initial = '';
            foreach ($words as $word) {
                $initial .= mb_substr(preg_replace('/[^a-zA-Z0-9]/', '', $word), 0, 1);
            }
            $initial = strtoupper($initial);

            return ! empty($initial) ? substr($initial, 0, 4) : 'OT';
        }

        $cleaned = strtoupper(preg_replace('/[^a-zA-Z0-9]/', '', $name));

        return mb_substr($cleaned ?: 'OUT', 0, 3);
    }

    /**
     * Generate Nomor Transaksi (TRX/YYYY.MM.DD/{Inisial Outlet}.{Inisial channel}/{Increament 4 digit})
     */
    public function generateTransactionNumber(
        Outlet $outlet,
        \DateTimeInterface $date,
        SalesChannelEnum $channel = SalesChannelEnum::Wholesale
    ): string {
        $outletInit = $this->resolveOutletInitial($outlet);
        $channelInit = $channel->initial();
        $dateFormatted = $date->format('Y.m.d');
        $prefix = "TRX/{$dateFormatted}/{$outletInit}.{$channelInit}/";

        $lastTx = Transaction::where('outlet_id', $outlet->id)
            ->where('transaction_number', 'like', $prefix.'%')
            ->orderBy('transaction_number', 'desc')
            ->lockForUpdate()
            ->first();

        $sequence = 1;
        if ($lastTx) {
            $lastSequence = (int) substr($lastTx->transaction_number, -4);
            $sequence = $lastSequence + 1;
        }

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Generate Nomor Faktur B2B ({Prefix}/YYYY.MM.DD/{Inisial Outlet}/{Increament 4 digit})
     */
    public function generateInvoiceNumber(Outlet $outlet, \DateTimeInterface $date): string
    {
        $outletInit = $this->resolveOutletInitial($outlet);
        $dateFormatted = $date->format('Y.m.d');

        $prefixSetting = $outlet->settings()
            ->where('category', 'sales')
            ->where('key', 'b2b_invoice_prefix')
            ->value('value');

        $rawPrefix = is_string($prefixSetting) && ! empty(trim($prefixSetting)) ? trim($prefixSetting) : 'INV';
        $prefixCode = strtoupper(substr(preg_replace('/[^a-zA-Z0-9\-_]/', '', $rawPrefix) ?: 'INV', 0, 5));

        $prefix = "{$prefixCode}/{$dateFormatted}/{$outletInit}/";

        $lastInvoice = DB::table('transaction_invoices')
            ->join('transactions', 'transaction_invoices.transaction_id', '=', 'transactions.id')
            ->where('transactions.outlet_id', $outlet->id)
            ->where('transaction_invoices.invoice_number', 'like', $prefix.'%')
            ->orderBy('transaction_invoices.invoice_number', 'desc')
            ->lockForUpdate()
            ->select('transaction_invoices.invoice_number')
            ->first();

        $sequence = 1;
        if ($lastInvoice) {
            $lastSequence = (int) substr($lastInvoice->invoice_number, -4);
            $sequence = $lastSequence + 1;
        }

        return $prefix.str_pad((string) $sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Validasi Ketersediaan Stok Fisik vs Toleransi Stok Negatif.
     */
    /**
     * Validasi Ketersediaan Stok Fisik vs Toleransi Stok Negatif.
     *
     * @return Collection<string, InventoryBalance>
     */
    public function checkStockAvailability(array $items, Outlet $outlet, bool $isOffline = false): Collection
    {
        $allowNegativeSetting = OutletSetting::where('outlet_id', $outlet->id)
            ->where(function ($q) {
                $q->where(function ($sq) {
                    $sq->where('category', 'pos')->where('key', 'allow_negative_stock');
                })->orWhere(function ($sq) {
                    $sq->where('category', 'sales')->whereIn('key', ['allow_negative_stock', 'allow_negative_stock_b2b']);
                });
            })
            ->pluck('value', 'key');

        $isNegativeAllowed = false;
        foreach (['allow_negative_stock_b2b', 'allow_negative_stock'] as $k) {
            if (isset($allowNegativeSetting[$k])) {
                $val = $allowNegativeSetting[$k];
                $isNegativeAllowed = is_array($val) ? ($val[0] ?? false) : (bool) $val;
                break;
            }
        }

        $invItemIds = array_values(array_unique(array_filter(array_column($items, 'inventory_item_id'))));
        if (empty($invItemIds)) {
            return collect();
        }

        // Bulk lock and fetch balances in a single query
        $balances = InventoryBalance::where('outlet_id', $outlet->id)
            ->whereIn('inventory_item_id', $invItemIds)
            ->lockForUpdate()
            ->get()
            ->keyBy('inventory_item_id');

        $hasOfflineId = false;
        foreach ($items as $item) {
            if (! empty($item['offline_id'])) {
                $hasOfflineId = true;
                break;
            }
        }

        if ($isOffline || $hasOfflineId || $isNegativeAllowed) {
            return $balances;
        }

        foreach ($items as $item) {
            $invId = $item['inventory_item_id'] ?? null;
            if (empty($invId)) {
                continue;
            }

            // Jika item dalam payload secara eksplisit menyatakan track_inventory === false, lewati
            if (isset($item['track_inventory']) && $item['track_inventory'] === false) {
                continue;
            }

            $balance = $balances->get($invId);
            $currentStock = $balance ? (float) $balance->current_stock : 0.0;
            $qty = (float) ($item['qty_deducted'] ?? $item['qty'] ?? 1);

            if ($currentStock < $qty) {
                // Pastikan item benar-benar dilacak sebelum melempar exception
                $isTracked = DB::table('inventory_items')
                    ->leftJoin('product_items', 'inventory_items.product_item_id', '=', 'product_items.id')
                    ->leftJoin('products', 'product_items.product_id', '=', 'products.id')
                    ->where('inventory_items.id', $invId)
                    ->where('inventory_items.business_id', $outlet->business_id)
                    ->where(function ($q) {
                        $q->where(function ($sq) {
                            $sq->whereNotNull('inventory_items.product_item_id')
                                ->where('product_items.track_inventory', true)
                                ->where(function ($psq) {
                                    $psq->whereNull('products.product_type')
                                        ->orWhere('products.product_type', '!=', 'service');
                                });
                        })->orWhere(function ($sq) {
                            $sq->whereNull('inventory_items.product_item_id')
                                ->where('inventory_items.is_active', true);
                        });
                    })
                    ->exists();

                if (! $isTracked) {
                    continue;
                }

                $productName = $item['product_name'] ?? 'Item';
                throw new InvalidArgumentException("Stok {$productName} tidak mencukupi. Sisa stok: {$currentStock}");
            }
        }

        return $balances;
    }

    /**
     * Potong stok inventori dan alokasikan layer FIFO cost untuk produk retail, resep BOM, dan modifier.
     *
     * @param  Collection<string, InventoryBalance>|null  $preloadedBalances
     */
    public function deductStockForTransaction(Transaction $transaction, User $user, ?Collection $preloadedBalances = null): void
    {
        $outlet = $transaction->outlet;
        $business = $outlet->business;

        $transaction->loadMissing([
            'items.product.activeRecipe.items.productItem.inventoryItem',
            'items.productItem.recipeVersions.items.productItem.inventoryItem',
            'items.inventoryItem.productItem',
        ]);

        $modifierOptionIds = collect($transaction->items)
            ->pluck('modifiers')
            ->filter()
            ->flatten(1)
            ->pluck('modifier_option_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $modifierOptions = ! empty($modifierOptionIds)
            ? ModifierOption::with('recipeItems.productItem.inventoryItem')
                ->whereIn('id', $modifierOptionIds)
                ->get()
                ->keyBy('id')
            : collect();

        $logger = $this->auditLogger ?? app(ActivityLoggerInterface::class);

        foreach ($transaction->items as $item) {
            $itemQty = (float) $item->qty;
            if ($itemQty <= 0) {
                continue;
            }

            $itemTotalCogs = 0.0;
            $product = $item->product;

            // 1. Resolusi Recipe Version aktif (Varian spesifik atau master recipe produk)
            $activeRecipe = null;
            if ($item->product_item_id && $item->productItem) {
                $activeRecipe = $item->productItem->recipeVersions->firstWhere('is_active', true);
            }
            if (! $activeRecipe && $product) {
                $activeRecipe = $product->activeRecipe;
            }

            // 2. Jika Produk Memiliki Resep Aktif: Potong Bahan Resep (BOM) via RecipeDeduction
            if ($activeRecipe && $activeRecipe->items->isNotEmpty()) {
                foreach ($activeRecipe->items as $recipeItem) {
                    $rawInvItem = $recipeItem->productItem?->inventoryItem
                        ?? InventoryItem::where('product_item_id', $recipeItem->product_item_id)->first()
                        ?? InventoryItem::find($recipeItem->product_item_id);

                    if (! $rawInvItem) {
                        continue;
                    }

                    if ($rawInvItem->relationLoaded('productItem') && $rawInvItem->productItem && ! $rawInvItem->productItem->track_inventory) {
                        continue;
                    }

                    $rawQtyToDeduct = (float) $recipeItem->qty * $itemQty;
                    if ($rawQtyToDeduct > 0) {
                        $costResult = $this->costingService->recordOutgoingStock(
                            $business,
                            $outlet,
                            $rawInvItem,
                            $rawQtyToDeduct,
                            InventoryMovementType::RecipeDeduction,
                            $transaction,
                            "Bahan Resep: {$item->product_name} ({$transaction->transaction_number})",
                            $user,
                            preloadedBalance: $preloadedBalances?->get($rawInvItem->id)
                        );
                        $itemTotalCogs += $costResult['total_cogs'];

                        $movement = $costResult['movement'] ?? null;
                        if ($movement && (float) $movement->stock_after < 0) {
                            $logger->log(
                                module: AuditModuleEnum::INVENTORY->value,
                                action: 'offline_sale_negative_stock',
                                description: "Penjualan offline transaksi #{$transaction->transaction_number} menghasilkan stok negatif pada {$rawInvItem->name} ({$movement->stock_before} -> {$movement->stock_after})",
                                subject: $rawInvItem,
                                causer: $user,
                                businessId: $business->id,
                                outletId: $outlet->id,
                                properties: [
                                    'transaction_id' => $transaction->id,
                                    'transaction_number' => $transaction->transaction_number,
                                    'inventory_item_id' => $rawInvItem->id,
                                    'item_name' => $rawInvItem->name,
                                    'qty_deducted' => $rawQtyToDeduct,
                                    'stock_before' => (float) $movement->stock_before,
                                    'stock_after' => (float) $movement->stock_after,
                                    'variance' => (float) $movement->stock_after,
                                ]
                            );
                        }
                    }
                }
            } elseif ($item->inventory_item_id) {
                // 3. Skenario Retail: Produk Langsung (Direct Variant SKU / Basic Inventory Item)
                $inventoryItem = $item->inventoryItem;
                if ($inventoryItem) {
                    $isTracked = ! ($inventoryItem->relationLoaded('productItem') && $inventoryItem->productItem && ! $inventoryItem->productItem->track_inventory);
                    if ($isTracked) {
                        $preloadedBalance = $preloadedBalances?->get($inventoryItem->id);

                        $costResult = $this->costingService->recordOutgoingStock(
                            $business,
                            $outlet,
                            $inventoryItem,
                            $itemQty,
                            InventoryMovementType::Sale,
                            $transaction,
                            "Penjualan {$transaction->transaction_number}",
                            $user,
                            preloadedBalance: $preloadedBalance
                        );
                        $itemTotalCogs += $costResult['total_cogs'];

                        $movement = $costResult['movement'] ?? null;
                        if ($movement && (float) $movement->stock_after < 0) {
                            $logger->log(
                                module: AuditModuleEnum::INVENTORY->value,
                                action: 'offline_sale_negative_stock',
                                description: "Penjualan offline transaksi #{$transaction->transaction_number} menghasilkan stok negatif pada {$item->product_name} ({$movement->stock_before} -> {$movement->stock_after})",
                                subject: $inventoryItem,
                                causer: $user,
                                businessId: $business->id,
                                outletId: $outlet->id,
                                properties: [
                                    'transaction_id' => $transaction->id,
                                    'transaction_number' => $transaction->transaction_number,
                                    'inventory_item_id' => $inventoryItem->id,
                                    'item_name' => $item->product_name,
                                    'qty_deducted' => $itemQty,
                                    'stock_before' => (float) $movement->stock_before,
                                    'stock_after' => (float) $movement->stock_after,
                                    'variance' => (float) $movement->stock_after,
                                ]
                            );
                        }
                    }
                }
            }

            // 4. Pengurangan Stok Bahan Baku untuk Modifier Option (ModifierOption.recipeItems)
            if (! empty($item->modifiers) && is_array($item->modifiers)) {
                foreach ($item->modifiers as $mod) {
                    $modOptionId = $mod['modifier_option_id'] ?? null;
                    if (! $modOptionId) {
                        continue;
                    }

                    $modOption = $modifierOptions->get($modOptionId);
                    if (! $modOption || ! $modOption->recipeItems) {
                        continue;
                    }

                    $modQty = (float) ($mod['qty'] ?? 1);
                    foreach ($modOption->recipeItems as $modRecipeItem) {
                        $rawModInvItem = $modRecipeItem->productItem?->inventoryItem
                            ?? InventoryItem::where('product_item_id', $modRecipeItem->product_item_id)->first()
                            ?? InventoryItem::find($modRecipeItem->product_item_id);

                        if (! $rawModInvItem) {
                            continue;
                        }

                        if ($rawModInvItem->relationLoaded('productItem') && $rawModInvItem->productItem && ! $rawModInvItem->productItem->track_inventory) {
                            continue;
                        }

                        $modQtyToDeduct = (float) $modRecipeItem->qty * $itemQty * $modQty;
                        if ($modQtyToDeduct > 0) {
                            $costResult = $this->costingService->recordOutgoingStock(
                                $business,
                                $outlet,
                                $rawModInvItem,
                                $modQtyToDeduct,
                                InventoryMovementType::RecipeDeduction,
                                $transaction,
                                "Bahan Modifier: {$modOption->name} ({$transaction->transaction_number})",
                                $user,
                                preloadedBalance: $preloadedBalances?->get($rawModInvItem->id)
                            );
                            $itemTotalCogs += $costResult['total_cogs'];

                            $movement = $costResult['movement'] ?? null;
                            if ($movement && (float) $movement->stock_after < 0) {
                                $logger->log(
                                    module: AuditModuleEnum::INVENTORY->value,
                                    action: 'offline_sale_negative_stock',
                                    description: "Penjualan offline transaksi #{$transaction->transaction_number} menghasilkan stok negatif pada bahan modifier {$rawModInvItem->name} ({$movement->stock_before} -> {$movement->stock_after})",
                                    subject: $rawModInvItem,
                                    causer: $user,
                                    businessId: $business->id,
                                    outletId: $outlet->id,
                                    properties: [
                                        'transaction_id' => $transaction->id,
                                        'transaction_number' => $transaction->transaction_number,
                                        'inventory_item_id' => $rawModInvItem->id,
                                        'item_name' => $rawModInvItem->name,
                                        'qty_deducted' => $modQtyToDeduct,
                                        'stock_before' => (float) $movement->stock_before,
                                        'stock_after' => (float) $movement->stock_after,
                                        'variance' => (float) $movement->stock_after,
                                    ]
                                );
                            }
                        }
                    }
                }
            }

            // 5. Simpan snapshot HPP ke baris item
            $item->unit_cogs = $itemQty > 0 ? ($itemTotalCogs / $itemQty) : 0.0;
            $item->cogs_amount = $itemTotalCogs;
            $item->save();
        }
    }

    /**
     * Reversal / Restorasi stok dan FIFO layer saat transaksi dibatalkan (retail, resep BOM, dan modifier).
     */
    public function reverseStockForTransaction(Transaction $transaction, User $user): void
    {
        $outlet = $transaction->outlet;
        $business = $outlet->business;

        $transaction->loadMissing([
            'items.product.activeRecipe.items.productItem.inventoryItem',
            'items.productItem.recipeVersions.items.productItem.inventoryItem',
            'items.inventoryItem.productItem',
        ]);

        $modifierOptionIds = collect($transaction->items)
            ->pluck('modifiers')
            ->filter()
            ->flatten(1)
            ->pluck('modifier_option_id')
            ->filter()
            ->unique()
            ->values()
            ->all();

        $modifierOptions = ! empty($modifierOptionIds)
            ? ModifierOption::with('recipeItems.productItem.inventoryItem')
                ->whereIn('id', $modifierOptionIds)
                ->get()
                ->keyBy('id')
            : collect();

        foreach ($transaction->items as $item) {
            $itemQty = (float) $item->qty;
            if ($itemQty <= 0) {
                continue;
            }

            $product = $item->product;

            // 1. Resolusi Recipe Version aktif
            $activeRecipe = null;
            if ($item->product_item_id && $item->productItem) {
                $activeRecipe = $item->productItem->recipeVersions->firstWhere('is_active', true);
            }
            if (! $activeRecipe && $product) {
                $activeRecipe = $product->activeRecipe;
            }

            // 2. Restorasi Bahan Resep (BOM) via RecipeReturn
            if ($activeRecipe && $activeRecipe->items->isNotEmpty()) {
                foreach ($activeRecipe->items as $recipeItem) {
                    $rawInvItem = $recipeItem->productItem?->inventoryItem
                        ?? InventoryItem::where('product_item_id', $recipeItem->product_item_id)->first()
                        ?? InventoryItem::find($recipeItem->product_item_id);

                    if (! $rawInvItem) {
                        continue;
                    }

                    $rawQty = (float) $recipeItem->qty * $itemQty;
                    if ($rawQty > 0) {
                        $origMovement = InventoryMovement::where('outlet_id', $outlet->id)
                            ->where('reference_id', $transaction->id)
                            ->where('inventory_item_id', $rawInvItem->id)
                            ->where('movement_type', InventoryMovementType::RecipeDeduction)
                            ->latest()
                            ->first();

                        $unitCost = $origMovement
                            ? (float) $origMovement->unit_cost
                            : (float) ($rawInvItem->balances()->where('outlet_id', $outlet->id)->value('average_cost') ?? 0);

                        $this->costingService->recordIncomingStock(
                            $business,
                            $outlet,
                            $rawInvItem,
                            $rawQty,
                            $unitCost,
                            InventoryMovementType::RecipeReturn,
                            $transaction,
                            "Pembatalan Resep: {$item->product_name} ({$transaction->transaction_number})",
                            $user
                        );
                    }
                }
            } elseif ($item->inventory_item_id) {
                // 3. Restorasi Produk Retail
                $inventoryItem = $item->inventoryItem;
                if ($inventoryItem) {
                    $unitCogs = (float) ($item->unit_cogs > 0
                        ? $item->unit_cogs
                        : ($inventoryItem->average_cost ?: ($inventoryItem->last_cost ?: 0.0)));

                    $this->costingService->restoreFifoCostLayer(
                        $business,
                        $outlet,
                        $inventoryItem,
                        $itemQty,
                        $unitCogs,
                        $transaction,
                        $user
                    );
                }
            }

            // 4. Restorasi Bahan Modifier Option via RecipeReturn
            if (! empty($item->modifiers) && is_array($item->modifiers)) {
                foreach ($item->modifiers as $mod) {
                    $modOptionId = $mod['modifier_option_id'] ?? null;
                    if (! $modOptionId) {
                        continue;
                    }

                    $modOption = $modifierOptions->get($modOptionId);
                    if (! $modOption || ! $modOption->recipeItems) {
                        continue;
                    }

                    $modQty = (float) ($mod['qty'] ?? 1);
                    foreach ($modOption->recipeItems as $modRecipeItem) {
                        $rawModInvItem = $modRecipeItem->productItem?->inventoryItem
                            ?? InventoryItem::where('product_item_id', $modRecipeItem->product_item_id)->first()
                            ?? InventoryItem::find($modRecipeItem->product_item_id);

                        if (! $rawModInvItem) {
                            continue;
                        }

                        $modQtyToRestore = (float) $modRecipeItem->qty * $itemQty * $modQty;
                        if ($modQtyToRestore > 0) {
                            $origModMovement = InventoryMovement::where('outlet_id', $outlet->id)
                                ->where('reference_id', $transaction->id)
                                ->where('inventory_item_id', $rawModInvItem->id)
                                ->where('movement_type', InventoryMovementType::RecipeDeduction)
                                ->latest()
                                ->first();

                            $unitCost = $origModMovement
                                ? (float) $origModMovement->unit_cost
                                : (float) ($rawModInvItem->balances()->where('outlet_id', $outlet->id)->value('average_cost') ?? 0);

                            $this->costingService->recordIncomingStock(
                                $business,
                                $outlet,
                                $rawModInvItem,
                                $modQtyToRestore,
                                $unitCost,
                                InventoryMovementType::RecipeReturn,
                                $transaction,
                                "Pembatalan Bahan Modifier: {$modOption->name} ({$transaction->transaction_number})",
                                $user
                            );
                        }
                    }
                }
            }
        }
    }

    /**
     * Sinkronisasi transaksi POS offline/online dengan idempotensi ketat dan pemotongan stok FIFO.
     *
     * @param  array<string, mixed>  $data
     */
    public function syncOfflineTransaction(array $data, ?OutletDevice $device = null): Transaction
    {
        return DB::transaction(function () use ($data, $device) {
            $outletId = $device?->outlet_id ?? $data['outlet_id'] ?? null;
            if (! $outletId) {
                throw new InvalidArgumentException('Outlet ID tidak ditemukan pada device atau data transaksi.');
            }

            $outlet = Outlet::with('business')->findOrFail($outletId);

            $transactionNumber = $data['transaction_number']
                ?? $data['receipt_number']
                ?? $data['offline_id']
                ?? $this->generateTransactionNumber($outlet, now());

            // 1. Idempotensi ketat: cegah duplikasi transaksi dan double deduction
            $existing = Transaction::with(['items', 'payments', 'promos'])
                ->where('outlet_id', $outletId)
                ->where(function ($q) use ($transactionNumber, $data) {
                    $q->where('transaction_number', $transactionNumber);
                    if (! empty($data['offline_id']) && Str::isUuid((string) $data['offline_id'])) {
                        $q->orWhere('id', $data['offline_id']);
                    }
                })
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $isValidUuid = static fn (?string $id): bool => ! empty($id) && Str::isUuid($id);

            // 2. Resolusi Shift, Customer, dan User Penanggung Jawab
            $shiftId = ($isValidUuid($data['shift_id'] ?? null) && Shift::where('id', $data['shift_id'])->where('outlet_id', $outletId)->exists())
                ? $data['shift_id']
                : null;

            $customerId = ($isValidUuid($data['customer_id'] ?? null) && Customer::where('id', $data['customer_id'])->where('business_id', $outlet->business_id)->exists())
                ? $data['customer_id']
                : null;

            // Resolusi created_by (kasir)
            $cashierCandidateId = $data['cashier_id'] ?? $data['user_id'] ?? null;
            $user = null;
            if ($isValidUuid($cashierCandidateId)) {
                $user = User::where('id', $cashierCandidateId)->first();
            }

            if (! $user) {
                $user = $outlet->business->users()->where('is_root_user', true)->first()
                    ?? $outlet->users()->first()
                    ?? User::first();
            }

            if (! $user) {
                throw new InvalidArgumentException('Tidak dapat mengidentifikasi user penanggung jawab transaksi.');
            }

            // 3. Pre-load dan validasi ketersediaan stok fisik
            $isOffline = ! empty($data['is_offline']) || ! empty($data['offline_id']);
            $preloadedBalances = $this->checkStockAvailability($data['items'], $outlet, $isOffline);

            // 4. Buat Master Transaksi
            $status = in_array($data['status'] ?? '', ['completed', 'paid', 'hold', 'void', 'draft'], true)
                ? $data['status']
                : 'completed';

            $paymentStatus = in_array($data['payment_status'] ?? '', ['unpaid', 'paid', 'partial'], true)
                ? $data['payment_status']
                : 'paid';

            $total = (float) ($data['total'] ?? 0);
            $totalPaid = $paymentStatus === 'paid' ? $total : (float) ($data['total_paid'] ?? 0);
            $balanceDue = max(0.0, $total - $totalPaid);

            $rawType = $data['type'] ?? 'walk_in';
            $resolvedType = TransactionTypeEnum::tryFrom((string) $rawType) ?? TransactionTypeEnum::WalkIn;

            $txAttributes = [
                'outlet_id' => $outlet->id,
                'shift_id' => $shiftId,
                'customer_id' => $customerId,
                'channel' => SalesChannelEnum::POS,
                'type' => $resolvedType,
                'transaction_number' => $transactionNumber,
                'transaction_date' => now(),
                'subtotal' => $data['subtotal'] ?? 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'discount_type' => $data['discount_type'] ?? null,
                'discount_value' => $data['discount_value'] ?? 0,
                'promo_name' => $data['promo_name'] ?? null,
                'tax_amount' => $data['tax_amount'] ?? 0,
                'shipping_fee' => $data['shipping_fee'] ?? 0,
                'service_charge_amount' => $data['service_charge_amount'] ?? 0,
                'total' => $total,
                'total_paid' => $totalPaid,
                'balance_due' => $balanceDue,
                'payment_status' => $paymentStatus,
                'status' => $status,
                'notes' => $data['notes'] ?? null,
                'created_by' => $user->id,
                'updated_by' => $user->id,
            ];

            $transaction = new Transaction($txAttributes);
            if (! empty($data['offline_id']) && Str::isUuid((string) $data['offline_id'])) {
                $transaction->id = $data['offline_id'];
            }
            $transaction->save();

            // 5. Simpan Item Transaksi
            foreach ($data['items'] as $item) {
                $product = ($isValidUuid($item['product_id'] ?? null))
                    ? Product::where('id', $item['product_id'])->where('business_id', $outlet->business_id)->first()
                    : null;
                $productId = $product?->id;

                $productItemId = ($isValidUuid($item['product_item_id'] ?? null) && ProductItem::where('id', $item['product_item_id'])->exists())
                    ? $item['product_item_id']
                    : null;

                $inventoryItemId = ($isValidUuid($item['inventory_item_id'] ?? null) && InventoryItem::where('id', $item['inventory_item_id'])->where('business_id', $outlet->business_id)->exists())
                    ? $item['inventory_item_id']
                    : null;

                // Jasa / Service items atau produk non-tracked tidak memiliki inventory_item_id
                $isTrackInventory = $product ? (bool) $product->track_inventory : true;
                if ($product && ($product->isService() || ! $isTrackInventory)) {
                    $inventoryItemId = null;
                } elseif (! $inventoryItemId && $product && $product->track_inventory) {
                    // Fallback otomatis inventory_item_id untuk barang fisik jika belum terisi
                    if ($productItemId) {
                        $inventoryItemId = InventoryItem::where('product_item_id', $productItemId)
                            ->where('business_id', $outlet->business_id)
                            ->value('id');
                    }
                    if (! $inventoryItemId) {
                        $inventoryItemId = $product->inventoryItems()
                            ->where('inventory_items.business_id', $outlet->business_id)
                            ->value('inventory_items.id');
                    }
                }

                $itemQty = (float) ($item['qty_deducted'] ?? $item['qty'] ?? 1);

                $transaction->items()->create([
                    'product_id' => $productId,
                    'product_item_id' => $productItemId,
                    'inventory_item_id' => $inventoryItemId,
                    'product_name' => $item['product_name'] ?? 'Item POS',
                    'price' => $item['price'] ?? 0,
                    'qty' => $itemQty,
                    'discount_amount' => $item['discount_amount'] ?? 0,
                    'subtotal' => $item['subtotal'] ?? 0,
                    'promo_name' => $item['promo_name'] ?? null,
                    'notes' => $item['notes'] ?? null,
                    'modifiers' => $item['modifiers'] ?? null,
                ]);
            }

            // 6. Simpan Pembayaran
            if (! empty($data['payments'])) {
                foreach ($data['payments'] as $payment) {
                    $paymentMethodId = ($isValidUuid($payment['payment_method_id'] ?? null) && PaymentMethod::where('id', $payment['payment_method_id'])->where('business_id', $outlet->business_id)->exists())
                        ? $payment['payment_method_id']
                        : null;

                    $transaction->payments()->create([
                        'payment_method_id' => $paymentMethodId,
                        'amount' => $payment['amount'] ?? 0,
                        'change_amount' => $payment['change_amount'] ?? 0,
                        'payment_reference' => $payment['payment_reference'] ?? null,
                        'payment_date' => now(),
                        'created_by' => $user->id,
                    ]);
                }
            }

            // 7. Simpan Promosi Terkait
            if (! empty($data['promos'])) {
                foreach ($data['promos'] as $promo) {
                    $promoId = ($isValidUuid($promo['promo_id'] ?? null) && Promotion::where('id', $promo['promo_id'])->where('business_id', $outlet->business_id)->exists())
                        ? $promo['promo_id']
                        : null;

                    $transaction->promos()->create([
                        'promo_id' => $promoId,
                        'promo_name' => $promo['promo_name'] ?? 'Promo POS',
                        'discount_type' => $promo['discount_type'] ?? 'fixed',
                        'discount_value' => $promo['discount_value'] ?? 0,
                        'discount_amount' => $promo['discount_amount'] ?? 0,
                    ]);
                }
            }

            // Reload relasi items untuk keperluan pemotongan stok
            $transaction->load([
                'items.inventoryItem.productItem.product',
                'items.product.activeRecipe.items.productItem.inventoryItem',
                'items.productItem.recipeVersions.items.productItem.inventoryItem',
                'outlet.business',
            ]);

            // 8. Pemotongan Stok Fisik & Alokasi Layer FIFO jika Transaksi Selesai/Lunas
            $txStatusVal = $transaction->status instanceof TransactionStatus ? $transaction->status->value : (string) $transaction->status;
            if (in_array($txStatusVal, ['completed', 'paid'], true)) {
                $this->deductStockForTransaction($transaction, $user, $preloadedBalances);
            }

            return $transaction;
        });
    }

    /**
     * Void transaksi shift berjalan, memulihkan layer FIFO stok inventori, dan mencatat audit log.
     */
    public function voidTransaction(
        Transaction $transaction,
        User $user,
        string $reason,
        ?string $notes = null
    ): Transaction {
        return DB::transaction(function () use ($transaction, $user, $reason, $notes) {
            $tx = Transaction::with(['items.inventoryItem', 'outlet.business'])
                ->where('id', $transaction->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($tx->status === TransactionStatus::Void || ! empty($tx->voided_at)) {
                throw new InvalidArgumentException('Transaksi sudah dibatalkan sebelumnya.');
            }

            // 1. Reversal stok & layer FIFO
            $this->reverseStockForTransaction($tx, $user);

            // 2. Update status & metadata void
            $tx->status = TransactionStatus::Void;
            $tx->voided_at = now();
            $tx->void_reason = $reason;
            $tx->voided_by = $user->id;
            $tx->updated_by = $user->id;
            if (! empty($notes)) {
                $tx->notes = trim(($tx->notes ? $tx->notes."\n" : '').'Catatan Void: '.$notes);
            }
            $tx->save();

            // 3. Catat jejak audit log terstruktur
            $logger = $this->auditLogger ?? app(ActivityLoggerInterface::class);
            $logger->log(
                module: AuditModuleEnum::POS->value,
                action: 'transaction.voided',
                description: "Void transaksi POS #{$tx->transaction_number}. Alasan: {$reason}",
                subject: $tx,
                causer: $user,
                businessId: $tx->outlet->business_id,
                outletId: $tx->outlet_id,
                properties: [
                    'reason' => $reason,
                    'notes' => $notes,
                    'total' => (float) $tx->total,
                    'voided_at' => $tx->voided_at?->toIso8601String(),
                ]
            );

            return $tx;
        });
    }

    /**
     * Sinkronisasi batch transaksi offline secara atomik dengan in-memory bulk preloading anti-N+1.
     *
     * @param  array<int, array<string, mixed>>  $transactionsData
     * @return array<int, Transaction>
     */
    public function syncOfflineTransactionsBatch(array $transactionsData, ?OutletDevice $device = null): array
    {
        $outletId = $device?->outlet_id ?? $transactionsData[0]['outlet_id'] ?? null;
        if (! $outletId) {
            throw new InvalidArgumentException('Outlet ID tidak ditemukan pada device atau data transaksi batch.');
        }

        $outlet = Outlet::with('business')->findOrFail($outletId);
        $business = $outlet->business;
        $businessId = $business->id;

        return DB::transaction(function () use ($transactionsData, $outlet, $business, $businessId) {
            $isValidUuid = static fn (?string $id): bool => ! empty($id) && Str::isUuid($id);

            // 1. Kumpulkan seluruh ID unik untuk bulk preload
            $allOfflineIds = [];
            $allTxNumbers = [];
            $allShiftIds = [];
            $allCustomerIds = [];
            $allCashierIds = [];
            $allProductIds = [];
            $allProductItemIds = [];
            $allInventoryItemIds = [];
            $allPaymentMethodIds = [];
            $allPromoIds = [];

            foreach ($transactionsData as $txData) {
                if (! empty($txData['offline_id']) && $isValidUuid((string) $txData['offline_id'])) {
                    $allOfflineIds[] = (string) $txData['offline_id'];
                }
                $txNum = $txData['transaction_number'] ?? $txData['receipt_number'] ?? null;
                if (! empty($txNum)) {
                    $allTxNumbers[] = $txNum;
                }
                if (! empty($txData['shift_id']) && $isValidUuid((string) $txData['shift_id'])) {
                    $allShiftIds[] = (string) $txData['shift_id'];
                }
                if (! empty($txData['customer_id']) && $isValidUuid((string) $txData['customer_id'])) {
                    $allCustomerIds[] = (string) $txData['customer_id'];
                }
                $cashierCandidate = $txData['cashier_id'] ?? $txData['user_id'] ?? null;
                if (! empty($cashierCandidate) && $isValidUuid((string) $cashierCandidate)) {
                    $allCashierIds[] = (string) $cashierCandidate;
                }

                foreach ($txData['items'] ?? [] as $item) {
                    if (! empty($item['product_id']) && $isValidUuid((string) $item['product_id'])) {
                        $allProductIds[] = (string) $item['product_id'];
                    }
                    if (! empty($item['product_item_id']) && $isValidUuid((string) $item['product_item_id'])) {
                        $allProductItemIds[] = (string) $item['product_item_id'];
                    }
                    if (! empty($item['inventory_item_id']) && $isValidUuid((string) $item['inventory_item_id'])) {
                        $allInventoryItemIds[] = (string) $item['inventory_item_id'];
                    }
                }

                foreach ($txData['payments'] ?? [] as $payment) {
                    if (! empty($payment['payment_method_id']) && $isValidUuid((string) $payment['payment_method_id'])) {
                        $allPaymentMethodIds[] = (string) $payment['payment_method_id'];
                    }
                }

                foreach ($txData['promos'] ?? [] as $promo) {
                    if (! empty($promo['promo_id']) && $isValidUuid((string) $promo['promo_id'])) {
                        $allPromoIds[] = (string) $promo['promo_id'];
                    }
                }
            }

            // 2. In-Memory Bulk Preloading (Set-Based 1-Query Lookups)
            $existingTransactionsMap = collect();
            if (! empty($allOfflineIds) || ! empty($allTxNumbers)) {
                $existingQuery = Transaction::with(['items', 'payments', 'promos'])
                    ->where('outlet_id', $outlet->id)
                    ->where(function ($q) use ($allOfflineIds, $allTxNumbers) {
                        if (! empty($allOfflineIds)) {
                            $q->whereIn('id', array_unique($allOfflineIds));
                        }
                        if (! empty($allTxNumbers)) {
                            $q->orWhereIn('transaction_number', array_unique($allTxNumbers));
                        }
                    })
                    ->lockForUpdate()
                    ->get();

                $existingTransactionsMap = collect();
                foreach ($existingQuery as $tx) {
                    $existingTransactionsMap->put((string) $tx->id, $tx);
                    if (! empty($tx->transaction_number)) {
                        $existingTransactionsMap->put((string) $tx->transaction_number, $tx);
                    }
                }
            }

            $shiftsMap = ! empty($allShiftIds)
                ? Shift::whereIn('id', array_unique($allShiftIds))->where('outlet_id', $outlet->id)->get()->keyBy('id')
                : collect();

            $customersMap = ! empty($allCustomerIds)
                ? Customer::whereIn('id', array_unique($allCustomerIds))->where('business_id', $businessId)->get()->keyBy('id')
                : collect();

            $usersMap = ! empty($allCashierIds)
                ? User::whereIn('id', array_unique($allCashierIds))->get()->keyBy('id')
                : collect();

            $defaultUser = $business->users()->where('is_root_user', true)->first()
                ?? $outlet->users()->first()
                ?? User::first();

            $productsMap = ! empty($allProductIds)
                ? Product::with(['activeRecipe.items.productItem.inventoryItem'])->whereIn('id', array_unique($allProductIds))->where('business_id', $businessId)->get()->keyBy('id')
                : collect();

            $productItemsMap = ! empty($allProductItemIds)
                ? ProductItem::whereIn('id', array_unique($allProductItemIds))->get()->keyBy('id')
                : collect();

            // Extract recipe raw material inventory items into preload list
            foreach ($productsMap as $prod) {
                if ($prod->activeRecipe?->items) {
                    foreach ($prod->activeRecipe->items as $rItem) {
                        $rInvId = $rItem->productItem?->inventoryItem?->id ?? $rItem->product_item_id;
                        if (! empty($rInvId)) {
                            $allInventoryItemIds[] = (string) $rInvId;
                        }
                    }
                }
            }

            $inventoryItemsMap = ! empty($allInventoryItemIds)
                ? InventoryItem::whereIn('id', array_unique($allInventoryItemIds))->where('business_id', $businessId)->get()->keyBy('id')
                : collect();

            $paymentMethodsMap = ! empty($allPaymentMethodIds)
                ? PaymentMethod::whereIn('id', array_unique($allPaymentMethodIds))->where('business_id', $businessId)->get()->keyBy('id')
                : collect();

            $promosMap = ! empty($allPromoIds)
                ? Promotion::whereIn('id', array_unique($allPromoIds))->where('business_id', $businessId)->get()->keyBy('id')
                : collect();

            // Bulk lock & fetch inventory balances across the entire batch
            $allResolvedInvItemIds = array_values(array_unique(array_filter($allInventoryItemIds)));
            $balancesMap = ! empty($allResolvedInvItemIds)
                ? InventoryBalance::where('outlet_id', $outlet->id)
                    ->whereIn('inventory_item_id', $allResolvedInvItemIds)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('inventory_item_id')
                : collect();

            // 3. Iterasi setiap transaksi dalam batch dengan O(1) memory lookup
            $syncedTransactions = [];

            foreach ($transactionsData as $data) {
                $offlineId = $data['offline_id'] ?? null;
                $transactionNumber = $data['transaction_number']
                    ?? $data['receipt_number']
                    ?? $offlineId
                    ?? $this->generateTransactionNumber($outlet, now());

                // Idempotensi dari preloaded map
                if (! empty($offlineId) && $existingTransactionsMap->has($offlineId)) {
                    $syncedTransactions[] = $existingTransactionsMap->get($offlineId);

                    continue;
                }
                if ($existingTransactionsMap->has($transactionNumber)) {
                    $syncedTransactions[] = $existingTransactionsMap->get($transactionNumber);

                    continue;
                }

                $shiftId = (! empty($data['shift_id']) && $shiftsMap->has($data['shift_id']))
                    ? $data['shift_id']
                    : null;

                $customerId = (! empty($data['customer_id']) && $customersMap->has($data['customer_id']))
                    ? $data['customer_id']
                    : null;

                $cashierCandidateId = $data['cashier_id'] ?? $data['user_id'] ?? null;
                $user = (! empty($cashierCandidateId) && $usersMap->has($cashierCandidateId))
                    ? $usersMap->get($cashierCandidateId)
                    : $defaultUser;

                if (! $user) {
                    throw new InvalidArgumentException('Tidak dapat mengidentifikasi user penanggung jawab transaksi.');
                }

                $status = in_array($data['status'] ?? '', ['completed', 'paid', 'hold', 'void', 'draft'], true)
                    ? $data['status']
                    : 'completed';

                $paymentStatus = in_array($data['payment_status'] ?? '', ['unpaid', 'paid', 'partial'], true)
                    ? $data['payment_status']
                    : 'paid';

                $total = (float) ($data['total'] ?? 0);
                $totalPaid = $paymentStatus === 'paid' ? $total : (float) ($data['total_paid'] ?? 0);
                $balanceDue = max(0.0, $total - $totalPaid);

                $rawType = $data['type'] ?? 'walk_in';
                $resolvedType = TransactionTypeEnum::tryFrom((string) $rawType) ?? TransactionTypeEnum::WalkIn;

                $tx = new Transaction([
                    'outlet_id' => $outlet->id,
                    'shift_id' => $shiftId,
                    'customer_id' => $customerId,
                    'channel' => SalesChannelEnum::POS,
                    'type' => $resolvedType,
                    'transaction_number' => $transactionNumber,
                    'transaction_date' => now(),
                    'subtotal' => $data['subtotal'] ?? 0,
                    'discount_amount' => $data['discount_amount'] ?? 0,
                    'discount_type' => $data['discount_type'] ?? null,
                    'discount_value' => $data['discount_value'] ?? 0,
                    'promo_name' => $data['promo_name'] ?? null,
                    'tax_amount' => $data['tax_amount'] ?? 0,
                    'shipping_fee' => $data['shipping_fee'] ?? 0,
                    'service_charge_amount' => $data['service_charge_amount'] ?? 0,
                    'total' => $total,
                    'total_paid' => $totalPaid,
                    'balance_due' => $balanceDue,
                    'payment_status' => $paymentStatus,
                    'status' => $status,
                    'notes' => $data['notes'] ?? null,
                    'created_by' => $user->id,
                    'updated_by' => $user->id,
                ]);

                if (! empty($offlineId) && $isValidUuid((string) $offlineId)) {
                    $tx->id = $offlineId;
                }
                $tx->save();

                // Items
                foreach ($data['items'] ?? [] as $item) {
                    $pId = (! empty($item['product_id']) && $productsMap->has($item['product_id']))
                        ? $item['product_id']
                        : null;
                    $product = $pId ? $productsMap->get($pId) : null;

                    $piId = (! empty($item['product_item_id']) && $productItemsMap->has($item['product_item_id']))
                        ? $item['product_item_id']
                        : null;

                    $invId = (! empty($item['inventory_item_id']) && $inventoryItemsMap->has($item['inventory_item_id']))
                        ? $item['inventory_item_id']
                        : null;

                    $isTrackInventory = $product ? (bool) $product->track_inventory : true;
                    if ($product && ($product->isService() || ! $isTrackInventory)) {
                        $invId = null;
                    }

                    $itemQty = (float) ($item['qty_deducted'] ?? $item['qty'] ?? 1);

                    $tx->items()->create([
                        'product_id' => $pId,
                        'product_item_id' => $piId,
                        'inventory_item_id' => $invId,
                        'product_name' => $item['product_name'] ?? 'Item POS',
                        'price' => $item['price'] ?? 0,
                        'qty' => $itemQty,
                        'discount_amount' => $item['discount_amount'] ?? 0,
                        'subtotal' => $item['subtotal'] ?? 0,
                        'promo_name' => $item['promo_name'] ?? null,
                        'notes' => $item['notes'] ?? null,
                        'modifiers' => $item['modifiers'] ?? null,
                    ]);
                }

                // Payments
                foreach ($data['payments'] ?? [] as $payment) {
                    $pmId = (! empty($payment['payment_method_id']) && $paymentMethodsMap->has($payment['payment_method_id']))
                        ? $payment['payment_method_id']
                        : null;

                    $tx->payments()->create([
                        'payment_method_id' => $pmId,
                        'amount' => $payment['amount'] ?? 0,
                        'change_amount' => $payment['change_amount'] ?? 0,
                        'payment_reference' => $payment['payment_reference'] ?? null,
                        'payment_date' => now(),
                        'created_by' => $user->id,
                    ]);
                }

                // Promos
                foreach ($data['promos'] ?? [] as $promo) {
                    $prId = (! empty($promo['promo_id']) && $promosMap->has($promo['promo_id']))
                        ? $promo['promo_id']
                        : null;

                    $tx->promos()->create([
                        'promo_id' => $prId,
                        'promo_name' => $promo['promo_name'] ?? 'Promo POS',
                        'discount_type' => $promo['discount_type'] ?? 'fixed',
                        'discount_value' => $promo['discount_value'] ?? 0,
                        'discount_amount' => $promo['discount_amount'] ?? 0,
                    ]);
                }

                // Potong stok jika transaksi completed/paid
                $batchTxStatus = $tx->status instanceof TransactionStatus ? $tx->status->value : (string) $tx->status;
                if (in_array($batchTxStatus, ['completed', 'paid'], true)) {
                    $tx->load(['items.inventoryItem.productItem.product', 'items.product', 'outlet.business']);
                    $this->deductStockForTransaction($tx, $user, $balancesMap);
                }

                $syncedTransactions[] = $tx;
                if (! empty($tx->id)) {
                    $existingTransactionsMap->put($tx->id, $tx);
                }
                if (! empty($tx->transaction_number)) {
                    $existingTransactionsMap->put($tx->transaction_number, $tx);
                }
            }

            return $syncedTransactions;
        });
    }
}
