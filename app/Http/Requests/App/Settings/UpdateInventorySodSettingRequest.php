<?php

namespace App\Http\Requests\App\Settings;

use App\Http\Requests\BaseInertiaFormRequest;

class UpdateInventorySodSettingRequest extends BaseInertiaFormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->is_root_user;
    }

    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'allow_owner_bypass' => ['required', 'boolean'],
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
