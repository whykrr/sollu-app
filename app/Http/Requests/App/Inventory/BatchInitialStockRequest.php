<?php

namespace App\Http\Requests\App\Inventory;

use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class BatchInitialStockRequest extends BaseInertiaFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check() && Auth::user()->business_id !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'outlet_id' => ['required', 'uuid', 'exists:outlets,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'uuid', 'exists:inventory_items,id', 'distinct'],
            'items.*.qty' => ['required', 'numeric', 'min:0.01'],
            'items.*.purchase_price' => ['required', 'numeric', 'min:0'],
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
            'outlet_id.required' => 'Outlet tujuan wajib dipilih.',
            'items.required' => 'Daftar barang belum ada yang dipilih nih.',
            'items.min' => 'Pilih minimal 1 barang untuk diisi stok awalnya.',
            'items.*.inventory_item_id.required' => 'Barang tidak valid.',
            'items.*.inventory_item_id.distinct' => 'Ada barang yang terduplikasi di dalam daftar.',
            'items.*.qty.required' => 'Jumlah stok awal wajib diisi.',
            'items.*.qty.min' => 'Jumlah stok awal harus lebih besar dari 0.',
            'items.*.purchase_price.required' => 'Harga modal beli wajib diisi.',
            'items.*.purchase_price.min' => 'Harga modal beli tidak boleh bernilai negatif.',
        ];
    }
}
