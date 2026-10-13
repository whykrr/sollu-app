<?php

namespace App\Http\Requests\App\User;

use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * @property string $name
 * @property string $owner_name
 * @property string $outlet_name
 * @property string $email
 * @property string $phone
 * @property int $business_type_id
 * @property string $password
 */
class RegisterRequest extends BaseInertiaFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:200'],
            'owner_name' => ['required', 'string', 'max:200'],
            'outlet_name' => ['required', 'string', 'max:200'],
            'email' => ['required', 'email', 'unique:users,email'],
            'phone' => [
                'required',
                'regex:/^(0|\+62|62)[0-9]{7,13}$/',
                'unique:users,phone',
            ],
            'business_type_id' => ['required', 'integer', 'exists:business_types,id'],
            'password' => ['required', 'confirmed', 'min:8'],
        ];
    }
}
