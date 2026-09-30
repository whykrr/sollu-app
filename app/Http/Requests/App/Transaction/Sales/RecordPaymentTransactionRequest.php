<?php

declare(strict_types=1);

namespace App\Http\Requests\App\Transaction\Sales;

use App\DTOs\Transaction\RecordPaymentDTO;
use App\Enums\PermissionEnum;
use App\Http\Requests\BaseInertiaFormRequest;
use Carbon\Carbon;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class RecordPaymentTransactionRequest extends BaseInertiaFormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->can(PermissionEnum::TRANSACTION_RECORD_PAYMENT->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'payment_method_id' => ['required', 'uuid', 'exists:payment_methods,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'change_amount' => ['nullable', 'numeric', 'min:0'],
            'payment_reference' => ['nullable', 'string', 'max:255'],
            'payment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method_id.required' => 'Metode pembayaran wajib dipilih untuk transaksi tunai atau pembayaran uang muka (DP).',
            'amount.required' => 'Nominal pembayaran cicilan minimal Rp 1.',
            'amount.min' => 'Nominal pembayaran cicilan minimal Rp 1.',
            'payment_date.required' => 'Tanggal realisasi pembayaran wajib diisi.',
        ];
    }

    public function toDTO(): RecordPaymentDTO
    {
        return new RecordPaymentDTO(
            paymentMethodId: $this->validated('payment_method_id'),
            amount: (float) $this->validated('amount'),
            paymentDate: Carbon::parse($this->validated('payment_date')),
            changeAmount: (float) $this->validated('change_amount', 0.0),
            paymentReference: $this->validated('payment_reference'),
            notes: $this->validated('notes')
        );
    }
}
