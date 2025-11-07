<?php

namespace App\Http\Requests\Merchant;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

/**
 * @property-read array $items
 * @property-read int $subscription_plan_id
 * @property-read string $start_date
 * @property-read string $period_end
 */
class CreateInvoiceSubscribeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()?->can('merchant.billing');

    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'merchant_id'          => ['required'],
            'subscription_plan_id' => ['required'],
            'subtotal'             => ['required'],
            'add_ons'              => ['min:0'],
            'tax'                  => ['required'],
            'discount'             => ['required'],
            'total'                => ['required'],
            'note'                 => ['required'],
            'start_date'           => ['required'],
            'period_end'           => ['required'],
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            redirect()
                ->back()
                ->withInput()
                ->with([
                    'failed' => 'terjadi kesalahan, harap reload halaman',
                ])
        );
    }
}
