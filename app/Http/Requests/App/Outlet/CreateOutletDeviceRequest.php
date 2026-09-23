<?php

namespace App\Http\Requests\App\Outlet;

use App\Enums\DeviceTypeEnum;
use App\Enums\PermissionEnum;
use App\Helpers\SelectedOutlet;
use App\Http\Requests\BaseInertiaFormRequest;
use App\Models\Outlet;
use Illuminate\Validation\Rule;

class CreateOutletDeviceRequest extends BaseInertiaFormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->has('outlet_id') || empty($this->input('outlet_id'))) {
            $outlet = $this->route('outlet');
            if ($outlet) {
                $this->merge([
                    'outlet_id' => $outlet instanceof Outlet ? $outlet->id : $outlet,
                ]);
            } else {
                $activeOutletId = SelectedOutlet::make($this->user())->currentId();
                if ($activeOutletId) {
                    $this->merge([
                        'outlet_id' => $activeOutletId,
                    ]);
                } else {
                    $singleOutlet = Outlet::where('business_id', $this->user()?->business_id)->select('id')->get();
                    if ($singleOutlet->count() === 1) {
                        $this->merge([
                            'outlet_id' => $singleOutlet->first()->id,
                        ]);
                    }
                }
            }
        }
    }

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
