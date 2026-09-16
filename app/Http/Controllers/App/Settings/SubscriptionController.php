<?php

namespace App\Http\Controllers\App\Settings;

use App\Constants\FlashDataVariable;
use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\SystemSetting;
use App\Services\App\Subscription\BillingEngine;
use App\Services\App\Subscription\SubscriptionService;
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

        if ($request->payment_method === 'midtrans' && ! SystemSetting::isMidtransEnabled()) {
            return redirect()->back()->with(FlashDataVariable::FAILED->value, 'Metode pembayaran otomatis (Midtrans) saat ini sedang dinonaktifkan.');
        }

        $business = $request->user()->business;
        $plan = SubscriptionPlan::findOrFail($request->plan_id);

        if ($error = $this->validatePlanAccess($business, $plan)) {
            return redirect()->back()->with(FlashDataVariable::FAILED->value, $error);
        }

        $subscription = $this->subscriptionService->subscribe($business, $plan, $request->billing_cycle);

        // Generate the initial invoice for active outlets
        if ($business->outlets()->where('is_active', true)->count() > 0) {
            $invoice = $this->billingEngine->generateRecurringInvoice($business, $subscription);

            if ($invoice->total_amount == 0) {
                app(\App\Services\App\Invoice\CompleteInvoiceService::class)->execute($invoice);

                return redirect()->route('settings.billing.index')->with(
                    FlashDataVariable::SUCCESS->value,
                    'Berhasil berlangganan paket.'
                );
            }

            if ($request->payment_method === 'manual') {
                $invoice->payments()->create([
                    'amount' => $invoice->total_amount,
                    'payment_method' => 'manual',
                    'status' => 'pending',
                    'payment_reference' => "{$invoice->invoice_number}-MANUAL-".\Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(4)),
                ]);
            }

            return redirect()->route('settings.billing.index', ['open_invoice' => $invoice->invoice_number])
                ->with(
                    FlashDataVariable::SUCCESS->value,
                    'Berhasil berlangganan. Silakan selesaikan pembayaran tagihan awal.'
                );
        }

        // Auto-activate since there are no active outlets
        $business->subscriptions()
            ->where('id', '!=', $subscription->id)
            ->where('status', 'active')
            ->update([
                'status' => 'canceled',
                'canceled_at' => \Carbon\Carbon::now(),
            ]);

        $subscription->update([
            'status' => 'active',
        ]);

        return redirect()->route('settings.billing.index')->with(
            FlashDataVariable::SUCCESS->value,
            'Berhasil berlangganan paket.'
        );
    }

    public function changePlan(Request $request)
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'payment_method' => 'required|in:midtrans,manual',
        ]);

        if ($request->payment_method === 'midtrans' && ! SystemSetting::isMidtransEnabled()) {
            return redirect()->back()->with(FlashDataVariable::FAILED->value, 'Metode pembayaran otomatis (Midtrans) saat ini sedang dinonaktifkan.');
        }

        $business = $request->user()->business;
        $plan = SubscriptionPlan::findOrFail($request->plan_id);

        if ($error = $this->validatePlanAccess($business, $plan)) {
            return redirect()->back()->with(FlashDataVariable::FAILED->value, $error);
        }

        $subscription = $this->subscriptionService->subscribe($business, $plan, $request->billing_cycle);

        $invoice = $this->billingEngine->generateRecurringInvoice($business, $subscription);

        if ($invoice->total_amount == 0) {
            app(\App\Services\App\Invoice\CompleteInvoiceService::class)->execute($invoice);

            return redirect()->route('settings.billing.index')->with(
                FlashDataVariable::SUCCESS->value,
                'Paket berhasil diubah.'
            );
        }

        if ($request->payment_method === 'manual') {
            $invoice->payments()->create([
                'amount' => $invoice->total_amount,
                'payment_method' => 'manual',
                'status' => 'pending',
                'payment_reference' => "{$invoice->invoice_number}-MANUAL-".\Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(4)),
            ]);
        }

        return redirect()->route('settings.billing.index', ['open_invoice' => $invoice->invoice_number])
            ->with(
                FlashDataVariable::SUCCESS->value,
                'Paket berhasil diubah. Silakan selesaikan pembayaran.'
            );
    }

    public function cancel(Request $request)
    {
        $business = $request->user()->business;
        $subscription = $business->subscriptions()->where('status', 'active')->first();

        if ($subscription) {
            $this->subscriptionService->cancel($subscription);
        }

        return redirect()->route('settings.billing.index')->with(
            FlashDataVariable::SUCCESS->value,
            'Berlangganan berhasil dibatalkan.'
        );
    }

    public function renew(Request $request, \App\Services\App\Subscription\GenerateRenewalInvoiceService $renewService)
    {
        $request->validate([
            'plan_id' => 'required|exists:subscription_plans,id',
            'billing_cycle' => 'required|in:monthly,yearly',
            'payment_method' => 'required|in:midtrans,manual',
        ]);

        if ($request->payment_method === 'midtrans' && ! SystemSetting::isMidtransEnabled()) {
            return redirect()->back()->with(FlashDataVariable::FAILED->value, 'Metode pembayaran otomatis (Midtrans) saat ini sedang dinonaktifkan.');
        }

        $business = $request->user()->business;
        $plan = SubscriptionPlan::findOrFail($request->plan_id);

        if ($error = $this->validatePlanAccess($business, $plan)) {
            return redirect()->back()->with(FlashDataVariable::FAILED->value, $error);
        }

        try {
            $invoice = $renewService->execute($business, $plan, $request->billing_cycle);
        } catch (\Symfony\Component\HttpKernel\Exception\BadRequestHttpException $e) {
            return redirect()->route('settings.billing.index')->with(
                FlashDataVariable::FAILED->value,
                $e->getMessage()
            );
        }

        if ($invoice->total_amount == 0) {
            app(\App\Services\App\Invoice\CompleteInvoiceService::class)->execute($invoice);

            return redirect()->route('settings.billing.index')->with(
                FlashDataVariable::SUCCESS->value,
                'Perpanjangan paket berhasil.'
            );
        }

        if ($request->payment_method === 'manual') {
            $invoice->payments()->create([
                'amount' => $invoice->total_amount,
                'payment_method' => 'manual',
                'status' => 'pending',
                'payment_reference' => "{$invoice->invoice_number}-MANUAL-".\Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(4)),
            ]);
        }

        return redirect()->route('settings.billing.index', ['open_invoice' => $invoice->invoice_number])
            ->with(
                FlashDataVariable::SUCCESS->value,
                'Invoice perpanjangan terbuat. Silakan selesaikan pembayaran.'
            );
    }

    protected function validatePlanAccess($business, SubscriptionPlan $plan): ?string
    {
        if (! $plan->is_active) {
            return 'Paket langganan ini sudah tidak aktif.';
        }

        if ($plan->business_id !== null && $plan->business_id !== $business->id) {
            return 'Anda tidak memiliki akses ke paket langganan ini.';
        }

        $assignedCustomPlansCount = SubscriptionPlan::getAllCached()
            ->filter(fn ($p) => $p->is_active && $p->business_id === $business->id)
            ->count();

        $activePlanId = $business->getActiveSubscriptionWithPlan()?->plan_id;

        if ($assignedCustomPlansCount > 0 && $plan->business_id !== $business->id && $activePlanId !== $plan->id) {
            return 'Bisnis Anda terikat pada paket kustom khusus. Silakan pilih paket yang tersedia untuk akun Anda.';
        }

        return null;
    }
}
