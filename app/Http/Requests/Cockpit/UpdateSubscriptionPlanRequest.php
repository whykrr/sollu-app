<?php

namespace App\Http\Requests\Cockpit;

use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateSubscriptionPlanRequest extends BaseInertiaFormRequest
{
    public function authorize(): bool
    {
        return Auth::guard('cockpit')->check();
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'price_per_outlet' => ['required', 'numeric', 'min:0'],
            'yearly_discount_percent' => ['required', 'integer', 'min:0', 'max:100'],
            'max_outlet' => ['nullable', 'integer', 'min:1'],
            'features' => ['nullable', 'array'],
            'features.*.title' => ['required_with:features', 'string', 'max:255'],
            'features.*.detail' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'is_public' => ['nullable', 'boolean'],
            'is_custom' => ['nullable', 'boolean'],
            'system_feature_ids' => ['nullable', 'array'],
            'system_feature_ids.*' => ['exists:features,id'],
        ];
    }
}
