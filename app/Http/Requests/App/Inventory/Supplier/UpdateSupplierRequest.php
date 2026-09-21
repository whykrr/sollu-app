<?php

namespace App\Http\Requests\App\Inventory\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'return_period_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'is_active' => ['boolean'],
            'inventory_items' => ['nullable', 'array'],
            'inventory_items.*' => ['uuid', 'exists:inventory_items,id'],
        ];
    }
}
