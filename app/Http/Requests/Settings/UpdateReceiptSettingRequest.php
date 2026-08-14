<?php

namespace App\Http\Requests\Settings;

use App\Enums\PermissionEnum;
use App\Http\Requests\BaseInertiaFormRequest;
use Illuminate\Validation\Rule;

class UpdateReceiptSettingRequest extends BaseInertiaFormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(PermissionEnum::SETTING_RECEIPT->value)
            || $this->user()?->can(PermissionEnum::OUTLET_UPDATE->value)
            || $this->user()?->can(PermissionEnum::SETTING_ALL->value);
    }

    public function rules(): array
    {
        $businessId = $this->user()->business_id;

        return [
            'outlet_id' => [
                'required',
                'uuid',
                Rule::exists('outlets', 'id')->where('business_id', $businessId),
            ],
            'paper_size' => ['required', 'string', 'in:58mm,80mm'],
            'show_logo' => ['required', 'boolean'],
            'custom_header_title' => ['nullable', 'string', 'max:100'],
            'header_notes' => ['nullable', 'string', 'max:255'],
            'show_address' => ['required', 'boolean'],
            'show_phone' => ['required', 'boolean'],
            'show_email' => ['required', 'boolean'],
            'show_cashier_name' => ['required', 'boolean'],
            'show_customer_name' => ['required', 'boolean'],
            'show_order_type' => ['required', 'boolean'],
            'show_modifiers' => ['required', 'boolean'],
            'show_item_notes' => ['required', 'boolean'],
            'show_tax_detail' => ['required', 'boolean'],
            'show_service_charge' => ['required', 'boolean'],
            'footer_notes' => ['nullable', 'string', 'max:255'],
            'social_media_info' => ['nullable', 'string', 'max:100'],
            'wifi_info' => ['nullable', 'string', 'max:100'],
            'show_qr_code' => ['required', 'boolean'],
            'qr_type' => ['nullable', 'string', 'in:feedback,payment,invoice'],
            'auto_print' => ['required', 'boolean'],
            'print_kitchen_copy' => ['required', 'boolean'],
            'print_checker_copy' => ['required', 'boolean'],
        ];
    }
}
