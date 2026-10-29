<?php

namespace App\Http\Requests\App\Inventory;

use App\Enums\PermissionEnum;
use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class RejectStockAdjustmentRequest extends BaseInertiaFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()?->can(PermissionEnum::INVENTORY_ADJUST_VALIDATE->value) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'notes' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'notes.required' => 'Alasan penolakan wajib diisi.',
        ];
    }
}
