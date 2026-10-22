<?php

namespace App\Http\Requests\App\Master\Recipe;

use Illuminate\Foundation\Http\FormRequest;

class StoreRecipeTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'product_id' => ['required', 'uuid', 'exists:products,id'],
            'product_item_id' => ['nullable', 'uuid', 'exists:product_items,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_item_id' => ['required', 'uuid', 'exists:product_items,id'],
            'items.*.qty' => ['required', 'numeric', 'min:0.0001'],
            'items.*.uom' => ['required', 'string', 'max:50'],
        ];
    }
}
