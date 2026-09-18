<?php

namespace App\Http\Requests\App\Master\Product;

use App\Enums\PermissionEnum;
use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Support\Facades\Auth;

class GetProductRequest extends BaseInertiaFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()?->can(PermissionEnum::PRODUCT_VIEW->value) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', 'string'],
            'outlet' => ['nullable', 'string'],
            'product_type' => ['nullable', 'string', 'in:basic,service,bundle'],
            'is_deleted' => ['nullable', 'in:0,1,true,false'],
            'sort' => ['nullable', 'string'],
            'direction' => ['nullable', 'in:asc,desc'],
            'perpage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
