<?php

namespace App\Http\Requests\Inventory\Purchase;

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
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'uuid', 'exists:purchase_order_items,id'],
            'items.*.qty_received' => ['required', 'numeric', 'min:0'],
            'items.*.conversion_factor' => ['required', 'numeric', 'min:0.0001'],
        ];
    }
}
