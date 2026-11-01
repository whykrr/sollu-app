<?php

declare(strict_types=1);

namespace App\Http\Requests\App\User;

use App\Http\Requests\BaseInertiaFormRequest;

class AccountChangePinRequest extends BaseInertiaFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'pin' => ['required', 'numeric', 'digits:6', 'confirmed'],
            'pin_confirmation' => ['required', 'numeric', 'digits:6'],
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pin.required' => 'PIN baru wajib diisi.',
            'pin.numeric' => 'PIN harus berupa angka.',
            'pin.digits' => 'PIN kasir harus terdiri dari 6 digit angka.',
            'pin.confirmed' => 'Konfirmasi PIN tidak cocok.',
            'pin_confirmation.required' => 'Konfirmasi PIN wajib diisi.',
            'pin_confirmation.numeric' => 'Konfirmasi PIN harus berupa angka.',
            'pin_confirmation.digits' => 'Konfirmasi PIN harus terdiri dari 6 digit angka.',
        ];
    }
}
