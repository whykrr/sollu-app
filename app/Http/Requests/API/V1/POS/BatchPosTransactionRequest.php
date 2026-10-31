<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\POS;

use Illuminate\Foundation\Http\FormRequest;

class BatchPosTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'transactions' => ['required', 'array', 'min:1'],
            'transactions.*.offline_id' => ['nullable', 'string'],
            'transactions.*.is_offline' => ['nullable', 'boolean'],
            'transactions.*.receipt_number' => ['nullable', 'string'],
            'transactions.*.transaction_number' => ['nullable', 'string'],
            'transactions.*.shift_id' => ['nullable', 'string'],
            'transactions.*.customer_id' => ['nullable', 'string'],
            'transactions.*.cashier_id' => ['nullable', 'string'],
            'transactions.*.user_id' => ['nullable', 'string'],
            'transactions.*.supervisor_id' => ['nullable', 'string'],
            'transactions.*.offline_metadata' => ['nullable', 'array'],
            'transactions.*.subtotal' => ['required', 'numeric', 'min:0'],
            'transactions.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'transactions.*.discount_type' => ['nullable', 'string'],
            'transactions.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'transactions.*.promo_name' => ['nullable', 'string'],
            'transactions.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
            'transactions.*.service_charge_amount' => ['nullable', 'numeric', 'min:0'],
            'transactions.*.shipping_fee' => ['nullable', 'numeric', 'min:0'],
            'transactions.*.total' => ['required', 'numeric', 'min:0'],
            'transactions.*.payment_status' => ['nullable', 'string', 'in:unpaid,paid,partial'],
            'transactions.*.status' => ['nullable', 'string', 'in:hold,completed,void,paid'],
            'transactions.*.notes' => ['nullable', 'string'],
            'transactions.*.items' => ['required', 'array', 'min:1'],
            'transactions.*.items.*.product_id' => ['nullable', 'string'],
            'transactions.*.items.*.product_item_id' => ['nullable', 'string'],
            'transactions.*.items.*.inventory_item_id' => ['nullable', 'string'],
            'transactions.*.items.*.variant_group_option_id' => ['nullable', 'string'],
            'transactions.*.items.*.product_name' => ['required', 'string'],
            'transactions.*.items.*.price' => ['required', 'numeric', 'min:0'],
            'transactions.*.items.*.qty' => ['nullable', 'numeric', 'min:0'],
            'transactions.*.items.*.qty_deducted' => ['nullable', 'numeric', 'min:0'],
            'transactions.*.items.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
            'transactions.*.items.*.discount_type' => ['nullable', 'string', 'in:percentage,fixed'],
            'transactions.*.items.*.discount_value' => ['nullable', 'numeric', 'min:0'],
            'transactions.*.items.*.track_inventory' => ['nullable', 'boolean'],
            'transactions.*.items.*.promo_name' => ['nullable', 'string'],
            'transactions.*.items.*.subtotal' => ['required', 'numeric', 'min:0'],
            'transactions.*.items.*.modifiers' => ['nullable', 'array'],
            'transactions.*.items.*.modifiers.*.modifier_option_id' => ['nullable', 'string'],
            'transactions.*.items.*.modifiers.*.modifier_name' => ['required', 'string'],
            'transactions.*.items.*.modifiers.*.price' => ['required', 'numeric', 'min:0'],
            'transactions.*.items.*.modifiers.*.qty' => ['required', 'numeric', 'min:1'],
            'transactions.*.payments' => ['nullable', 'array'],
            'transactions.*.payments.*.payment_method_id' => ['nullable', 'string'],
            'transactions.*.payments.*.amount' => ['required', 'numeric', 'min:0'],
            'transactions.*.payments.*.change_amount' => ['nullable', 'numeric', 'min:0'],
            'transactions.*.payments.*.payment_reference' => ['nullable', 'string'],
            'transactions.*.promos' => ['nullable', 'array'],
            'transactions.*.promos.*.promo_id' => ['nullable', 'string'],
            'transactions.*.promos.*.promo_name' => ['required', 'string'],
            'transactions.*.promos.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
