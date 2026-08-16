<?php

namespace App\Http\Requests\Outlet;

use App\Enums\PermissionEnum;
use App\Http\Requests\BaseInertiaFormRequest;

class CreateOutletDeviceRequest extends BaseInertiaFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::SETTING_DEVICE->value)
            || $this->user()?->can(PermissionEnum::OUTLET_UPDATE->value)
            || false;
    }

    public function rules(): array
    {
        return [
            'outlet_id' => ['nullable', 'uuid'],
            'device_name' => ['required', 'string', 'max:255'],
            'device_type' => ['required', 'string', 'max:50'],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }
}
