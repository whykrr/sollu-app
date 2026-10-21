<?php

declare(strict_types=1);

namespace App\Http\Requests\App\User;

use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

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
            'plan_id' => ['required', 'string', 'exists:subscription_plans,id'],
            'use_trial' => ['required', 'boolean'],
            'billing_cycle' => ['nullable', 'in:monthly,yearly'],
            'name' => ['required', 'string', 'max:200'],
            'owner_name' => ['required', 'string', 'max:200'],
            'outlet_name' => ['required', 'string', 'max:200'],
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->where(function ($query) {
                    return $query->where('is_root_user', true)->whereNull('deleted_at');
                }),
            ],
            'phone' => [
                'required',
                'regex:/^(0|\+62|62)[0-9]{7,13}$/',
                Rule::unique('users', 'phone')->where(function ($query) {
                    return $query->where('is_root_user', true)->whereNull('deleted_at');
                }),
            ],
            'business_type_id' => ['required', 'integer', 'exists:business_types,id'],
            'password' => ['required', 'confirmed', 'min:8'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'plan_id.required' => 'Pilih salah satu paket langganan terlebih dahulu.',
            'plan_id.exists' => 'Paket langganan yang dipilih tidak valid.',
            'email.unique' => 'Email ini sudah terdaftar sebagai pemilik usaha di bisnis lain.',
            'phone.unique' => 'Nomor telepon sudah terdaftar sebagai pemilik usaha di bisnis lain.',
        ];
    }
}
