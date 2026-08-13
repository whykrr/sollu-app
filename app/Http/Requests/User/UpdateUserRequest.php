<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

/**
 * Class UserUpdateRequest
 *
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
        $rules = [
            'name' => 'required|max:200',
            'email' => [
                'required',
                'email',
            ],
            'phone' => 'nullable|numeric',
            'pin' => 'nullable|numeric|digits:6',
        ];

        if (! $this->user->is_root_user) {
            $rules['role'] = 'required';
            $rules['outlets'] = 'required|array';
            $rules['outlets.*'] = 'distinct|exists:outlets,id';
        }

        return $rules;
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     * @return void
     */
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $req = $this->all();
            $user = $this->user;

            $find = \App\Models\User::where(function ($builder) use ($req) {
                $builder->where('email', '=', $req['email']);
                if (! empty($req['phone'])) {
                    $builder->orWhere('phone', '=', $req['phone']);
                }
            })->where('id', '!=', $user->id)->first();

            if ($find !== null && $find->merchant_id !== Auth::user()->merchant_id) {
                if ($find->email === $req['email']) {
                    $validator->errors()->add('email', 'Sudah terdaftar di merchant lain!');
                } elseif (! empty($req['phone']) && $find->phone === $req['phone']) {
                    $validator->errors()->add('phone', 'Sudah terdaftar di merchant lain!');
                }
            } elseif ($find) {
                if ($find->email === $req['email']) {
                    $validator->errors()->add('email', 'Sudah terdaftar!');
                } elseif (! empty($req['phone']) && $find->phone === $req['phone']) {
                    $validator->errors()->add('phone', 'Sudah terdaftar!');
                }
            }
        });
    }
}
