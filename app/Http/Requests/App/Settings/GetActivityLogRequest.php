<?php

namespace App\Http\Requests\App\Settings;

use App\Enums\AuditModuleEnum;
use App\Enums\DatePresetEnum;
use App\Enums\PermissionEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class GetActivityLogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()?->can(PermissionEnum::SETTING_AUDIT->value) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'preset' => ['nullable', 'string', Rule::enum(DatePresetEnum::class)],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'module' => ['nullable', 'string', Rule::enum(AuditModuleEnum::class)],
            'action' => ['nullable', 'string', 'max:100'],
            'outlet_id' => ['nullable', 'uuid'],
            'causer_id' => ['nullable', 'uuid'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'sort' => ['nullable', 'string', 'in:created_at,module,action'],
            'direction' => ['nullable', 'string', 'in:asc,desc'],
        ];
    }
}
