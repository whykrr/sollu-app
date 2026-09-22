<?php

namespace App\Http\Requests\App\Employee;

use App\Http\Requests\BaseInertiaFormRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends BaseInertiaFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()?->can('user.create') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $businessId = Auth::user()?->business_id;

        return [
            'name' => 'required|string|max:200',
            'email' => 'required|email|max:200',
            'phone' => 'nullable|numeric|digits_between:8,16',
            'role' => [
                'required',
                'string',
                Rule::exists('roles', 'name')->where('business_id', $businessId),
            ],
            'outlets' => 'required|array|min:1',
            'outlets.*' => [
                'distinct',
                Rule::exists('outlets', 'id')->where('business_id', $businessId),
            ],
            'pin' => 'required|numeric|digits:6',
        ];
    }

    /**
     * Custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.max' => 'Nama lengkap tidak boleh lebih dari :max karakter.',
            'email.required' => 'Alamat email wajib diisi.',
            'email.email' => 'Format email kurang tepat. Contoh: nama@bisnis.com',
            'phone.numeric' => 'Nomor telepon harus berupa angka.',
            'phone.digits_between' => 'Nomor telepon harus terdiri dari :min hingga :max angka.',
            'role.required' => 'Silakan pilih peran untuk karyawan ini.',
            'role.exists' => 'Peran yang dipilih tidak valid.',
            'outlets.required' => 'Pilih setidaknya satu outlet untuk akses karyawan.',
            'outlets.min' => 'Pilih setidaknya satu outlet untuk akses karyawan.',
            'pin.required' => 'PIN karyawan wajib diisi.',
            'pin.digits' => 'PIN karyawan harus berupa 6 digit angka.',
        ];
    }

    /**
     * Configure the validator instance.
     *
     * @param  \Illuminate\Validation\Validator  $validator
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $email = $this->input('email');
            $phone = $this->input('phone');

            if (! empty($email)) {
                $emailExists = User::withTrashed()->where('email', $email)->exists();
                if ($emailExists) {
                    $validator->errors()->add('email', 'Email ini sudah terdaftar di sistem. Coba gunakan alamat email lain ya.');
                }
            }

            if (! empty($phone)) {
                $phoneExists = User::withTrashed()->where('phone', $phone)->exists();
                if ($phoneExists) {
                    $validator->errors()->add('phone', 'Nomor telepon sudah digunakan. Coba gunakan nomor telepon lain ya.');
                }
            }
        });
    }
}
