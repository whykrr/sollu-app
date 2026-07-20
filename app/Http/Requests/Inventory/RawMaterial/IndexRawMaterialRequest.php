<?php

namespace App\Http\Requests\Inventory\RawMaterial;

use Illuminate\Foundation\Http\FormRequest;

class IndexRawMaterialRequest extends FormRequest
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
            'search'          => 'nullable|string',
            'track_inventory' => 'nullable|boolean',
            'sort'            => 'nullable|string|in:name,sku,barcode',
            'direction'       => 'nullable|string|in:asc,desc',
        ];
    }
}
