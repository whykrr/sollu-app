<?php

namespace App\Http\Requests\App\Transaction\Sales;

use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Gate;

class StoreSalesTransactionRequest extends BaseInertiaFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $canCreate = Gate::allows('transaction.create');

        // Cek jika ada diskon manual
        $manualDiscount = floatval($this->input('manual_discount_amount', 0));
        if ($manualDiscount > 0 && ! Gate::allows('transaction.discount')) {
            return false;
        }

        return $canCreate;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'customer_id' => $this->customer_id ?: null,
            'promo_id' => $this->promo_id ?: null,
            'payment_method_id' => $this->payment_method_id ?: null,
            'due_date' => $this->due_date ?: null,
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'outlet_id' => ['required', 'uuid'],
            'customer_id' => ['nullable', 'uuid'],
            'channel' => ['required', 'string'],
            'transaction_date' => ['required', 'date'],
            'payment_term' => ['required', 'in:cash,credit'],
            'due_date' => ['nullable', 'required_if:payment_term,credit', 'date'],
            'payment_method_id' => [
                'nullable',
                'uuid',
                'exists:payment_methods,id',
                function ($attribute, $value, $fail) {
                    $action = $this->input('action');
                    $term = $this->input('payment_term');
                    $paidAmount = floatval($this->input('paid_amount', 0));

                    if ($action === 'issue' && ($term === 'cash' || $paidAmount > 0) && empty($value)) {
                        $fail('Metode pembayaran wajib dipilih untuk pembayaran tunai atau uang muka (DP).');
                    }
                },
            ],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'payment_notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'uuid'],
            'items.*.inventory_item_id' => ['nullable', 'uuid', 'exists:inventory_items,id'],
            'items.*.variant_group_option_id' => ['nullable', 'uuid'],
            'items.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'items.*.promo_name' => ['nullable', 'string'],
            'subtotal' => ['nullable', 'numeric', 'min:0'],
            'manual_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'promo_id' => ['nullable', 'uuid'],
            'promo_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'shipping_fee' => ['nullable', 'numeric', 'min:0'],
            'tax_amount' => ['nullable', 'numeric', 'min:0'],
            'service_charge_amount' => ['nullable', 'numeric', 'min:0'],
            'total' => ['nullable', 'numeric', 'min:0'],
            'items.*.subtotal' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string'],
            'terms_and_conditions' => ['nullable', 'string'],
            'action' => ['required', 'in:draft,issue'],
        ];
    }
}
