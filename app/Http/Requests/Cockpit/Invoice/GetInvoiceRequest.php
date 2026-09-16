<?php

namespace App\Http\Requests\Cockpit\Invoice;

use App\Http\Requests\BaseInertiaFormRequest;

class GetInvoiceRequest extends BaseInertiaFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
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
            'status' => ['nullable', 'string', 'max:50'],
            'sort' => ['nullable', 'string', 'in:invoice_number,total_amount,status,created_at,due_date,merchant'],
            'direction' => ['nullable', 'in:asc,desc'],
            'open_invoice' => ['nullable', 'string', 'max:100'],
            'perpage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
