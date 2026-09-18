<?php

namespace App\Http\Requests\App\Master\Category;

use App\Enums\PermissionEnum;
use App\Http\Requests\BaseInertiaFormRequest;

class ReorderCategoryRequest extends BaseInertiaFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::CATEGORY_UPDATE->value) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'categories' => ['required', 'array'],
            'categories.*.id' => ['required', 'uuid', 'exists:product_categories,id'],
            'categories.*.parent_id' => ['nullable', 'uuid', 'exists:product_categories,id'],
            'categories.*.sort_order' => ['required', 'integer'],
        ];
    }
}
