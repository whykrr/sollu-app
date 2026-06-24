<?php

namespace App\Http\Requests\Outlet;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOutletSettingRequest extends FormRequest
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
            'settings' => ['required', 'array'],
            'settings.*.category' => ['required', 'string', 'max:50'],
            'settings.*.key' => ['required', 'string', 'max:50'],
            'settings.*.value' => ['nullable'], // can be string, boolean, etc.
        ];
    }
}
