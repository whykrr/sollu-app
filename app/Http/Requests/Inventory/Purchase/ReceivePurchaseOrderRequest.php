<?php

namespace App\Http\Requests\Inventory\Purchase;

use Illuminate\Foundation\Http\FormRequest;

class ReceivePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.id'             => ['required', 'uuid', 'exists:purchase_order_items,id'],
            'items.*.qty_received'   => ['required', 'numeric', 'min:0'],
        ];
    }
}
