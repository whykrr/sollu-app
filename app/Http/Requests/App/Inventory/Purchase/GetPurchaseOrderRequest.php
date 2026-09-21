<?php

namespace App\Http\Requests\App\Inventory\Purchase;

use App\Enums\PermissionEnum;
use App\Enums\PurchaseOrderStatus;
use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class GetPurchaseOrderRequest extends BaseInertiaFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()?->can(PermissionEnum::PURCHASE_ORDER_VIEW->value) ?? false;
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
            'status' => ['nullable', 'string', Rule::enum(PurchaseOrderStatus::class)],
            'supplier_id' => ['nullable', 'uuid', 'exists:suppliers,id'],
            'outlet_id' => ['nullable', 'uuid', 'exists:outlets,id'],
            'preset' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'sort' => ['nullable', 'string'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
            'perpage' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
