<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use Illuminate\Http\Request;

class OutletController extends Controller
{
    /**
     * Retrieve all active outlets for the current business.
     * Note: Intentionally no authorization check to allow internal UI components (like filters) to fetch outlets freely.
     */
    public function index(Request $request)
    {
        $outlets = Outlet::currentBusiness()
            ->active()
            ->select('id', 'name', 'is_stock_frozen')
            ->orderBy('name')
            ->get();

        return response()->json($outlets);
    }

    public function salesSettings(Request $request)
    {
        $outletId = $request->input('outlet_id');
        $outlet = Outlet::currentBusiness()
            ->where('id', $outletId)
            ->first();

        $defaultDueDays = 14;
        $defaultTnc = "1. Pembayaran dilakukan sesuai tanggal jatuh tempo yang tertera pada faktur.\n2. Pembayaran via transfer ditujukan ke rekening resmi yang tertera.\n3. Barang yang sudah diterima dalam kondisi baik tidak dapat dikembalikan tanpa persetujuan tertulis.";
        $invoicePrefix = 'INV';

        if ($outlet) {
            $settings = $outlet->settings()
                ->where('category', 'sales')
                ->whereIn('key', [
                    'default_due_days_b2b',
                    'default_terms_and_conditions_b2b',
                    'b2b_invoice_prefix',
                    'allow_negative_stock_b2b',
                    'allow_custom_price_b2b',
                    'sales_channels_b2b',
                ])
                ->get();

            $dueDaysSetting = $settings->firstWhere('key', 'default_due_days_b2b');
            if ($dueDaysSetting) {
                $defaultDueDays = (int) $dueDaysSetting->value;
            }

            $tncSetting = $settings->firstWhere('key', 'default_terms_and_conditions_b2b');
            if ($tncSetting) {
                $defaultTnc = (string) $tncSetting->value;
            }

            $prefixSetting = $settings->firstWhere('key', 'b2b_invoice_prefix');
            if ($prefixSetting) {
                $invoicePrefix = (string) $prefixSetting->value;
            }
        }

        return response()->json([
            'data' => [
                'default_due_days_b2b' => $defaultDueDays,
                'default_terms_and_conditions_b2b' => $defaultTnc,
                'b2b_invoice_prefix' => $invoicePrefix,
            ],
        ]);
    }
}
