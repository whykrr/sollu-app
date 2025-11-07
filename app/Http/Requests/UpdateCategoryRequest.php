<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Category;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $categoryId = $this->route('category')->id;

        return [
            'name' => 'required|string|max:255',
            'parent_id' => [
                'nullable',
                'exists:categories,id',
                function ($attribute, $value, $fail) use ($categoryId) {
                    if ($value) {
                        if ($value == $categoryId) {
                            $fail('Kategori tidak dapat menjadi parent dari dirinya sendiri.');
                        }

                        $parent = Category::find($value);
                        if ($parent && $parent->parent_id) {
                            $grandparent = Category::find($parent->parent_id);
                            if ($grandparent && $grandparent->parent_id) {
                                $fail('Kategori hanya bisa memiliki maksimal 3 level.');
                            }
                        }
                    }
                },
            ],
        ];
    }
}