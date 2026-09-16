<?php

namespace App\Http\Requests\Cockpit\BusinessType;

use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateBusinessTypeRequest extends BaseInertiaFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::guard('cockpit')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'code' => ['required', 'string', 'max:100', Rule::unique('business_types', 'code')->ignore($id)],
            'name' => ['required', 'string', 'max:100'],
            'sort_order' => ['required', 'integer', 'min:0'],
            'is_visible' => ['required', 'boolean'],
            'features' => ['nullable', 'array'],
            'features.*' => ['string', 'exists:features,code'],
        ];
    }
}
