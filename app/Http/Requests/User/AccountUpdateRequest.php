<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AccountUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
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
                Rule::unique('users', 'email')->ignore(request()->input('id'))],
            'phone' => [
                'nullable',
                'regex:/^(0|\+62|62)[0-9]{7,13}$/',
            ],
        ];
    }
}
