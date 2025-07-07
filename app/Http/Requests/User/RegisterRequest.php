<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;

class RegisterRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'        => 'required|string|max:200',
            'owner_name'  => 'required|string|max:200',
            'outlet_name' => 'required|string|max:200',
            'email'       => 'required|email|unique:users,email',
            'phone'       => [
                'required',
                'regex:/^(0|\+62|62)[0-9]{7,13}$/',
            ],
            'address'          => 'required',
            'merchant_type_id' => 'required',
            'password'         => 'required|confirmed|min:8',
        ];
    }
}
