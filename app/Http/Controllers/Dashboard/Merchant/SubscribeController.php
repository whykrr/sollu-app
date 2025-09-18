<?php

namespace App\Http\Controllers\Dashboard\Merchant;

use App\Constants\AuthorizationMessage;
use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\SubscriptionPlan;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SubscribeController extends Controller
{
    public function index(Request $req)
    {
        if (! $req->user()->can('merchant.billing')) {
            throw new AuthorizationException(AuthorizationMessage::CANT_ACCESS_PAGE);
        }

        $plan_id = $req->get('plan');
        if ($plan_id === null) {
            $plan_id = Auth::user()->merchant
                ->subscriptions()
                ->latest()
                ->first()
                ->subscription_plan_id;
        }

        $plan    = SubscriptionPlan::find($plan_id);
        $outlets = Outlet::where('merchant_id', Auth::user()->merchant_id)->get();

        return inertia('Dashboard/Merchant/Billing/Subscribe', [
            'plan'    => $plan,
            'outlets' => $outlets,
        ]);
    }

    public function store(Request $req)
    {

    }
}
