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
            'maxOutlets' => $business->maxOutletsAllowed(),
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

        $plans = SubscriptionPlan::query()
            ->where('is_active', true)
            ->where(function ($query) use ($subscription) {
                $query->where('is_public', true);
                if ($subscription?->plan_id) {
                    $query->orWhere('id', $subscription->plan_id);
                }
            })
            ->orderBy('price_per_outlet', 'asc')
            ->get();

        return Inertia::render('Settings/Billing/Plans', [
            'subscription' => $subscription,
            'plans' => $plans,
            'invoice' => $invoice,
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
