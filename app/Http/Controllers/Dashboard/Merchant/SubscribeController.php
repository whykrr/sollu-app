<?php

namespace App\Http\Controllers\Dashboard\Merchant;

use App\Constants\AuthorizationMessage;
use App\Constants\ResourceMessage;
use App\Enum\SubscriptionInvoice\Status;
use App\Http\Controllers\Controller;
use App\Http\Requests\Merchant\CreateInvoiceSubscribeRequest;
use App\Models\Outlet;
use App\Models\SubscriptionInvoice;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

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
                ->where('is_active', '=', true)
                ->latest()
                ->first()
                ->subscription_plan_id;
        }

        $plan    = SubscriptionPlan::find($plan_id);
        $outlets = Outlet::where('merchant_id', Auth::user()->merchant_id)->where('is_active', true)->get();

        return inertia('Dashboard/Merchant/Billing/Subscribe', [
            'plan'    => $plan,
            'outlets' => $outlets,
        ]);
    }

    public function store(CreateInvoiceSubscribeRequest $req)
    {
        DB::beginTransaction();
        try {
            $subscription = Auth::user()->merchant->subscriptions()->create([
                'subscription_plans_id' => $req->subscription_plan_id,
                'start_date'            => $req->start_date,
                'end_date'              => $req->period_end,
                'is_active'             => false,
            ]);

            $invoice                           = new SubscriptionInvoice($req->validated());
            $invoice->code                     = SubscriptionInvoice::generateCode();
            $invoice->status                   = Status::Unpaid;
            $invoice->due_date                 = Carbon::now()->addDay();
            $invoice->merchant_subscription_id = $subscription->id;

            $invoice->save();

            $invoice->items()->createMany($req->items);

            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        }

        // Auth::user()->merchant()->notify();

        return redirect()
            ->route('dashboard.merchant.invoices.show', $invoice->code)
            ->with('success', ResourceMessage::CREATE_SUCCESS);
    }


}
