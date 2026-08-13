<?php

namespace App\Http\Requests\Transaction\Sales;

use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Support\Facades\Gate;

class RecordPaymentTransactionRequest extends BaseInertiaFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Gate::allows('transaction.record_payment');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payment_method_id' => ['required', 'uuid'],
            'amount' => ['required', 'numeric', 'min:1'],
            'payment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
