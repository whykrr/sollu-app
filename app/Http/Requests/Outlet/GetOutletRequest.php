<?php

namespace App\Http\Requests\Outlet;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class GetOutletRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()?->can('outlet.view');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sort' => 'nullable|in:name,created_at,updated_at',
            'direction' => 'nullable|in:asc,desc',
            'perpage' => 'nullable|integer|min:1|max:100',
            'search' => 'nullable|string|max:255',
        ];
    }
}
