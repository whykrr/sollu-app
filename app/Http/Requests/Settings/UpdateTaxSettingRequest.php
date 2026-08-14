<?php

namespace App\Http\Requests\Settings;

use App\Enums\PermissionEnum;
use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Validation\Rule;

class UpdateTaxSettingRequest extends BaseInertiaFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::SETTING_TAX->value)
            || $this->user()?->can(PermissionEnum::OUTLET_UPDATE->value)
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
            'financial_tax' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'financial_service_fee' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'tax_included_in_price' => ['nullable', 'boolean'],
            'rounding_enabled' => ['nullable', 'boolean'],
            'rounding_mode' => ['nullable', 'string', 'in:up,down,nearest'],
        ];
    }
}
