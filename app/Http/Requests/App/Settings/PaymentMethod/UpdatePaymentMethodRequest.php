<?php

namespace App\Http\Requests\App\Settings\PaymentMethod;

use App\Enums\PaymentMethodType;
use App\Enums\PermissionEnum;
use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentMethodRequest extends BaseInertiaFormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(PermissionEnum::SETTING_PAYMENT->value);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(PaymentMethodType::class)],
            'is_active' => ['nullable', 'boolean'],
            'outlet_ids' => ['nullable', 'array'],
            'outlet_ids.*' => ['string', 'exists:outlets,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama metode pembayaran wajib diisi.',
            'type.required' => 'Tipe metode pembayaran wajib dipilih.',
            'outlet_ids.*.exists' => 'Outlet yang dipilih tidak valid.',
        ];
    }
}
