<?php

namespace App\Http\Requests\App\Settings;

use App\Enums\InventoryCostingMethod;
use App\Enums\PermissionEnum;
use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Validation\Rule;

class UpdateInventoryCostingMethodRequest extends BaseInertiaFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::BUSINESS_UPDATE->value)
            || $this->user()?->can(PermissionEnum::SETTING_ALL->value);
    }

    public function rules(): array
    {
        return [
            'costing_method' => [
                'required',
                'string',
                Rule::enum(InventoryCostingMethod::class),
            ],
        ];
    }
}
