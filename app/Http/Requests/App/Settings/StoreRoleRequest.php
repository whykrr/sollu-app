<?php

namespace App\Http\Requests\App\Settings;

use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends BaseInertiaFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()?->can('role.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'label' => [
                'required',
                'string',
                'max:100',
                Rule::unique('roles', 'label')->where('business_id', Auth::user()->business_id),
            ],
            'permissions' => 'required|array|min:1',
            'permissions.*' => 'exists:permissions,name',
        ];
    }
}
