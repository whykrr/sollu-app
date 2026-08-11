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
            'offline_id' => ['required', 'string', 'uuid'],
            'receipt_number' => ['nullable', 'string'],
            'customer_id' => ['nullable', 'string', 'uuid'],
            'subtotal' => ['required', 'numeric', 'min:0'],
            'discount_amount' => ['required', 'numeric', 'min:0'],
            'tax_amount' => ['required', 'numeric', 'min:0'],
            'service_charge_amount' => ['required', 'numeric', 'min:0'],
            'total' => ['required', 'numeric', 'min:0'],
            'payment_status' => ['required', 'string', 'in:unpaid,paid,partial'],
            'status' => ['required', 'string', 'in:hold,completed,void'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'string', 'uuid'],
            'items.*.variant_group_option_id' => ['nullable', 'string', 'uuid'],
            'items.*.product_name' => ['required', 'string'],
            'items.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.qty' => ['required', 'numeric', 'min:1'],
            'items.*.discount_amount' => ['required', 'numeric', 'min:0'],
            'items.*.subtotal' => ['required', 'numeric', 'min:0'],
            'items.*.modifiers' => ['nullable', 'array'],
            'items.*.modifiers.*.modifier_option_id' => ['required', 'string', 'uuid'],
            'items.*.modifiers.*.modifier_name' => ['required', 'string'],
            'items.*.modifiers.*.price' => ['required', 'numeric', 'min:0'],
            'items.*.modifiers.*.qty' => ['required', 'numeric', 'min:1'],
            'payments' => ['nullable', 'array'],
            'payments.*.payment_method_id' => ['required', 'string', 'uuid'],
            'payments.*.amount' => ['required', 'numeric', 'min:0'],
            'payments.*.change_amount' => ['required', 'numeric', 'min:0'],
            'payments.*.payment_reference' => ['nullable', 'string'],
        ];
    }
}
