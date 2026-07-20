<?php

namespace App\Http\Requests\Inventory\Transfer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'from_outlet_id'            => ['required', 'uuid', 'exists:outlets,id'],
            'to_outlet_id'              => ['required', 'uuid', 'exists:outlets,id', 'different:from_outlet_id'],
            'transfer_date'             => ['required', 'date'],
            'notes'                     => ['nullable', 'string'],
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'uuid', 'exists:inventory_items,id'],
            'items.*.qty_transferred'   => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
