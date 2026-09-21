<?php

namespace App\Http\Requests\App\Inventory\Purchase;

use App\Enums\PermissionEnum;
use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Support\Facades\Auth;

class StorePurchaseReturnRequest extends BaseInertiaFormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->can(PermissionEnum::PURCHASE_ORDER_RETURN->value) ?? false;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('items') && is_array($this->input('items'))) {
            $items = array_map(function ($item) {
                if (isset($item['qty_returned']) && ! isset($item['return_purchase_qty'])) {
                    $item['return_purchase_qty'] = $item['qty_returned'];
                }

                return $item;
            }, $this->input('items'));

            $this->merge(['items' => $items]);
        }
    }

    public function rules(): array
    {
        return [
            'outlet_id' => ['required', 'uuid', 'exists:outlets,id'],
            'supplier_id' => ['nullable', 'uuid', 'exists:suppliers,id'],
            'purchase_order_id' => ['nullable', 'uuid', 'exists:purchase_orders,id'],
            'return_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:500'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'uuid', 'exists:inventory_items,id'],
            'items.*.goods_receipt_item_id' => ['nullable', 'uuid', 'exists:goods_receipt_items,id'],
            'items.*.uom_id' => ['nullable', 'uuid', 'exists:uoms,id'],
            'items.*.return_purchase_qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.conversion_factor' => ['nullable', 'numeric', 'min:0.0001'],
            'items.*.unit_cost' => ['required', 'numeric', 'min:0'],
        ];
    }
}
