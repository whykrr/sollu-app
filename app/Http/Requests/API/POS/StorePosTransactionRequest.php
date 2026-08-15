<?php

namespace App\Http\Requests\API\POS;

use Illuminate\Foundation\Http\FormRequest;

class StorePosTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'offline_id' => ['nullable', 'string'],
            'receipt_number' => ['nullable', 'string'],
            'transaction_number' => ['nullable', 'string'],
            'shift_id' => ['nullable', 'string', 'uuid'],
            'customer_id' => ['nullable', 'string', 'uuid'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['required', 'numeric', 'min:0'],
            'discount_type' => ['nullable', 'string'],
            'discount_value' => ['nullable', 'numeric', 'min:0'],
            'promo_name' => ['nullable', 'string'],
            'tax_amount' => ['required', 'numeric', 'min:0'],
            'service_charge_amount' => ['required', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'payment_status' => ['required', 'string', 'in:unpaid,paid,partial'],
            'status' => ['required', 'string', 'in:hold,completed,void,paid'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['nullable', 'string', 'uuid'],
            'items.*.inventory_item_id' => ['nullable', 'string', 'uuid'],
            'items.*.variant_group_option_id' => ['nullable', 'string', 'uuid'],
            'items.*.product_name' => ['required', 'string'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.qty' => ['required', 'numeric', 'min:1'],
            'items.*.discount_amount' => ['required', 'numeric', 'min:0'],
            'items.*.promo_name' => ['nullable', 'string'],
            'items.*.subtotal' => ['required', 'numeric', 'min:0'],
            'items.*.modifiers' => ['nullable', 'array'],
            'items.*.modifiers.*.modifier_option_id' => ['nullable', 'string', 'uuid'],
            'items.*.modifiers.*.modifier_name' => ['required', 'string'],
            'items.*.modifiers.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.modifiers.*.qty' => ['required', 'numeric', 'min:1'],
            'payments' => ['nullable', 'array'],
            'payments.*.payment_method_id' => ['nullable', 'string', 'uuid'],
            'payments.*.amount' => ['required', 'numeric', 'min:0'],
            'payments.*.change_amount' => ['required', 'numeric', 'min:0'],
            'payments.*.payment_reference' => ['nullable', 'string'],
            'promos' => ['nullable', 'array'],
            'promos.*.promo_id' => ['nullable', 'string', 'uuid'],
            'promos.*.promo_name' => ['required', 'string'],
            'promos.*.promo_code' => ['nullable', 'string'],
            'promos.*.discount_type' => ['nullable', 'string'],
            'promos.*.discount_value' => ['nullable', 'numeric'],
            'promos.*.discount_amount' => ['nullable', 'numeric'],
        ];
    }
}
