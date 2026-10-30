<?php

namespace App\Http\Requests\App\Inventory;

use App\Enums\AdjustmentReason;
use App\Enums\PermissionEnum;
use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StoreStockAdjustmentRequest extends BaseInertiaFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()?->can(PermissionEnum::INVENTORY_ADJUST_CREATE->value) ?? false;
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
            'reason' => ['required', 'string', Rule::enum(AdjustmentReason::class)],
            'notes' => ['nullable', 'string', 'max:500'],
            'is_draft' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.inventory_item_id' => ['required', 'uuid', 'exists:inventory_items,id', 'distinct'],
            'items.*.qty_change' => ['required', 'numeric', 'not_in:0'],
            'items.*.unit_cost' => ['nullable', 'numeric', 'min:0'],
            'items.*.description' => ['required', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.min' => 'Minimal 1 item harus ditambahkan.',
            'items.*.qty_change.not_in' => 'Jumlah perubahan tidak boleh nol.',
            'items.*.inventory_item_id.distinct' => 'Item tidak boleh duplikat dalam satu dokumen.',
            'items.*.description.required' => 'Deskripsi per item wajib diisi.',
            'items.*.unit_cost.numeric' => 'Cost harus berupa angka.',
        ];
    }
}
