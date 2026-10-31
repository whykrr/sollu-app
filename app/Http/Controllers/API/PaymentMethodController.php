<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\App\Settings\PaymentMethodService;
use BackedEnum;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentMethodController extends Controller
{
    public function __construct(
        protected PaymentMethodService $paymentMethodService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $businessId = $request->user()->business_id;
        $outletId = $request->input('outlet_id');

        $paymentMethods = $this->paymentMethodService->getForInternalApi($businessId, $outletId);

        return response()->json([
            'data' => $paymentMethods->map(fn ($pm) => [
                'id' => $pm->id,
                'name' => $pm->name,
                'type' => $pm->type,
                'provider' => $pm->provider,
                'account_number' => $pm->account_number,
                'account_name' => $pm->account_name,
                'reference_policy' => $pm->reference_policy instanceof BackedEnum ? $pm->reference_policy->value : (string) ($pm->reference_policy ?? 'optional'),
                'open_cash_drawer' => (bool) $pm->open_cash_drawer,
                'instructions' => $pm->instructions,
                'qr_image_url' => $pm->qr_image_url,
                'is_system' => (bool) $pm->is_system,
                'is_active' => (bool) $pm->is_active,
                'is_enabled' => $pm->outletPaymentMethods->firstWhere('outlet_id', $outletId)?->is_enabled ?? true,
            ]),
        ]);
    }
}
