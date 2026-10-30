<?php

namespace App\Http\Requests\App\Inventory\Transfer;

use App\Enums\PermissionEnum;
use App\Http\Requests\BaseInertiaFormRequest;

class VoidStockTransferRequest extends BaseInertiaFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::INVENTORY_TRANSFER_VALIDATE->value) ?? false;
    }

    public function rules(): array
    {
        return [
            'void_reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'void_reason.required' => 'Alasan pembatalan mutasi stok wajib diisi.',
            'void_reason.max' => 'Alasan pembatalan tidak boleh melebihi 500 karakter.',
        ];
    }
}
