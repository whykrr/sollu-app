<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\POS;

use Illuminate\Foundation\Http\FormRequest;

class VoidPosTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'supervisor_id' => ['nullable', 'string', 'uuid'],
            'supervisor_pin' => ['nullable', 'string'],
            'reason' => ['required', 'string', 'max:500'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
