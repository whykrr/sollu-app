<?php

namespace App\Http\Requests\App\Promotion;

use App\Enums\PermissionEnum;
use App\Enums\PromoStatus;
use App\Enums\PromoTarget;
use App\Enums\PromoType;
use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class GetPromotionRequest extends BaseInertiaFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::user()?->can(PermissionEnum::PROMO_VIEW->value) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::enum(PromoStatus::class)],
            'promo_type' => ['nullable', Rule::enum(PromoType::class)],
            'type' => ['nullable', Rule::enum(PromoType::class)],
            'target_type' => ['nullable', Rule::enum(PromoTarget::class)],
            'target' => ['nullable', Rule::enum(PromoTarget::class)],
            'outlet' => ['nullable', 'string'],
            'sort' => ['nullable', 'string'],
            'direction' => ['nullable', 'in:asc,desc'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'perpage' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
