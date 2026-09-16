<?php

namespace App\Http\Requests\Cockpit\BusinessType;

use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Support\Facades\Auth;

class UpdateBusinessTypeFeaturesRequest extends BaseInertiaFormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'features' => ['present', 'array'],
            'features.*' => ['string', 'exists:features,code'],
        ];
    }
}
