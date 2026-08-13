<?php

namespace App\Http\Requests\Customer;

use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends BaseInertiaFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->can('customer.create');
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        // Assuming business_id is obtained via auth context, not required from client
        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => [
                'required',
                'string',
                // Unique per business_id (assumes business_id is available via auth or hidden input)
                Rule::unique('customers')->where(function ($query) {
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
