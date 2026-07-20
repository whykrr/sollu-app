<?php

namespace App\Http\Requests\Inventory\StockTaking;

use Illuminate\Foundation\Http\FormRequest;

class StoreStockOpnameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'outlet_id'                 => ['required', 'uuid', 'exists:outlets,id'],
            'notes'                     => ['nullable', 'string'],
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'uuid', 'exists:inventory_items,id'],
            'items.*.system_qty'        => ['required', 'numeric', 'min:0'],
            'items.*.actual_qty'        => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
