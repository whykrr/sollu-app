<?php

namespace App\Http\Requests\App\Promotion;

use App\Enums\PermissionEnum;
use App\Enums\PromotionApplicationMode;
use App\Enums\PromotionDiscountType;
use App\Enums\PromotionStatus;
use App\Enums\PromotionTargetScope;
use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Validation\Rule;

class GetPromotionRequest extends BaseInertiaFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::PROMO_VIEW->value) ?? false;
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
            'status' => ['nullable', Rule::enum(PromotionStatus::class)],
            'target_scope' => ['nullable', Rule::enum(PromotionTargetScope::class)],
            'target' => ['nullable', Rule::enum(PromotionTargetScope::class)],
            'target_type' => ['nullable', Rule::enum(PromotionTargetScope::class)],
            'discount_type' => ['nullable', Rule::enum(PromotionDiscountType::class)],
            'promo_type' => ['nullable', Rule::enum(PromotionDiscountType::class)],
            'type' => ['nullable', Rule::enum(PromotionDiscountType::class)],
            'application_mode' => ['nullable', Rule::enum(PromotionApplicationMode::class)],
            'mode' => ['nullable', Rule::enum(PromotionApplicationMode::class)],
            'outlet' => ['nullable', 'string'],
            'outlet_id' => ['nullable', 'string'],
            'sort' => ['nullable', 'string'],
            'direction' => ['nullable', 'in:asc,desc'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
            'perpage' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
