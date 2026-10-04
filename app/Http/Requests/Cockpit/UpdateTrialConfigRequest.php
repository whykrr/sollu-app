<?php

namespace App\Http\Requests\Cockpit;

use App\Enums\FeatureEnum;
use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateTrialConfigRequest extends BaseInertiaFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::guard('cockpit')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'duration_days' => ['required', 'integer', 'min:1', 'max:365'],
            'features' => ['present', 'array'],
            'features.*' => ['string', Rule::enum(FeatureEnum::class)],
        ];
    }
}
