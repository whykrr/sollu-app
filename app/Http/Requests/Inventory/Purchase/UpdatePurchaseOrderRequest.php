<?php

namespace App\Http\Requests\Inventory\Purchase;

use App\Enum\PermissionEnum;
use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Support\Facades\Auth;

class UpdatePurchaseOrderRequest extends BaseInertiaFormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->can(PermissionEnum::PURCHASE_ORDER_UPDATE->value);
    }

    public function rules(): array
    {
        return [
            'supplier_id'               => ['nullable', 'uuid', 'exists:suppliers,id'],
            'outlet_id'                 => ['required', 'uuid', 'exists:outlets,id'],
            'order_date'                => ['required', 'date'],
            'expected_date'             => ['nullable', 'date', 'after_or_equal:order_date'],
            'notes'                     => ['nullable', 'string'],
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'uuid', 'exists:inventory_items,id'],
            'items.*.uom_id'            => ['required', 'uuid', 'exists:uoms,id'],
            'items.*.qty_ordered'       => ['required', 'numeric', 'min:0.01'],
            'items.*.purchase_price'    => ['required', 'numeric', 'min:0'],
        ];
    }
}
