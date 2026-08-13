<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Services\BillingEngine;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;

class SubscriptionController extends Controller
{
    protected $subscriptionService;

    protected $billingEngine;

    public function __construct(SubscriptionService $subscriptionService, BillingEngine $billingEngine)
    {
        $this->subscriptionService = $subscriptionService;
        $this->billingEngine = $billingEngine;
    }

    public function subscribe(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'payment_method' => 'required|in:midtrans,manual',
        ]);

        $business = $request->user()->business;
        $plan = SubscriptionPlan::findOrFail($request->plan_id);

        $subscription = $this->subscriptionService->subscribe($business, $plan, $request->billing_cycle);

        // Generate the initial invoice for active outlets
        if ($business->outlets()->where('is_active', true)->count() > 0) {
            $invoice = $this->billingEngine->generateRecurringInvoice($business, $subscription);

            if ($invoice->total_amount == 0) {
                $invoice->update([
                    'status' => 'paid',
                    'paid_at' => \Carbon\Carbon::now(),
                ]);
                $subscription->update([
                    'status' => 'active',
                ]);

                return redirect()->route('settings.billing.index')->with('success', 'Berhasil berlangganan paket.');
            }

            if ($request->payment_method === 'manual') {
                $invoice->payments()->create([
                    'amount' => $invoice->total_amount,
                    'payment_method' => 'manual',
                    'status' => 'pending',
                    'payment_reference' => "{$invoice->invoice_number}-MANUAL-".\Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(4)),
                ]);
            }

            return redirect()->route('settings.billing.invoices.show', $invoice->invoice_number)
                ->with('success', 'Berhasil berlangganan. Silakan selesaikan pembayaran tagihan awal.');
        }

        // Auto-activate since there are no active outlets
        $subscription->update([
            'status' => 'active',
        ]);

        return redirect()->route('settings.billing.index')->with('success', 'Berhasil berlangganan paket.');
    }

    public function changePlan(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'payment_method' => 'required|in:midtrans,manual',
        ]);

        $business = $request->user()->business;
        $plan = SubscriptionPlan::findOrFail($request->plan_id);

        $subscription = $this->subscriptionService->subscribe($business, $plan, $request->billing_cycle);

        $invoice = $this->billingEngine->generateRecurringInvoice($business, $subscription);

        if ($invoice->total_amount == 0) {
            $invoice->update([
                'status' => 'paid',
                'paid_at' => \Carbon\Carbon::now(),
            ]);
            $subscription->update([
                'status' => 'active',
            ]);

            return redirect()->route('settings.billing.index')->with('success', 'Paket berhasil diubah.');
        }

        if ($request->payment_method === 'manual') {
            $invoice->payments()->create([
                'amount' => $invoice->total_amount,
                'payment_method' => 'manual',
                'status' => 'pending',
                'payment_reference' => "{$invoice->invoice_number}-MANUAL-".\Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(4)),
            ]);
        }

        return redirect()->route('settings.billing.invoices.show', $invoice->invoice_number)
            ->with('success', 'Paket berhasil diubah. Silakan selesaikan pembayaran.');
    }

    public function cancel(Request $request)
    {
        $business = $request->user()->business;
        $subscription = $business->subscriptions()->where('status', 'active')->first();

        if ($subscription) {
            $this->subscriptionService->cancel($subscription);
        }

        return redirect()->route('settings.billing.index')->with('success', 'Berlangganan berhasil dibatalkan.');
    }
}
