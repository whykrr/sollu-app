<?php

namespace App\Http\Requests\App\Promotion;

use App\Enums\PermissionEnum;
use App\Enums\PromotionApplicationMode;
use App\Enums\PromotionDiscountType;
use App\Enums\PromotionTargetScope;
use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class StorePromotionRequest extends BaseInertiaFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::PROMO_CREATE->value) ?? false;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $applicationMode = $this->input('application_mode');
        $targetScope = $this->input('target_scope');
        $discountType = $this->input('discount_type');
        $appliesToAllOutlets = $this->boolean('applies_to_all_outlets');

        $updates = [];

        // 1. Promo Code: Uppercase & trimmed if manual, null if automatic
        if ($applicationMode === PromotionApplicationMode::Automatic->value) {
            $updates['promo_code'] = null;
        } elseif ($this->has('promo_code') && is_string($this->input('promo_code'))) {
            $trimmed = trim($this->input('promo_code'));
            $updates['promo_code'] = $trimmed !== '' ? strtoupper($trimmed) : null;
        }

        // 2. Max Discount Amount: Null if fixed discount
        if ($discountType === PromotionDiscountType::Fixed->value) {
            $updates['max_discount_amount'] = null;
        }

        // 3. Outlets: Null if applies to all outlets
        if ($appliesToAllOutlets) {
            $updates['outlet_ids'] = null;
        }

        // 4. Target Scopes: Nullify unselected scopes
        if ($targetScope !== PromotionTargetScope::Category->value) {
            $updates['category_ids'] = null;
        }
        if ($targetScope !== PromotionTargetScope::Product->value) {
            $updates['product_ids'] = null;
        }
        if ($targetScope !== PromotionTargetScope::Variant->value) {
            $updates['product_item_ids'] = null;
        }

        // 5. Times: Convert empty string to null
        if ($this->has('start_time') && trim((string) $this->input('start_time')) === '') {
            $updates['start_time'] = null;
        }
        if ($this->has('end_time') && trim((string) $this->input('end_time')) === '') {
            $updates['end_time'] = null;
        }

        if (! empty($updates)) {
            $this->merge($updates);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $businessId = $this->user()?->business_id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'application_mode' => ['required', Rule::enum(PromotionApplicationMode::class)],
            'promo_code' => [
                Rule::requiredIf(fn () => $this->input('application_mode') === PromotionApplicationMode::Manual->value),
                'nullable',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('promotions', 'promo_code')->where(function ($query) use ($businessId) {
                    return $query->where('business_id', $businessId);
                }),
            ],
            'target_scope' => ['required', Rule::enum(PromotionTargetScope::class)],
            'discount_type' => ['required', Rule::enum(PromotionDiscountType::class)],
            'discount_value' => [
                'required',
                'numeric',
                'min:0.01',
                function ($attribute, $value, $fail) {
                    if ($this->input('discount_type') === PromotionDiscountType::Percentage->value && (float) $value > 100) {
                        $fail('Nilai diskon persentase harus antara 0.01% hingga 100%.');
                    }
                },
            ],
            'max_discount_amount' => ['nullable', 'numeric', 'min:1'],
            'min_subtotal' => ['nullable', 'numeric', 'min:0'],
            'min_quantity' => ['nullable', 'numeric', 'min:1'],
            'applies_to_all_outlets' => ['required', 'boolean'],
            'outlet_ids' => [
                Rule::requiredIf(fn () => ! $this->boolean('applies_to_all_outlets')),
                'nullable',
                'array',
                Rule::when(! $this->boolean('applies_to_all_outlets'), ['min:1']),
            ],
            'outlet_ids.*' => [
                Rule::exists('outlets', 'id')->where(function ($query) use ($businessId) {
                    return $query->where('business_id', $businessId);
                }),
            ],
            'category_ids' => [
                Rule::requiredIf(fn () => $this->input('target_scope') === PromotionTargetScope::Category->value),
                'nullable',
                'array',
                Rule::when($this->input('target_scope') === PromotionTargetScope::Category->value, ['min:1']),
            ],
            'category_ids.*' => [
                Rule::exists('product_categories', 'id')->where(function ($query) use ($businessId) {
                    return $query->where('business_id', $businessId);
                }),
            ],
            'product_ids' => [
                Rule::requiredIf(fn () => $this->input('target_scope') === PromotionTargetScope::Product->value),
                'nullable',
                'array',
                Rule::when($this->input('target_scope') === PromotionTargetScope::Product->value, ['min:1']),
            ],
            'product_ids.*' => [
                Rule::exists('products', 'id')->where(function ($query) use ($businessId) {
                    return $query->where('business_id', $businessId);
                }),
            ],
            'product_item_ids' => [
                Rule::requiredIf(fn () => $this->input('target_scope') === PromotionTargetScope::Variant->value),
                'nullable',
                'array',
                Rule::when($this->input('target_scope') === PromotionTargetScope::Variant->value, ['min:1']),
            ],
            'product_item_ids.*' => [
                Rule::exists('product_items', 'id')->where(function ($query) use ($businessId) {
                    return $query->where('business_id', $businessId);
                }),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'start_time' => ['nullable', 'date_format:H:i,H:i:s', 'required_with:end_time'],
            'end_time' => ['nullable', 'date_format:H:i,H:i:s', 'required_with:start_time', 'after:start_time'],
            'days_of_week' => ['nullable', 'array'],
            'days_of_week.*' => ['integer', 'between:1,7'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Nama promo wajib diisi.',
            'application_mode.required' => 'Mode aplikasi promo tidak valid.',
            'promo_code.required' => 'Kode promo wajib diisi jika mode manual dan hanya boleh huruf, angka, strip, dan underscore.',
            'promo_code.required_if' => 'Kode promo wajib diisi jika mode manual dan hanya boleh huruf, angka, strip, dan underscore.',
            'promo_code.unique' => 'Kode promo sudah digunakan.',
            'promo_code.alpha_dash' => 'Kode promo hanya boleh huruf, angka, strip, dan underscore.',
            'target_scope.required' => 'Cakupan target promo tidak valid.',
            'discount_type.required' => 'Tipe diskon tidak valid.',
            'discount_value.required' => 'Nilai diskon wajib diisi.',
            'discount_value.min' => 'Nilai potongan nominal harus lebih dari 0.',
            'max_discount_amount.min' => 'Batas maksimum diskon harus berupa nominal positif.',
            'min_subtotal.min' => 'Minimal subtotal tidak boleh bernilai negatif.',
            'min_quantity.min' => 'Minimal kuantitas barang minimal 1.',
            'applies_to_all_outlets.required' => 'Cakupan outlet wajib ditentukan.',
            'outlet_ids.required_if' => 'Pilih minimal satu outlet jika promo tidak berlaku di semua outlet.',
            'category_ids.required_if' => 'Pilih minimal satu kategori produk untuk target kategori.',
            'category_ids.*.exists' => 'Kategori produk yang dipilih tidak valid atau tidak ditemukan.',
            'product_ids.required_if' => 'Pilih minimal satu produk untuk target produk spesifik.',
            'product_ids.*.exists' => 'Produk yang dipilih tidak valid atau tidak ditemukan.',
            'product_item_ids.required_if' => 'Pilih minimal satu varian produk untuk target varian.',
            'product_item_ids.*.exists' => 'Varian produk yang dipilih tidak valid atau tidak ditemukan.',
            'start_date.required' => 'Tanggal mulai wajib diisi.',
            'end_date.required' => 'Tanggal berakhir wajib diisi.',
            'end_date.after_or_equal' => 'Tanggal berakhir tidak boleh mendahului tanggal mulai.',
            'start_time.required_with' => 'Jam mulai wajib diisi jika jam selesai ditentukan.',
            'end_time.required_with' => 'Jam selesai wajib diisi jika jam mulai ditentukan.',
            'end_time.after' => 'Jam selesai harus setelah jam mulai.',
            'days_of_week.*.between' => 'Pilihan hari tidak valid.',
            'outlet_ids.*.exists' => 'Outlet yang dipilih tidak valid atau tidak ditemukan.',
        ];
    }
}
