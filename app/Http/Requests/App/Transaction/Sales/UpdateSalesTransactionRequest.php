<?php

declare(strict_types=1);

namespace App\Http\Requests\App\Transaction\Sales;

use App\DTOs\Transaction\CreateB2bTransactionDTO;
use App\DTOs\Transaction\CreateTransactionItemDTO;
use App\Enums\PaymentTermEnum;
use App\Enums\PermissionEnum;
use App\Enums\SalesChannelEnum;
use App\Http\Requests\BaseInertiaFormRequest;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateSalesTransactionRequest extends BaseInertiaFormRequest
{
    public function authorize(): bool
    {
        $user = Auth::user();

        return ($user?->can(PermissionEnum::TRANSACTION_UPDATE->value) || $user?->can(PermissionEnum::TRANSACTION_CREATE->value)) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'outlet_id' => ['required', 'uuid', 'exists:outlets,id'],
            'customer_id' => ['nullable', 'uuid', 'exists:customers,id'],
            'channel' => ['required', Rule::enum(SalesChannelEnum::class)],
            'transaction_date' => ['required', 'date'],
            'payment_term' => ['required', 'in:cash,credit'],
            'due_date' => ['nullable', 'required_if:payment_term,credit', 'date', 'after_or_equal:transaction_date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'discount_type' => ['nullable', 'string', 'in:manual,promo'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'promo_code' => ['nullable', 'string', 'max:50'],
            'shipping_fee' => ['nullable', 'numeric', 'min:0'],
            'service_charge_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_term_code' => ['nullable', 'string'],
            'issue_now' => ['nullable', 'boolean'],
            'payment' => ['nullable', 'array'],
            'payment.payment_method_id' => ['nullable', 'required_with:payment.amount', 'uuid', 'exists:payment_methods,id'],
            'payment.amount' => ['nullable', 'numeric', 'min:0'],
            'payment.change_amount' => ['nullable', 'numeric', 'min:0'],
            'payment.payment_reference' => ['nullable', 'string', 'max:255'],
            'payment.notes' => ['nullable', 'string', 'max:1000'],

            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid', 'exists:products,id'],
            'items.*.product_item_id' => ['nullable', 'uuid', 'exists:product_items,id'],
            'items.*.inventory_item_id' => ['nullable', 'uuid', 'exists:inventory_items,id'],
            'items.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            if ($this->boolean('issue_now') && $this->input('payment_term') === 'cash') {
                $amount = (float) $this->input('payment.amount', 0);
                $paymentMethodId = $this->input('payment.payment_method_id');

                if (empty($paymentMethodId)) {
                    $validator->errors()->add('payment.payment_method_id', 'Metode pembayaran wajib dipilih untuk penerbitan faktur tunai.');
                }
                if ($amount <= 0) {
                    $validator->errors()->add('payment.amount', 'Nominal pembayaran wajib diisi lunas untuk penerbitan faktur tunai agar tidak menghasilkan piutang.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'outlet_id.required' => 'Outlet penjualan wajib dipilih.',
            'channel.required' => 'Saluran penjualan wajib dipilih.',
            'transaction_date.required' => 'Tanggal transaksi wajib diisi dengan format tanggal yang valid.',
            'payment_term.in' => 'Termin pembayaran harus berupa Tunai atau Kredit.',
            'due_date.required_if' => 'Tanggal jatuh tempo wajib diisi untuk termin kredit dan tidak boleh mendahului tanggal transaksi.',
            'items.required' => 'Faktur penjualan wajib memuat minimal satu item produk/jasa.',
        ];
    }

    public function toDTO(): CreateB2bTransactionDTO
    {
        $items = [];
        foreach ($this->validated('items', []) as $item) {
            $items[] = new CreateTransactionItemDTO(
                productId: $item['product_id'],
                qty: (float) $item['qty'],
                price: (float) $item['price'],
                productItemId: $item['product_item_id'] ?? null,
                inventoryItemId: $item['inventory_item_id'] ?? null,
                discountAmount: (float) ($item['discount_amount'] ?? 0.0),
                notes: $item['notes'] ?? null,
            );
        }

        return new CreateB2bTransactionDTO(
            outletId: $this->validated('outlet_id'),
            channel: SalesChannelEnum::from($this->validated('channel')),
            transactionDate: Carbon::parse($this->validated('transaction_date')),
            paymentTerm: PaymentTermEnum::from($this->validated('payment_term')),
            items: $items,
            customerId: $this->validated('customer_id'),
            dueDate: $this->validated('due_date') ? Carbon::parse($this->validated('due_date')) : null,
            notes: $this->validated('notes'),
            discountType: $this->validated('discount_type'),
            discountValue: (float) $this->validated('discount_value', 0.0),
            taxAmount: (float) $this->validated('tax_amount', 0.0),
            shippingFee: (float) $this->validated('shipping_fee', 0.0),
            serviceChargeAmount: (float) $this->validated('service_charge_amount', 0.0),
            paymentTermCode: $this->validated('payment_term_code', 'custom'),
            promoCode: $this->validated('promo_code') ? (string) $this->validated('promo_code') : null
        );
    }
}
