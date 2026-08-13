<?php

namespace App\Http\Requests\User;

use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Support\Facades\Auth;

class StoreUserRequest extends BaseInertiaFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()?->can('user.create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|max:200',
            'email' => 'required|email',
            'phone' => 'nullable|numeric',
            'role' => 'required',
            'outlets' => 'required|array',
            'outlets.*' => 'distinct|exists:outlets,id',
            'pin' => 'required|numeric|digits:6',
        ];
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

            $find = \App\Models\User::where(function ($builder) use ($req) {
                $builder->where('email', '=', $req['email']);
                if (! empty($req['phone'])) {
                    $builder->orWhere('phone', '=', $req['phone']);
                }
            })->first();

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
