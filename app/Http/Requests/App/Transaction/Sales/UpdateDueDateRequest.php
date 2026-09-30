<?php

declare(strict_types=1);

namespace App\Http\Requests\App\Transaction\Sales;

use App\Enums\PermissionEnum;
use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;

class UpdateDueDateRequest extends BaseInertiaFormRequest
{
    public function authorize(): bool
    {
        return Auth::user()?->can(PermissionEnum::TRANSACTION_EDIT_DUE_DATE->value) ?? false;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'due_date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'due_date.required' => 'Tanggal jatuh tempo baru wajib diisi.',
            'due_date.date' => 'Format tanggal tidak valid.',
        ];
    }
}
