<?php

namespace App\Http\Requests\App\Inventory\Purchase;

use App\Enums\PermissionEnum;
use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Support\Facades\Auth;

class ReceivePurchaseOrderRequest extends BaseInertiaFormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->can(PermissionEnum::PURCHASE_ORDER_RECEIVE->value);
    }

    public function rules(): array
    {
        return [
            'delivery_order_number' => ['nullable', 'string', 'max:100'],
            'received_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['nullable', 'uuid', 'exists:purchase_order_items,id'],
            'items.*.purchase_order_item_id' => ['nullable', 'uuid', 'exists:purchase_order_items,id'],
            'items.*.qty_received' => ['nullable', 'numeric', 'min:0'],
            'items.*.received_purchase_qty' => ['nullable', 'numeric', 'min:0'],
            'items.*.conversion_factor' => ['nullable', 'numeric', 'min:0.0001'],
            'items.*.uom_id' => ['nullable', 'uuid', 'exists:uoms,id'],
        ];
    }
}
