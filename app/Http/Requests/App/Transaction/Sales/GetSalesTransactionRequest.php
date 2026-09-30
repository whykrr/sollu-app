<?php

declare(strict_types=1);

namespace App\Http\Requests\App\Transaction\Sales;

use App\Enums\PermissionEnum;
use App\Enums\SalesChannelEnum;
use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class GetSalesTransactionRequest extends BaseInertiaFormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->can(PermissionEnum::TRANSACTION_VIEW->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'channel' => ['nullable', 'string', Rule::enum(SalesChannelEnum::class)],
            'status' => ['nullable', 'string'],
            'payment_status' => ['nullable', 'string'],
            'outlet' => ['nullable', 'string'],
            'outlet_id' => ['nullable', 'uuid', 'exists:outlets,id'],
            'preset' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'sort' => ['nullable', 'string'],
            'direction' => ['nullable', 'in:asc,desc'],
            'perpage' => ['nullable', 'integer', 'min:1', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
