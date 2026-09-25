<?php

namespace App\Http\Requests\App\Master\Service;

use App\Enums\PermissionEnum;
use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Support\Facades\Auth;

class StoreServiceProductRequest extends BaseInertiaFormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->can(PermissionEnum::SERVICE_CREATE->value) ?? false;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'product_type' => 'service',
            'has_variant' => false,
            'has_modifier' => false,
            'has_recipe' => false,
            'track_inventory' => false,
            'purchasable' => false,
            'is_show' => $this->boolean('is_show', true),
            'sellable' => $this->boolean('sellable', true),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50'],
            'description' => ['nullable', 'string'],
            'product_category_id' => ['nullable', 'uuid', 'exists:product_categories,id'],
            'is_show' => ['boolean'],
            'sellable' => ['boolean'],

            // Price setup
            'base_price' => ['required', 'numeric', 'min:0'],
            'outlet_prices' => ['nullable', 'array'],
            'outlet_prices.*.outlet_id' => ['required', 'uuid', 'exists:outlets,id'],
            'outlet_prices.*.amount' => ['required', 'numeric', 'min:0'],

            // Outlets setup
            'outlets' => ['nullable', 'array'],
            'outlets.*.outlet_id' => ['required', 'uuid', 'exists:outlets,id'],
            'outlets.*.is_enabled' => ['boolean'],
            'outlets.*.is_available' => ['boolean'],

            // Images
            'images' => ['nullable', 'array'],
            'images.*.image_url' => ['required_without:images.*.image_file', 'nullable', 'string'],
            'images.*.image_file' => ['required_without:images.*.image_url', 'nullable', 'file', 'image', 'max:2048'],
            'images.*.sort_order' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Nama layanan belum diisi nih.',
            'name.max' => 'Nama layanan maksimal 255 karakter ya.',
            'code.max' => 'Kode layanan maksimal 50 karakter ya.',
            'base_price.required' => 'Tarif dasar layanan wajib diisi ya.',
            'base_price.numeric' => 'Tarif dasar layanan harus berupa nominal angka.',
            'base_price.min' => 'Tarif dasar layanan tidak boleh bernilai negatif.',
            'product_category_id.exists' => 'Kategori layanan yang dipilih tidak ditemukan.',
            'outlet_prices.*.outlet_id.exists' => 'Outlet yang dipilih tidak valid.',
            'outlet_prices.*.amount.min' => 'Tarif per outlet tidak boleh bernilai negatif.',
            'images.*.image_file.image' => 'Berkas foto harus berupa gambar valid.',
            'images.*.image_file.max' => 'Ukuran foto layanan maksimal 2 MB ya.',
        ];
    }
}
