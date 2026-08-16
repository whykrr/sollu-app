<?php

namespace App\Http\Controllers\Settings;

use App\Constants\FlashDataVariable;
use App\Enums\PermissionEnum;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\SubscriptionPlan;
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
            ->with(['items'])
            ->latest();

        $activeSubscription = $business->subscriptions()->with('plan')->where('status', 'active')->first();

        return Inertia::render('Settings/Billing/Index', [
            'subscription' => $activeSubscription,
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

        $plans = SubscriptionPlan::orderBy('price_per_outlet', 'asc')->get();

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

        return Inertia::render('Settings/Billing/Checkout', [
            'subscription' => $subscription,
            'plan' => $plan,
        ]);
    }
}
