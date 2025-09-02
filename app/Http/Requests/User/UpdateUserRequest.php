<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Class UserUpdateRequest
 *
 * @package App\Http\Requests
 *
 * @property-read User $user  The bound user model from route model binding.
 */
class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()?->can('user.update');
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
            'phone'     => 'nullable|numeric',
            'role'      => 'required',
            'outlets'   => 'required|array',
            'outlets.*' => 'distinct|exists:outlets,id',
        ];
    }
}
