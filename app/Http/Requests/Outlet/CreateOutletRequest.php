<?php

namespace App\Http\Requests\Outlet;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CreateOutletRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()?->can('outlet.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('outlets')->where(function ($query) {
                    return $query->where('business_id', Auth::user()->business_id);
                }),
                function ($attribute, $value, $fail) {
                    $business = Auth::user()->business;
                    if ($business && $business->outlets()->count() >= $business->maxOutletsAllowed()) {
                        $fail('Batas maksimum outlet untuk paket langganan Anda telah tercapai. Harap upgrade paket Anda.');
                    }
                },
            ],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'timezone' => ['nullable', 'string', 'max:50'],
            'currency_code' => ['nullable', 'string', 'max:3'],
        ];
    }
}
