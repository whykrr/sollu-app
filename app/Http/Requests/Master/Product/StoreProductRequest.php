<?php

namespace App\Http\Requests\Master\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->can('product.create') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $productType = $this->input('product_type');

        if ($productType === 'service') {
            $this->merge([
                'has_variant'     => false,
                'has_recipe'      => false,
                'track_inventory' => false,
            ]);
        } elseif ($productType === 'bundle') {
            $this->merge([
                'has_variant'     => false,
                'has_modifier'    => false,
                'has_recipe'      => false,
                'track_inventory' => false,
            ]);
        }
    }

    public function rules(): array
    {
        $rules = [
            'name'                => 'required|string|max:255',
            'code'                => 'nullable|string|max:255',
            'description'         => 'nullable|string',
            'product_category_id' => 'nullable|uuid|exists:product_categories,id',
            'product_type'        => 'required|in:basic,service,bundle',
            'is_show'             => 'boolean',
            'sellable'            => 'boolean',
            'purchasable'         => 'boolean',
            
            // Feature flags
            'has_variant'         => 'boolean',
            'has_modifier'        => 'boolean',
            'has_recipe'          => 'boolean',
            'track_inventory'     => 'boolean',

            // Price setup
            'base_price'                  => 'required|numeric|min:0',
            'outlet_prices'               => 'nullable|array',
            'outlet_prices.*.outlet_id'   => 'required|uuid|exists:outlets,id',
            'outlet_prices.*.amount'      => 'required|numeric|min:0',

            // Outlets setup
            'outlets'                     => 'nullable|array',
            'outlets.*.outlet_id'         => 'required|uuid|exists:outlets,id',
            'outlets.*.is_enabled'        => 'boolean',
            'outlets.*.is_available'      => 'boolean',

            // Initial stock
            'stock'                       => 'nullable|numeric|min:0',
            'purchase_price'              => 'nullable|numeric|min:0',
            'stock_description'           => 'nullable|string',

            // Multiple images
            'images'                      => 'nullable|array',
            'images.*.image_url'          => 'required_without:images.*.image_file|nullable|string',
            'images.*.image_file'         => 'required_without:images.*.image_url|nullable|file|image|max:2048',
            'images.*.sort_order'         => 'nullable|integer',
        ];

        if ($this->product_type === 'basic') {
            if ($this->has_variant) {
                $rules['variants']                             = 'required|array|min:1';
                $rules['variants.*.name']                      = 'required|string|max:255';
                $rules['variants.*.options']                   = 'required|array|min:1';
                $rules['variants.*.options.*.name']            = 'required|string|max:255';
                
                $rules['variant_combinations']                 = 'required|array|min:1';
                $rules['variant_combinations.*.options']       = 'required|array';
                $rules['variant_combinations.*.sku']           = 'nullable|string';
                $rules['variant_combinations.*.barcode']       = 'nullable|string';
                $rules['variant_combinations.*.price']         = 'nullable|numeric|min:0';
                $rules['variant_combinations.*.stock']         = 'nullable|numeric|min:0';
                $rules['variant_combinations.*.purchase_price']            = 'nullable|numeric|min:0';
                $rules['variant_combinations.*.stock_description']         = 'nullable|string';
                $rules['variant_combinations.*.outlet_prices']             = 'nullable|array';
                $rules['variant_combinations.*.outlet_prices.*.outlet_id'] = 'required|uuid|exists:outlets,id';
                $rules['variant_combinations.*.outlet_prices.*.amount']    = 'required|numeric|min:0';
            }

            if ($this->has_recipe) {
                $rules['recipes']                              = 'required|array|min:1';
                $rules['recipes.*.inventory_item_id']          = 'required|uuid|exists:inventory_items,id';
                $rules['recipes.*.qty']                        = 'required|numeric|min:0';
                $rules['recipes.*.uom']                        = 'required|string';
            }
        }

        if ($this->product_type === 'bundle') {
            $rules['bundle_items']                               = 'required|array|min:1';
            $rules['bundle_items.*.component_product_id']        = 'required|uuid|exists:products,id';
            $rules['bundle_items.*.component_inventory_item_id'] = 'nullable|uuid|exists:inventory_items,id';
            $rules['bundle_items.*.qty']                         = 'required|numeric|min:0';
        }

        if ($this->has_modifier) {
            $rules['modifier_groups']                            = 'nullable|array';
            $rules['modifier_groups.*.modifier_group_id']        = 'required|uuid|exists:modifier_groups,id';
        }

        return $rules;
    }
}
