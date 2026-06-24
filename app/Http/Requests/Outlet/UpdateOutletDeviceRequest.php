<?php

namespace App\Http\Requests\Outlet;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOutletDeviceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can('outlet.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'device_name' => ['required', 'string', 'max:255'],
            'device_type' => ['required', 'string', 'max:50'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }
}
