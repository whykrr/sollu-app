<?php

namespace App\Http\Requests\Settings;

use App\Enums\PermissionEnum;
use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Validation\Rule;

class UpdateOperationalSettingRequest extends BaseInertiaFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::OUTLET_UPDATE->value)
            || $this->user()?->can(PermissionEnum::SETTING_ALL->value);
    }

    public function rules(): array
    {
        $businessId = $this->user()->business_id;

        return [
            'outlet_id' => [
                'required',
                'uuid',
                Rule::exists('outlets', 'id')->where('business_id', $businessId),
            ],
            'hours' => ['required', 'array', 'size:7'],
            'hours.*.day_of_week' => ['required', 'integer', 'min:0', 'max:6'],
            'hours.*.open_time' => ['nullable', 'date_format:H:i', 'required_if:hours.*.is_closed,false'],
            'hours.*.close_time' => ['nullable', 'date_format:H:i', 'required_if:hours.*.is_closed,false'],
            'hours.*.is_closed' => ['required', 'boolean'],
        ];
    }
}
