<?php

namespace App\Http\Requests\App\Settings\Billing;

use App\Enums\PermissionEnum;
use App\Enums\SubscriptionInvoice\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GetBillingInvoiceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::BUSINESS_BILLING->value) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(Status::class)],
            'sort' => ['nullable', 'string', 'in:invoice_number,total_amount,status,created_at,due_date'],
            'direction' => ['nullable', 'in:asc,desc'],
            'perpage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
