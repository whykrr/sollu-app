<?php

namespace App\Http\Requests\API\POS;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePosPrinterSettingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'paper_size' => ['required', 'string', 'in:58mm,80mm'],
            'printer_name' => ['nullable', 'string', 'max:255'],
            'printer_mac_address' => ['nullable', 'string', 'max:255'],
            'auto_cut' => ['nullable', 'boolean'],
            'open_cash_drawer' => ['nullable', 'boolean'],
        ];
    }
}
