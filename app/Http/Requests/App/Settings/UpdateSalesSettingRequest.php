<?php

namespace App\Http\Requests\App\Settings;

use App\Enums\PermissionEnum;
use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Validation\Rule;

class UpdateSalesSettingRequest extends BaseInertiaFormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->can(PermissionEnum::SETTING_SALES->value);
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
            // POS Settings
            'enable_supervisor_pin' => ['sometimes', 'boolean'],
            'allow_negative_stock' => ['sometimes', 'boolean'],
            'pos_order_types' => ['sometimes', 'array', 'min:1'],
            'pos_order_types.*' => ['string', 'in:walk_in,dine_in,take_away'],

            // B2B Invoice Settings
            'allow_negative_stock_b2b' => ['sometimes', 'boolean'],
            'allow_custom_price_b2b' => ['sometimes', 'boolean'],
            'default_due_days_b2b' => ['sometimes', 'integer', 'min:0', 'max:365'],
            'b2b_invoice_prefix' => ['sometimes', 'string', 'max:5', 'regex:/^[A-Z0-9\-_]+$/i'],
        ];
    }
}
