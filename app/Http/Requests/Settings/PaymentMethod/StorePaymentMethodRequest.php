<?php

namespace App\Http\Requests\Settings\PaymentMethod;

use App\Enums\PaymentMethodType;
use App\Enums\PermissionEnum;
use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Validation\Rule;

class StorePaymentMethodRequest extends BaseInertiaFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can(PermissionEnum::SETTING_PAYMENT->value) || $this->user()->can(PermissionEnum::SETTING_ALL->value);
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
