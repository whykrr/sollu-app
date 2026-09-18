<?php

namespace App\Http\Requests\Cockpit;

use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Support\Facades\Auth;

class GetCockpitDashboardRequest extends BaseInertiaFormRequest
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
            'period' => ['nullable', 'string', 'in:today,yesterday,7_days,last_30_days,this_month,last_month,this_year,all_time'],
        ];
    }
}
