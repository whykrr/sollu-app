<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()?->can('product.update');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name'  => 'required|max:200',
            'email' => [
                'required',
                'email',
                Rule::unique('users', 'email')->ignore($this->user)],
            'role'           => 'required',
            'merchant_ids'   => 'required|array',
            'merchant_ids.*' => 'distinct|exists:merchants,id',
        ];
    }
}
