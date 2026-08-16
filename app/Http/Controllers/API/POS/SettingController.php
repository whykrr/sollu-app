<?php

namespace App\Http\Controllers\API\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\API\POS\UpdatePosPrinterSettingRequest;
use App\Models\OutletSetting;
use Illuminate\Support\Str;

class SettingController extends Controller
{
    public function updatePrinter(UpdatePosPrinterSettingRequest $request)
    {
        $device = $request->user();
        $outlet = $device->outlet;

        if (! $outlet) {
            return $this->errorResponse('Outlet tidak ditemukan untuk perangkat ini.', [], 404);
        }

        $validated = $request->validated();
        $paperSize = $validated['paper_size'];

        // 1. Update receipt layout_config
        $receiptRow = OutletSetting::firstOrNew([
            'outlet_id' => $outlet->id,
            'category' => 'receipt',
            'key' => 'layout_config',
        ]);

        $currentConfig = $receiptRow->value ?? [];
        if (! is_array($currentConfig)) {
            $currentConfig = [];
        }

        $currentConfig['paper_size'] = $paperSize;
        $receiptRow->id = $receiptRow->id ?? (string) Str::uuid();
        $receiptRow->value = $currentConfig;
        $receiptRow->save();

        // 2. Sync legacy POS format keys for backward compatibility
        OutletSetting::updateOrCreate(
            [
                'outlet_id' => $outlet->id,
                'category' => 'pos',
                'key' => 'receipt_format',
            ],
            [
                'id' => (string) Str::uuid(),
                'value' => $paperSize === '80mm' ? 'large' : 'standard',
            ]
        );

        if (! empty($validated['printer_mac_address'])) {
            OutletSetting::updateOrCreate(
                [
                    'outlet_id' => $outlet->id,
                    'category' => 'pos',
                    'key' => 'printer_mac_address',
                ],
                [
                    'id' => (string) Str::uuid(),
                    'value' => $validated['printer_mac_address'],
                ]
            );
        }

        return $this->successResponse([
            'paper_size' => $paperSize,
            'receipt_format' => $paperSize === '80mm' ? 'large' : 'standard',
        ], 'Pengaturan printer berhasil disinkronkan ke database pusat.');
    }
}
