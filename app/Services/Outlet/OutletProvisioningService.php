<?php

namespace App\Services\Outlet;

use App\Models\Master\OutletPaymentMethod;
use App\Models\Master\PaymentMethod;
use App\Models\Outlet;
use App\Models\OutletOperationalHour;
use App\Models\OutletSetting;
use Illuminate\Support\Str;

class OutletProvisioningService
{
    /**
     * Provision all default settings and master data for an outlet.
     */
    public function provisionAll(Outlet $outlet): void
    {
        $this->provisionPaymentMethods($outlet);
        $this->provisionReceiptSettings($outlet);
        $this->provisionFinancialSettings($outlet);
        $this->provisionOperationalHours($outlet);
    }

    /**
     * Provision default payment methods (Tunai, QRIS) for business and attach to outlet.
     */
    public function provisionPaymentMethods(Outlet $outlet): void
    {
        $businessId = $outlet->business_id;

        $defaultMethods = [
            [
                'name' => 'Tunai',
                'type' => 'cash',
                'sort_order' => 1,
            ],
            [
                'name' => 'QRIS',
                'type' => 'qris',
                'sort_order' => 2,
            ],
        ];

        foreach ($defaultMethods as $item) {
            $paymentMethod = PaymentMethod::firstOrCreate(
                [
                    'business_id' => $businessId,
                    'type' => $item['type'],
                ],
                [
                    'id' => (string) Str::uuid(),
                    'name' => $item['name'],
                    'sort_order' => $item['sort_order'],
                ]
            );

            // Attach to outlet if not already attached
            OutletPaymentMethod::firstOrCreate(
                [
                    'payment_method_id' => $paymentMethod->id,
                    'outlet_id' => $outlet->id,
                ],
                [
                    'id' => (string) Str::uuid(),
                    'is_enabled' => true,
                ]
            );
        }
    }

    /**
     * Provision default receipt layout settings (all 22 fields matching UI defaults).
     */
    public function provisionReceiptSettings(Outlet $outlet): void
    {
        $defaultLayoutConfig = [
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

        // 1. Layout config
        OutletSetting::firstOrCreate(
            [
                'outlet_id' => $outlet->id,
                'category' => 'receipt',
                'key' => 'layout_config',
            ],
            [
                'id' => (string) Str::uuid(),
                'value' => $defaultLayoutConfig,
            ]
        );

        // 2. Legacy POS format keys for backward compatibility
        OutletSetting::firstOrCreate(
            [
                'outlet_id' => $outlet->id,
                'category' => 'pos',
                'key' => 'receipt_format',
            ],
            [
                'id' => (string) Str::uuid(),
                'value' => 'standard',
            ]
        );

        OutletSetting::firstOrCreate(
            [
                'outlet_id' => $outlet->id,
                'category' => 'pos',
                'key' => 'auto_print',
            ],
            [
                'id' => (string) Str::uuid(),
                'value' => 1,
            ]
        );
    }

    /**
     * Provision default financial settings (tax, service fee, rounding).
     */
    public function provisionFinancialSettings(Outlet $outlet): void
    {
        $financialDefaults = [
            'tax' => 0.0,
            'service_fee' => 0.0,
            'tax_included_in_price' => false,
            'rounding_enabled' => false,
            'rounding_mode' => 'nearest',
        ];

        foreach ($financialDefaults as $key => $defaultValue) {
            OutletSetting::firstOrCreate(
                [
                    'outlet_id' => $outlet->id,
                    'category' => 'financial',
                    'key' => $key,
                ],
                [
                    'id' => (string) Str::uuid(),
                    'value' => $defaultValue,
                ]
            );
        }
    }

    /**
     * Provision default operational hours (7 days: 08:00 - 22:00).
     */
    public function provisionOperationalHours(Outlet $outlet): void
    {
        for ($day = 0; $day <= 6; $day++) {
            OutletOperationalHour::firstOrCreate(
                [
                    'outlet_id' => $outlet->id,
                    'day_of_week' => $day,
                ],
                [
                    'id' => (string) Str::uuid(),
                    'open_time' => '08:00',
                    'close_time' => '22:00',
                    'is_closed' => false,
                ]
            );
        }
    }
}
