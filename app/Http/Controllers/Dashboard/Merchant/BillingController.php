<?php

namespace App\Http\Controllers\Dashboard\Merchant;

use App\Constants\AuthorizationMessage;
use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    public function index(Request $req)
    {
        if (! $req->user()->can('merchant.billing')) {
            throw new AuthorizationException(AuthorizationMessage::CANT_ACCESS_PAGE);
        }

        return inertia('Dashboard/Merchant/Billing/Index');
    }

    public function plans(Request $req)
    {
        if (! $req->user()->can('merchant.billing')) {
            throw new AuthorizationException(AuthorizationMessage::CANT_ACCESS_PAGE);
        }


        $billing_cycle = $req->get('billing_cycle', 'monthly');

        $plans = SubscriptionPlan::where('is_trial', false)
            ->where('billing_cycle', $billing_cycle)
            ->orderBy('price', 'asc')
            ->get();

        return inertia('Dashboard/Merchant/Billing/Plans', [
            'plans'         => $plans,
            'billing_cycle' => $billing_cycle,
        ]);
    }
}
