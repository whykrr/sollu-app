<?php

namespace App\Http\Requests\Inventory\Adjustment;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'outlet_id'         => ['required', 'uuid', 'exists:outlets,id'],
            'inventory_item_id' => ['required', 'uuid', 'exists:inventory_items,id'],
            'movement_type'     => ['required', 'string'],
            'qty_change'        => ['required', 'numeric', 'not_in:0'],
            'description'       => ['required', 'string'],
        ];
    }
}
