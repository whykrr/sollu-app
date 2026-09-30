<?php

declare(strict_types=1);

namespace App\Http\Requests\App\Transaction\Sales;

use App\Enums\PermissionEnum;
use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Support\Facades\Auth;

class DeleteSalesTransactionRequest extends BaseInertiaFormRequest
{
    public function authorize(): bool
    {
        $user = Auth::user();

        return ($user?->can(PermissionEnum::TRANSACTION_DELETE->value)
            || $user?->can(PermissionEnum::TRANSACTION_CANCEL->value)
            || $user?->can(PermissionEnum::TRANSACTION_CREATE->value)) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [];
    }
}
