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
            'max_outlets' => ['nullable', 'integer', 'min:1'],
            'has_trial' => ['nullable', 'boolean'],
            'trial_duration_days' => ['nullable', 'integer', 'min:1', 'max:90'],
            'features' => ['nullable', 'array'],
            'features.*.title' => ['required_with:features', 'string', 'max:255'],
            'features.*.detail' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
            'is_public' => ['nullable', 'boolean'],
            'business_id' => ['nullable', 'uuid', 'exists:businesses,id'],
            'system_feature_ids' => ['nullable', 'array'],
            'system_feature_ids.*' => ['exists:features,id'],
        ];
    }
}
