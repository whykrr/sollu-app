<?php

namespace App\Http\Requests\App\Settings;

use App\Enums\PermissionEnum;
use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Validation\Rule;

class UpdateSalesSettingRequest extends BaseInertiaFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::SETTING_SALES->value)
            || $this->user()?->can(PermissionEnum::SETTING_ALL->value)
            || $this->user()?->can(PermissionEnum::BUSINESS_ALL->value);
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
            'allow_negative_stock_b2b' => ['required', 'boolean'],
            'allow_custom_price_b2b' => ['required', 'boolean'],
            'sales_channels_b2b' => ['required', 'array', 'min:1'],
            'sales_channels_b2b.*' => ['string', 'in:direct,wholesale,e_commerce,social_media,custom'],
            'default_due_days_b2b' => ['required', 'integer', 'min:0', 'max:365'],
            'default_terms_and_conditions_b2b' => ['nullable', 'string', 'max:2000'],
            'b2b_invoice_prefix' => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9\-_]+$/i'],
        ];
    }
}
