<?php

namespace App\Http\Requests\App\Inventory\Transfer;

use App\Enums\PermissionEnum;
use App\Enums\StockTransferStatus;
use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class GetStockTransferRequest extends BaseInertiaFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()?->can(PermissionEnum::INVENTORY_TRANSFER_READ->value) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'string', Rule::enum(StockTransferStatus::class)],
            'from_outlet_id' => ['nullable', 'uuid', 'exists:outlets,id'],
            'to_outlet_id' => ['nullable', 'uuid', 'exists:outlets,id'],
            'outlet_id' => ['nullable', 'uuid', 'exists:outlets,id'],
            'preset' => ['nullable', 'string'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'sort' => ['nullable', 'string'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
