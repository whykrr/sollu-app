<?php

namespace App\Http\Requests\App\Transaction\Shift;

use App\Enums\DatePresetEnum;
use App\Enums\ShiftStatus;
use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Validation\Rule;

class GetShiftRequest extends BaseInertiaFormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('transaction.view');
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(ShiftStatus::class)],
            'preset' => ['nullable', Rule::enum(DatePresetEnum::class)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date'],
            'outlet_id' => ['nullable', 'uuid'],
            'sort' => ['nullable', 'string'],
            'direction' => ['nullable', 'in:asc,desc'],
            'perpage' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }
}
