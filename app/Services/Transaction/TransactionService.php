<?php

namespace App\Services\Transaction;

use App\Models\Sales\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TransactionService
{
    protected PriceCalculationService $priceCalculationService;
    protected InventoryDeductionService $inventoryDeductionService;

    public function __construct(
        PriceCalculationService $priceCalculationService,
        InventoryDeductionService $inventoryDeductionService
    ) {
        $this->priceCalculationService = $priceCalculationService;
        $this->inventoryDeductionService = $inventoryDeductionService;
    }

    public function createB2bInvoice(array $data, User $user): Transaction
    {
        return DB::transaction(function () use ($data, $user) {
            $transaction = Transaction::create([
                'outlet_id' => $data['outlet_id'] ?? null,
                'customer_id' => $data['customer_id'] ?? null,
                'channel' => 'invoice',
                'subtotal' => $data['subtotal'],
                'discount_amount' => $data['discount_amount'] ?? 0,
                'tax_amount' => $data['tax_amount'] ?? 0,
                'service_charge_amount' => $data['service_charge_amount'] ?? 0,
                'total' => $data['total'],
                'payment_status' => 'unpaid',
                'status' => 'completed', // B2B invoices are considered completed orders, but unpaid.
                'is_offline' => false,
                'due_date' => $data['due_date'] ?? null,
                'receipt_number' => $this->generateInvoiceNumber(),
            ]);

            if (!empty($data['items'])) {
                foreach ($data['items'] as $item) {
                    $transaction->items()->create([
                        'product_id' => $item['product_id'],
                        'product_name' => $item['product_name'],
                        'price' => $item['price'],
                        'qty' => $item['qty'],
                        'discount_amount' => $item['discount_amount'] ?? 0,
                        'subtotal' => $item['subtotal'],
                    ]);
                }
            }

            // Deduct stock if invoice is completed
            $this->inventoryDeductionService->deductFromTransaction($transaction);

            return $transaction;
        });
    }

    protected function generateInvoiceNumber(): string
    {
        $prefix = 'INV/' . date('Y/m/');
        $last = Transaction::where('receipt_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if (!$last) {
            return $prefix . '0001';
        }

        $lastNumber = intval(substr($last->receipt_number, -4));
        return $prefix . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    public function syncOfflineTransaction(array $data, ?\App\Models\OutletDevice $device = null): Transaction
    {
        return DB::transaction(function () use ($data, $device) {
            // Check idempotency
            $offlineId = $data['offline_id'] ?? null;
            if ($offlineId) {
                $existing = Transaction::where('offline_id', $offlineId)->first();
                if ($existing) {
                    return $existing;
                }
            }

            // Create transaction from offline data
            $transaction = Transaction::create([
                'outlet_id' => $device->outlet_id,
                'customer_id' => $data['customer_id'] ?? null,
                'channel' => 'pos',
                'subtotal' => $data['subtotal'],
                'discount_amount' => $data['discount_amount'],
                'tax_amount' => $data['tax_amount'],
                'service_charge_amount' => $data['service_charge_amount'],
                'total' => $data['total'],
                'payment_status' => $data['payment_status'],
                'status' => $data['status'],
                'is_offline' => true,
                'offline_id' => $offlineId,
                'receipt_number' => $data['receipt_number'] ?? null,
            ]);

            foreach ($data['items'] as $item) {
                $txItem = $transaction->items()->create([
                    'product_id' => $item['product_id'],
                    'variant_group_option_id' => $item['variant_group_option_id'] ?? null,
                    'product_name' => $item['product_name'],
                    'price' => $item['price'],
                    'qty' => $item['qty'],
                    'discount_amount' => $item['discount_amount'],
                    'subtotal' => $item['subtotal'],
                ]);

                if (!empty($item['modifiers'])) {
                    foreach ($item['modifiers'] as $mod) {
                        $txItem->modifiers()->create([
                            'modifier_option_id' => $mod['modifier_option_id'],
                            'modifier_name' => $mod['modifier_name'],
                            'price' => $mod['price'],
                            'qty' => $mod['qty'],
                        ]);
                    }
                }
            }

            if (!empty($data['payments'])) {
                foreach ($data['payments'] as $payment) {
                    $transaction->payments()->create([
                        'payment_method_id' => $payment['payment_method_id'],
                        'amount' => $payment['amount'],
                        'change_amount' => $payment['change_amount'],
                        'payment_reference' => $payment['payment_reference'] ?? null,
                    ]);
                }
            }

            // Deduct stock if completed
            if ($transaction->status === 'completed') {
                $this->inventoryDeductionService->deductFromTransaction($transaction);
            }

            return $transaction;
        });
    }
}
