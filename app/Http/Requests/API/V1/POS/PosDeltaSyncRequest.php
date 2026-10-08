<?php

declare(strict_types=1);

namespace App\Http\Requests\API\V1\POS;

use Carbon\Carbon;
use Illuminate\Foundation\Http\FormRequest;

class PosDeltaSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'updated_since' => [
                'required',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    try {
                        Carbon::parse($value);
                    } catch (\Throwable) {
                        $fail('Format updated_since tidak valid.');
                    }
                },
            ],
        ];
    }
}
