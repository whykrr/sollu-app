<?php

namespace App\Http\Requests;

use App\Enums\PermissionEnum;
use App\Enums\PromoTarget;
use App\Enums\PromoType;
use Illuminate\Validation\Rule;

class StorePromoRequest extends BaseInertiaFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can(PermissionEnum::PROMO_CREATE->value);
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
            'description' => ['nullable', 'string'],
            'promo_type' => ['required', Rule::enum(PromoType::class)],
            'target_type' => ['required', Rule::enum(PromoTarget::class)],
            'discount_value' => ['required', 'numeric', 'min:0'],
            'max_discount' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::requiredIf(fn () => $this->promo_type === PromoType::Percentage->value),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'start_time' => ['nullable', 'date_format:H:i', 'required_with:end_time'],
            'end_time' => ['nullable', 'date_format:H:i', 'required_with:start_time'],
            'applies_to_all_outlets' => ['required', 'boolean'],
            'outlet_ids' => [
                'array',
                Rule::requiredIf(fn () => ! $this->applies_to_all_outlets),
            ],
            'outlet_ids.*' => ['exists:outlets,id'],
            'inventory_item_ids' => [
                'array',
                Rule::requiredIf(fn () => $this->target_type === PromoTarget::Product->value),
            ],
            'inventory_item_ids.*' => ['exists:inventory_items,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => 'Tanggal berakhir tidak boleh sebelum tanggal mulai.',
            'max_discount.required_if' => 'Batas maksimal diskon harus diisi untuk tipe persentase.',
            'inventory_item_ids.required_if' => 'Minimal satu produk harus dipilih untuk promo per produk.',
            'outlet_ids.required_if' => 'Minimal satu outlet harus dipilih jika tidak berlaku untuk semua.',
            'start_time.required_with' => 'Jam mulai dan jam selesai harus diisi keduanya jika ingin menggunakan jadwal waktu.',
            'end_time.required_with' => 'Jam mulai dan jam selesai harus diisi keduanya jika ingin menggunakan jadwal waktu.',
        ];
    }
}
