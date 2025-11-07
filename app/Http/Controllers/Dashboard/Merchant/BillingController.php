<?php

namespace App\Http\Controllers\Dashboard\Merchant;

use App\Constants\AuthorizationMessage;
use App\Enum\SubscriptionInvoice\Status;
use App\Http\Controllers\Controller;
use App\Models\MerchantSubscriptions;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function index(Request $req)
    {
        if (! $req->user()->can('merchant.billing')) {
            throw new AuthorizationException(AuthorizationMessage::CANT_ACCESS_PAGE);
        }

        $subscriptions = MerchantSubscriptions::currentMerchant()
            ->with(['plan'])
            ->latest();

        return inertia('Dashboard/Merchant/Billing/Index', [
            'subscription'  => $subscriptions->first(),
            'subscriptions' => $subscriptions->paginate($req->get('perpage', 20))
            ,
        ]);
    }

    public function plans(Request $req)
    {
        if (! $req->user()->can('merchant.billing')) {
            throw new AuthorizationException(AuthorizationMessage::CANT_ACCESS_PAGE);
        }

        $subscription = $req->user()->merchant->subscriptions()
            ->where('is_active', '=', true)
            ->latest()
            ->first();

        $cycle = $subscription->plan->billing_cycle;

        $invoice = SubscriptionInvoice::whereStatus(Status::Unpaid)
            ->where('due_date', '>', Carbon::now()->format('Y-m-d'))->first();

        $billing_cycle = $req->get('billing_cycle', $cycle);

        $plans = SubscriptionPlan::where('is_trial', false)
            ->where('billing_cycle', $billing_cycle)
            ->orderBy('price', 'asc')
            ->get();

        return inertia('Dashboard/Merchant/Billing/Plans', [
            'subscription'  => $subscription,
            'plans'         => $plans,
            'billing_cycle' => $billing_cycle,
            'invoice'       => $invoice,
        ]);
    }
}
