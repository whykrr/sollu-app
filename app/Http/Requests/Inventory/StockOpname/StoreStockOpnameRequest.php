<?php

namespace App\Http\Requests\Inventory\StockOpname;

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
            'items.*.system_qty'        => ['required', 'min:0'],
            'items.*.actual_qty'        => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'outlet_id.exists'                 => 'Outlet tidak ditemukan.',
            'items.required'                   => 'Minimal 1 item harus dimuat untuk opname.',
            'items.min'                        => 'Minimal 1 item harus dimuat untuk opname.',
            'items.*.inventory_item_id.exists' => 'Item inventory tidak ditemukan.',
            'items.*.actual_qty.min'           => 'Stok fisik tidak boleh negatif.',
        ];
    }
}
