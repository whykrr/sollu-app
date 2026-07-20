<?php

namespace App\Http\Requests\Inventory\Transfer;

use Illuminate\Foundation\Http\FormRequest;

class ProcessStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'items'                  => ['required', 'array', 'min:1'],
            'items.*.id'             => ['required', 'uuid', 'exists:stock_transfer_items,id'],
            'items.*.qty_received'   => ['required', 'numeric', 'min:0'],
        ];
    }
}
