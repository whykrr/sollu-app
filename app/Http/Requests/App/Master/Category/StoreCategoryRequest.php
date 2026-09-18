<?php

namespace App\Http\Requests\App\Master\Category;

use App\Enums\PermissionEnum;
use App\Http\Requests\BaseInertiaFormRequest;
use App\Models\Master\ProductCategory;

class StoreCategoryRequest extends BaseInertiaFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::CATEGORY_CREATE->value) ?? false;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->parent_id === '') {
            $this->merge([
                'parent_id' => null,
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => [
                'nullable',
                'uuid',
                'exists:product_categories,id',
                // Custom rule to ensure max 1 level of nesting
                function ($attribute, $value, $fail) {
                    $parentCategory = ProductCategory::find($value);
                    if ($parentCategory && $parentCategory->parent_id !== null) {
                        $fail('Maksimal kedalaman kategori adalah 1 tingkat (Sub-kategori tidak boleh memiliki sub-kategori lagi).');
                    }
                },
            ],
            'sort_order' => ['nullable', 'integer'],
        ];
    }
}
