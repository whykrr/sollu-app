<?php

namespace App\Http\Controllers\Settings;

use App\Constants\AuthorizationMessage;
use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function index(Request $req)
    {
        if (! $req->user()->can('business.billing')) {
            throw new AuthorizationException(AuthorizationMessage::CANT_ACCESS_PAGE);
        }

        $business = $req->user()->business;

        $invoices = $business->invoices()
            ->with(['items'])
            ->latest();

        $activeSubscription = $business->subscriptions()->with('plan')->where('status', 'active')->first();

        return inertia('Settings/Billing/Index', [
            'subscription'  => $activeSubscription,
            'invoices'      => $invoices->paginate($req->get('perpage', 20)),
        ]);
    }

    public function plans(Request $req)
    {
        if (! $req->user()->can('business.billing')) {
            throw new AuthorizationException(AuthorizationMessage::CANT_ACCESS_PAGE);
        }

        $business     = $req->user()->business;
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

        return inertia('Settings/Billing/Plans', [
            'subscription'  => $subscription,
            'plans'         => $plans,
            'invoice'       => $invoice,
        ]);
    }

    public function checkout(Request $req, $plan_id)
    {
        if (! $req->user()->can('business.billing')) {
            throw new AuthorizationException(AuthorizationMessage::CANT_ACCESS_PAGE);
        }

        $business     = $req->user()->business;
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
                ->with('error', 'Anda masih memiliki tagihan yang belum dibayar.');
        }

        $plan = SubscriptionPlan::findOrFail($plan_id);

        return inertia('Settings/Billing/Checkout', [
            'subscription' => $subscription,
            'plan'         => $plan,
        ]);
    }
}
