<?php

namespace App\Http\Requests\API\POS;

use Illuminate\Foundation\Http\FormRequest;

class UnpairPosDeviceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['nullable', 'string', 'exists:users,id'],
            'pin' => ['nullable', 'string'],
        ];
    }
}
