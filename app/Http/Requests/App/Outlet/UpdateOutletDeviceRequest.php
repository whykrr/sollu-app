<?php

namespace App\Http\Requests\App\Outlet;

use App\Enums\DeviceTypeEnum;
use App\Enums\PermissionEnum;
use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Validation\Rule;

class UpdateOutletDeviceRequest extends BaseInertiaFormRequest
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
            'outlet_id' => [
                'sometimes',
                'required',
                'uuid',
                Rule::exists('outlets', 'id')->where('business_id', $this->user()?->business_id),
            ],
            'device_name' => ['required', 'string', 'max:255'],
            'device_type' => [
                'required',
                Rule::enum(DeviceTypeEnum::class),
                function (string $attribute, mixed $value, \Closure $fail) {
                    $type = DeviceTypeEnum::tryFrom($value);
                    if ($type && ! $type->isAvailable()) {
                        $fail("Tipe perangkat {$type->label()} saat ini belum tersedia.");
                    }
                },
            ],
            'serial_number' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }
}
