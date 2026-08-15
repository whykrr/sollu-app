<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\Settings\PaymentMethodService;
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
                'sort_order' => $pm->sort_order,
                'is_enabled' => $pm->outletPaymentMethods->firstWhere('outlet_id', $outletId)?->is_enabled ?? true,
            ]),
        ]);
    }
}
