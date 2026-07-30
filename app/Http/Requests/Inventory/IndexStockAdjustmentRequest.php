<?php

namespace App\Http\Requests\Inventory;

use App\Enums\AdjustmentReason;
use App\Enums\AdjustmentStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class IndexStockAdjustmentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()?->can('inventory.adjustment.read') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search'        => ['nullable', 'string', 'max:255'],
            'status'        => ['nullable', 'string', Rule::enum(AdjustmentStatus::class)],
            'reason'        => ['nullable', 'string', Rule::enum(AdjustmentReason::class)],
            'outlet_id'     => ['nullable', 'uuid'],
            'date_from'     => ['nullable', 'date'],
            'date_to'       => ['nullable', 'date', 'after_or_equal:date_from'],
            'adjustment_id' => ['nullable', 'uuid'],
            'per_page'      => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort'          => ['nullable', 'string', 'in:created_at,adjustment_number'],
            'direction'     => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
