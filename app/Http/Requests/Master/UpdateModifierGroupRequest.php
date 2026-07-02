<?php

namespace App\Http\Requests\Master;

use Illuminate\Foundation\Http\FormRequest;

class UpdateModifierGroupRequest extends FormRequest
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
            'name'                       => ['required', 'string', 'max:255'],
            'selection_type'             => ['required', 'in:single,multi'],
            'max_select'                 => ['nullable', 'integer', 'min:1'],
            'is_required'                => ['boolean'],
            'options'                    => ['required', 'array', 'min:1'],
            'options.*.name'             => ['required', 'string', 'max:255'],
            'options.*.additional_price' => ['numeric', 'min:0'],
            'options.*.is_default'       => ['boolean'],
        ];
    }
}
