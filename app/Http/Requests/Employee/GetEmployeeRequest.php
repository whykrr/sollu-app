<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class GetEmployeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()?->can('user.view');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sort' => 'nullable|in:name,email,created_at',
            'direction' => 'nullable|in:asc,desc',
            'perpage' => 'nullable|integer|min:1|max:100',
            'search' => 'nullable|string|max:255',
            'role' => 'nullable',
            'is_deleted' => 'nullable|boolean',
            'outlet' => 'nullable|exists:outlets,id',
        ];
    }
}
