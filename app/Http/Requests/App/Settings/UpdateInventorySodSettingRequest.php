<?php

namespace App\Http\Requests\App\Settings;

use App\Enums\PermissionEnum;
use App\Http\Requests\BaseInertiaFormRequest;

class UpdateInventorySodSettingRequest extends BaseInertiaFormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user) {
            return false;
        }

        return (bool) $user->is_root_user
            || $user->can(PermissionEnum::BUSINESS_VIEW->value)
            || $user->can('business.update')
            || $user->can('business.*')
            || $user->can(PermissionEnum::SETTING_AUDIT->value);
    }

    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'allow_owner_bypass' => ['nullable', 'boolean'],
            'rules' => ['nullable', 'array'],
            'rules.stock_adjustment' => ['nullable', 'boolean'],
            'rules.stock_opname' => ['nullable', 'boolean'],
            'rules.stock_transfer_approval' => ['nullable', 'boolean'],
            'rules.stock_transfer_receive' => ['nullable', 'boolean'],
            'rules.purchase_order_receive' => ['nullable', 'boolean'],
            'rules.direct_purchase_allowed' => ['nullable', 'boolean'],
        ];
    }
}
