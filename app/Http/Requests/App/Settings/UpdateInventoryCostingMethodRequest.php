<?php

namespace App\Http\Requests\App\Settings;

use App\Enums\InventoryCostingMethod;
use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Validation\Rule;

class UpdateInventoryCostingMethodRequest extends BaseInertiaFormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_root_user;
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
