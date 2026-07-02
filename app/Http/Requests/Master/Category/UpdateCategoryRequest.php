<?php

namespace App\Http\Requests\Master\Category;

use App\Models\Master\ProductCategory;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
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
        $categoryId = $this->route('category')->id ?? $this->route('category');

        return [
            'name' => ['required', 'string', 'max:255'],
            'parent_id' => [
                'nullable',
                'uuid',
                'exists:product_categories,id',
                // Custom rule to ensure max 1 level of nesting and no circular reference
                function ($attribute, $value, $fail) use ($categoryId) {
                    if ($value === $categoryId) {
                        $fail('Kategori tidak dapat menjadi induk untuk dirinya sendiri.');
                        return;
                    }

                    $parentCategory = ProductCategory::find($value);
                    if ($parentCategory && $parentCategory->parent_id !== null) {
                        $fail('Maksimal kedalaman kategori adalah 1 tingkat (Sub-kategori tidak boleh memiliki sub-kategori lagi).');
                        return;
                    }

                    $currentCategory = ProductCategory::withCount('children')->find($categoryId);
                    if ($currentCategory && $currentCategory->children_count > 0 && $value !== null) {
                        $fail('Kategori ini sudah memiliki sub-kategori, sehingga tidak dapat dijadikan sub-kategori dari kategori lain.');
                    }
                },
            ],
            'sort_order' => ['nullable', 'integer'],
        ];
    }
}
