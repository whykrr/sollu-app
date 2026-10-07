<?php

namespace App\Http\Requests\API\POS;

use Illuminate\Foundation\Http\FormRequest;

class ConnectDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('otp')) {
            $this->merge([
                'otp' => preg_replace('/[^0-9]/', '', (string) $this->otp),
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'otp' => ['required', 'string', 'digits:8'],
            'device_uuid' => ['required', 'string'],
            'hardware_fingerprint' => ['required', 'string'],
            'app_version' => ['nullable', 'string'],
            'platform_type' => ['nullable', 'string'],
        ];
    }
}
