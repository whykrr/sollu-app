<?php

namespace App\Http\Requests\App\Settings\PaymentMethod;

use App\Enums\PaymentMethodType;
use App\Enums\PaymentReferencePolicy;
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
            'name' => ['required', 'string', 'max:100'],
            'type' => ['required', Rule::enum(PaymentMethodType::class)],
            'provider' => ['nullable', 'string', 'max:50'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'account_name' => ['nullable', 'string', 'max:100'],
            'reference_policy' => ['required', Rule::enum(PaymentReferencePolicy::class)],
            'open_cash_drawer' => ['nullable', 'boolean'],
            'instructions' => ['nullable', 'string', 'max:500'],
            'qr_image' => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:2048'],
            'remove_qr_image' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'outlet_ids' => ['nullable', 'array'],
            'outlet_ids.*' => ['string', 'exists:outlets,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama metode pembayaran wajib diisi.',
            'name.max' => 'Nama metode pembayaran maksimal 100 karakter.',
            'type.required' => 'Jenis metode pembayaran wajib dipilih.',
            'reference_policy.required' => 'Kebijakan nomor referensi wajib dipilih.',
            'qr_image.image' => 'File QR harus berupa gambar.',
            'qr_image.mimes' => 'Format gambar QR harus PNG, JPG, JPEG, atau WEBP.',
            'qr_image.max' => 'Ukuran gambar QR maksimal 2MB.',
            'outlet_ids.*.exists' => 'Outlet yang dipilih tidak valid.',
        ];
    }
}
