<?php

declare(strict_types=1);

namespace App\Services\Pos;

use App\Models\Outlet;
use App\Services\App\Transaction\TransactionService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class PosSettingsPayloadBuilder
{
    public function __construct(
        private readonly TransactionService $transactionService,
    ) {}

    public function resolveLogoUrl(Outlet $outlet): ?string
    {
        $business = $outlet->business;
        $logoUrl = null;
        if ($outlet->logo_url) {
            $logoUrl = str_starts_with($outlet->logo_url, 'http') ? $outlet->logo_url : url($outlet->logo_url);
        } elseif ($business?->logo) {
            $logoUrl = url(Storage::url($business->logo));
        }

        return $logoUrl;
    }

    public function buildOutletProfile(Outlet $outlet): array
    {
        return [
            'id' => $outlet->id,
            'name' => $outlet->name,
            'initial' => $this->transactionService->resolveOutletInitial($outlet),
            'address' => $outlet->address,
            'phone' => $outlet->phone,
            'email' => $outlet->email,
            'logo_url' => $this->resolveLogoUrl($outlet),
        ];
    }

    /**
     * @param  Collection<int, mixed>  $outletSettings
     * @return array<string, mixed>
     */
    public function buildStructuredSettings(Outlet $outlet, Collection $outletSettings): array
    {
        $financialTax = (float) ($outletSettings->firstWhere('key', 'tax')?->value ?? 0.0);
        $financialServiceFee = (float) ($outletSettings->firstWhere('key', 'service_fee')?->value ?? 0.0);
        $taxIncluded = (bool) ($outletSettings->firstWhere('key', 'tax_included_in_price')?->value ?? false);
        $roundingEnabled = (bool) ($outletSettings->firstWhere('key', 'rounding_enabled')?->value ?? false);
        $roundingMode = (string) ($outletSettings->firstWhere('key', 'rounding_mode')?->value ?? 'nearest');

        $receiptSetting = $outletSettings->where('category', 'receipt')->firstWhere('key', 'layout_config')?->value ?? [
            'paper_size' => '58mm',
            'auto_print' => true,
            'print_kitchen_copy' => false,
            'print_checker_copy' => false,
            'show_logo' => true,
            'custom_header_title' => null,
            'header_notes' => 'Terima kasih atas kunjungan Anda!',
            'show_address' => true,
            'show_phone' => true,
            'show_email' => false,
            'show_cashier_name' => true,
            'show_customer_name' => true,
            'show_order_type' => true,
            'show_modifiers' => true,
            'show_item_notes' => true,
            'show_tax_detail' => true,
            'show_service_charge' => false,
            'footer_notes' => 'Barang yang sudah dibeli tidak dapat ditukar atau dikembalikan.',
            'social_media_info' => null,
            'wifi_info' => null,
            'show_qr_code' => false,
            'qr_type' => 'invoice',
        ];

        $receiptSetting['logo_url'] = $this->resolveLogoUrl($outlet);

        $posOrderTypes = $outletSettings->where('category', 'pos')->firstWhere('key', 'pos_order_types')?->value ?? ['walk_in', 'dine_in', 'take_away'];
        if (! is_array($posOrderTypes) || empty($posOrderTypes)) {
            $posOrderTypes = ['walk_in', 'dine_in', 'take_away'];
        }

        return [
            'tax_percentage' => $financialTax,
            'service_charge_percentage' => $financialServiceFee,
            'tax_included_in_price' => $taxIncluded,
            'rounding_enabled' => $roundingEnabled,
            'rounding_mode' => $roundingMode,
            'allow_negative_stock' => (bool) ($outletSettings->firstWhere('key', 'allow_negative_stock')?->value ?? true),
            'pos_order_types' => $posOrderTypes,
            'receipt' => $receiptSetting,
        ];
    }
}
