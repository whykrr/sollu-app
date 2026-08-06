<?php

namespace App\Http\Requests\Inventory\StockOpname;

use App\Models\Inventory\StockOpname;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStockOpnameRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes'                     => ['nullable', 'string'],
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'uuid', 'exists:inventory_items,id'],
            'items.*.system_qty'        => ['required', 'numeric', 'min:0'],
            'items.*.actual_qty'        => ['required', 'numeric', 'min:0'], // When updating/submitting results, actual_qty is required
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'                       => 'Minimal 1 item harus dimuat untuk opname.',
            'items.min'                            => 'Minimal 1 item harus dimuat untuk opname.',
            'items.*.inventory_item_id.exists'     => 'Item inventory tidak ditemukan.',
            'items.*.actual_qty.required'          => 'Stok fisik wajib diisi untuk semua item.',
            'items.*.actual_qty.min'               => 'Stok fisik tidak boleh negatif.',
        ];
    }
}

