<?php

namespace App\Http\Requests\API\POS;

use Illuminate\Foundation\Http\FormRequest;

class ConnectDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'otp' => ['required', 'string', 'size:8'],
            'device_uuid' => ['required', 'string'],
            'hardware_fingerprint' => ['required', 'string'],
        ];
    }
}
