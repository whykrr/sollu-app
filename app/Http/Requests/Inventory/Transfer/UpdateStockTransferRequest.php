<?php

namespace App\Http\Requests\Inventory\Transfer;

use App\Enums\PermissionEnum;
use App\Enums\StockTransferStatus;
use Illuminate\Foundation\Http\FormRequest;

class UpdateStockTransferRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::INVENTORY_TRANSFER_UPDATE->value) ?? false;
    }

    public function rules(): array
    {
        return [
            'from_outlet_id'            => ['required', 'uuid', 'exists:outlets,id'],
            'to_outlet_id'              => ['required', 'uuid', 'exists:outlets,id', 'different:from_outlet_id'],
            'notes'                     => ['nullable', 'string'],
            'items'                     => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'uuid', 'exists:inventory_items,id', 'distinct'],
            'items.*.qty'               => ['required', 'numeric', 'min:0.01'],
        ];
    }
}
