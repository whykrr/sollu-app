<?php

namespace App\Http\Requests\App\Reports;

use App\Enums\DatePresetEnum;
use App\Enums\PermissionEnum;
use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class ExportReportRequest extends BaseInertiaFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::REPORT_EXPORT->value)
            || $this->user()?->is_root_user;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'period' => ['nullable', 'string', Rule::enum(DatePresetEnum::class)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'outlet' => ['nullable', 'string', 'uuid'],
        ];
    }
}
