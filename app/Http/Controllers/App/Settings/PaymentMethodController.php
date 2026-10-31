<?php

namespace App\Http\Controllers\App\Settings;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\PaymentMethodType;
use App\Enums\PaymentReferencePolicy;
use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Settings\PaymentMethod\StorePaymentMethodRequest;
use App\Http\Requests\App\Settings\PaymentMethod\UpdatePaymentMethodRequest;
use App\Models\Master\PaymentMethod;
use App\Models\Outlet;
use App\Services\App\Settings\PaymentMethodService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PaymentMethodController extends Controller
{
    public function __construct(
        protected PaymentMethodService $paymentMethodService
    ) {}

    public function index(Request $request): Response
    {
        $this->authorize(PermissionEnum::SETTING_PAYMENT->value);

        $businessId = $request->user()->business_id;
        $filters = $request->only(['search', 'type']);

        $paymentMethods = $this->paymentMethodService->getPaginated(
            $businessId,
            $filters,
            (int) $request->get('perpage', 15)
        );

        $outlets = Outlet::where('business_id', $businessId)
            ->where('is_active', true)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $presetProviders = [
            'BCA', 'Mandiri', 'BRI', 'BNI', 'BSI', 'CIMB Niaga',
            'GoPay', 'OVO', 'DANA', 'ShopeePay', 'LinkAja',
        ];

        return Inertia::render('Settings/PaymentMethod/Index', [
            'paymentMethods' => $paymentMethods,
            'outlets' => $outlets,
            'types' => PaymentMethodType::options(),
            'referencePolicies' => PaymentReferencePolicy::options(),
            'presetProviders' => $presetProviders,
            'filters' => $filters,
        ]);
    }

    public function store(StorePaymentMethodRequest $request): RedirectResponse
    {
        $this->paymentMethodService->create(
            $request->validated(),
            $request->user()->business_id
        );

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::CREATE_SUCCESS
        );
    }

    public function update(UpdatePaymentMethodRequest $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        if ($paymentMethod->business_id !== $request->user()->business_id) {
            abort(403);
        }

        try {
            $this->paymentMethodService->update($paymentMethod, $request->validated());

            return redirect()->back()->with(
                FlashDataVariable::SUCCESS->value,
                ResourceMessage::UPDATE_SUCCESS
            );
        } catch (Exception $e) {
            return redirect()->back()->with(
                FlashDataVariable::FAILED->value,
                $e->getMessage()
            );
        }
    }

    public function toggleActive(Request $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $this->authorize(PermissionEnum::SETTING_PAYMENT->value);

        if ($paymentMethod->business_id !== $request->user()->business_id) {
            abort(403);
        }

        $this->paymentMethodService->toggleActive($paymentMethod);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );
    }

    public function toggleOutlet(Request $request, PaymentMethod $paymentMethod, Outlet $outlet): RedirectResponse
    {
        $this->authorize(PermissionEnum::SETTING_PAYMENT->value);

        if ($paymentMethod->business_id !== $request->user()->business_id || $outlet->business_id !== $request->user()->business_id) {
            abort(403);
        }

        $isEnabled = $request->has('is_enabled') ? $request->boolean('is_enabled') : null;
        $this->paymentMethodService->toggleOutletStatus($paymentMethod, $outlet->id, $isEnabled);

        return redirect()->back()->with(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );
    }

    public function destroy(Request $request, PaymentMethod $paymentMethod): RedirectResponse
    {
        $this->authorize(PermissionEnum::SETTING_PAYMENT->value);

        if ($paymentMethod->business_id !== $request->user()->business_id) {
            abort(403);
        }

        try {
            $this->paymentMethodService->delete($paymentMethod);

            return redirect()->back()->with(
                FlashDataVariable::SUCCESS->value,
                ResourceMessage::PURGE_SUCCESS
            );
        } catch (Exception $e) {
            return redirect()->back()->with(
                FlashDataVariable::FAILED->value,
                $e->getMessage()
            );
        }
    }
}
