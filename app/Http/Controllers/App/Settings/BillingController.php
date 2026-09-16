<?php

namespace App\Http\Controllers\App\Settings;

use App\Constants\FlashDataVariable;
use App\Enums\PermissionEnum;
use App\Enums\SubscriptionInvoice\Status;
use App\Http\Controllers\Controller;
use App\Http\Requests\App\Settings\Billing\GetBillingInvoiceRequest;
use App\Models\Invoice;
use App\Models\Master\SubscriptionManualPaymentMethod;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function index(GetBillingInvoiceRequest $request): Response
    {
        $this->authorize(PermissionEnum::BUSINESS_BILLING->value);

        $business = $request->user()->business;

        $invoices = $business->invoices()
            ->with(['paymentManualValidation:id,invoice_id,validation_status'])
            ->filters($request->validated())
            ->sortable($request->validated('sort', 'created_at'), $request->validated('direction', 'desc'))
            ->paginate($request->validated('perpage', 20))
            ->appends($request->query());

        $activeSubscription = $business->subscriptions()
            ->with('plan')
            ->where('status', 'active')
            ->first();

        $pendingInvoice = $business->invoices()
            ->where('status', Status::Open)
            ->where('due_date', '>', Carbon::now())
            ->latest()
            ->first();

        return Inertia::render('Settings/Billing/Index', [
            'subscription' => $activeSubscription,
            'pendingInvoice' => $pendingInvoice,
            'invoices' => $invoices,
            'params' => $request->validated(),
        ]);
    }

    public function plans(Request $request): Response
    {
        $this->authorize(PermissionEnum::BUSINESS_BILLING->value);

        $business = $request->user()->business;
        $subscription = $business->subscriptions()
            ->where('status', 'active')
            ->with(['plan'])
            ->latest()
            ->first();

        $invoice = Invoice::query()
            ->where('business_id', $business->id)
            ->where('status', Status::Open)
            ->where('due_date', '>', Carbon::now())
            ->first();

        $allCachedPlans = SubscriptionPlan::getAllCached();
        $businessId = $business->id;

        $assignedCustomPlans = $allCachedPlans->filter(function ($plan) use ($businessId) {
            return $plan->is_active && $plan->business_id === $businessId;
        })->values();

        $isCustomCatalog = $assignedCustomPlans->isNotEmpty();

        if ($isCustomCatalog) {
            $plans = $allCachedPlans->filter(function ($plan) use ($businessId, $subscription) {
                return $plan->is_active && ($plan->business_id === $businessId || ($subscription?->plan_id && $plan->id === $subscription->plan_id));
            })->values();
        } else {
            $plans = $allCachedPlans->filter(function ($plan) use ($subscription) {
                return $plan->is_active && $plan->business_id === null && ($plan->is_public || ($subscription?->plan_id && $plan->id === $subscription->plan_id));
            })->values();
        }

        return Inertia::render('Settings/Billing/Plans', [
            'subscription' => $subscription,
            'plans' => $plans,
            'invoice' => $invoice,
            'isCustomCatalog' => $isCustomCatalog,
        ]);
    }

    public function checkout(Request $request, string $plan_id): Response|RedirectResponse
    {
        $this->authorize(PermissionEnum::BUSINESS_BILLING->value);

        $business = $request->user()->business;
        $subscription = $business->subscriptions()
            ->where('status', 'active')
            ->with(['plan'])
            ->latest()
            ->first();

        $invoice = Invoice::query()
            ->where('business_id', $business->id)
            ->where('status', Status::Open)
            ->where('due_date', '>', Carbon::now())
            ->first();

        if ($invoice) {
            return redirect()->route('settings.billing.plans')
                ->with(FlashDataVariable::WARNING->value, 'Anda masih memiliki tagihan yang belum dibayar.');
        }

        $plan = SubscriptionPlan::findOrFail($plan_id);

        if (! $plan->is_active) {
            return redirect()->route('settings.billing.plans')
                ->with(FlashDataVariable::WARNING->value, 'Paket langganan ini sudah tidak aktif.');
        }

        if ($plan->business_id !== null && $plan->business_id !== $business->id) {
            return redirect()->route('settings.billing.plans')
                ->with(FlashDataVariable::WARNING->value, 'Anda tidak memiliki akses ke paket langganan ini.');
        }

        $assignedCustomPlansCount = SubscriptionPlan::getAllCached()
            ->filter(fn ($p) => $p->is_active && $p->business_id === $business->id)
            ->count();

        if ($assignedCustomPlansCount > 0 && $plan->business_id !== $business->id && $subscription?->plan_id !== $plan->id) {
            return redirect()->route('settings.billing.plans')
                ->with(FlashDataVariable::WARNING->value, 'Bisnis Anda terikat pada paket kustom khusus. Silakan pilih paket yang tersedia untuk akun Anda.');
        }

        $manualPaymentMethods = SubscriptionManualPaymentMethod::where('is_active', true)
            ->orderBy('bank_name')
            ->get();

        return Inertia::render('Settings/Billing/Checkout', [
            'subscription' => $subscription,
            'plan' => $plan,
            'isRenewal' => $request->boolean('is_renewal'),
            'manualPaymentMethods' => $manualPaymentMethods,
            'isMidtransEnabled' => SystemSetting::isMidtransEnabled(),
        ]);
    }
}
