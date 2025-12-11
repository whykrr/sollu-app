<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\Product\ProductCategory;

class StoreProductCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'parent_id' => [
                'nullable',
                'exists:product_categories,id',
                function ($attribute, $value, $fail) {
                    if ($value) {
                        $parent = ProductCategory::find($value);
                        // Check level 1
                        if ($parent && $parent->parent_id) {
                            $grandparent = ProductCategory::find($parent->parent_id);
                            // Check level 2
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