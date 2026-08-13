<?php

namespace App\Http\Requests\Inventory\RawMaterial;

use Illuminate\Foundation\Http\FormRequest;

class StoreRawMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'sku' => ['nullable', 'string', 'max:255'],
            'barcode' => ['nullable', 'string', 'max:255'],
            'uom_id' => ['required', 'exists:uoms,id'],
            'track_inventory' => ['boolean'],
            'minimum_stock' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
