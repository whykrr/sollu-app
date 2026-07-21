<?php

namespace App\Http\Requests\Inventory\Supplier;

use Illuminate\Foundation\Http\FormRequest;

class GetSupplierRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search'    => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable'],
            'sort'      => ['nullable', 'string'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
            'page'      => ['nullable', 'integer', 'min:1'],
        ];
    }
}
