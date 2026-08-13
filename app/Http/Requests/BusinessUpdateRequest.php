<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BusinessUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()->hasRole('owner');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'max:150'],
            'email' => ['required', 'email',
                Rule::unique('businesses', 'email')->ignore(request()->input('id'))],
            'phone' => [
                'nullable',
                'regex:/^(0|\+62|62)[0-9]{7,13}$/',
            ],
            'owner_name' => ['required', 'max:200'],
            'address' => ['nullable'],
        ];
    }
}
