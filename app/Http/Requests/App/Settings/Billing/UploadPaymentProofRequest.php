<?php

namespace App\Http\Requests\App\Settings\Billing;

use App\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;

class UploadPaymentProofRequest extends FormRequest
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
            'payment_proof' => ['required', 'image', 'max:2048'],
        ];
    }
}
