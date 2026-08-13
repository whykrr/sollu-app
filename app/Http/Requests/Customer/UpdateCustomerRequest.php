<?php

namespace App\Http\Requests\Customer;

use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends BaseInertiaFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('customer.update');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // Assume route model binding provides $this->customer
        $customerId = $this->route('customer')?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                'string',
                Rule::unique('customers')->ignore($customerId)->where(function ($query) {
                    return $query->where('business_id', auth()->user()->business_id ?? null);
                }),
            ],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'birthdate' => ['nullable', 'date'],
            'gender' => ['nullable', Rule::in(['male', 'female'])],
            'notes' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
