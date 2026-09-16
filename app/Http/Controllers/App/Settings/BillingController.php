<?php

namespace App\Http\Controllers\App\Settings;

use App\Constants\FlashDataVariable;
use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingController extends Controller
{
    public function index(Request $req): Response
    {
        $this->authorize(PermissionEnum::BUSINESS_BILLING->value);

        $business = $req->user()->business;

        $invoices = $business->invoices()
            ->with(['items', 'paymentManualValidation'])
            ->latest();

        $activeSubscription = $business->subscriptions()->with('plan')->where('status', 'active')->first();

        $pendingInvoice = $business->invoices()
            ->where('status', 'open')
            ->where('due_date', '>', Carbon::now())
            ->latest()
            ->first();

        return Inertia::render('Settings/Billing/Index', [
            'subscription' => $activeSubscription,
            'pendingInvoice' => $pendingInvoice,
            'invoices' => $invoices->paginate($req->get('perpage', 20)),
        ]);
    }

    public function plans(Request $req): Response
    {
        $this->authorize(PermissionEnum::BUSINESS_BILLING->value);

        $business = $req->user()->business;
        $subscription = $business->subscriptions()
            ->where('status', 'active')
            ->with(['plan'])
            ->latest()
            ->first();

        $invoice = Invoice::where('business_id', $business->id)
            ->where('status', 'open')
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

    public function checkout(Request $req, $plan_id)
    {
        $this->authorize(PermissionEnum::BUSINESS_BILLING->value);

        $business = $req->user()->business;
        $subscription = $business->subscriptions()
            ->where('status', 'active')
            ->with(['plan'])
            ->latest()
            ->first();

        $invoice = Invoice::where('business_id', $business->id)
            ->where('status', 'open')
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

        $manualPaymentMethods = \App\Models\Master\SubscriptionManualPaymentMethod::where('is_active', true)
            ->orderBy('bank_name')
            ->get();

        return Inertia::render('Settings/Billing/Checkout', [
            'subscription' => $subscription,
            'plan' => $plan,
            'isRenewal' => $req->boolean('is_renewal'),
            'manualPaymentMethods' => $manualPaymentMethods,
            'isMidtransEnabled' => SystemSetting::isMidtransEnabled(),
        ]);
    }
}
