<?php

namespace App\Services\App\Subscription;

use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\Invoice;
use App\Models\SubscriptionPlan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class GenerateRenewalInvoiceService
{
    public function __construct(protected BillingEngine $billingEngine) {}

    public function execute(Business $business, SubscriptionPlan $plan, string $billingCycle): Invoice
    {
        /** @var Invoice $invoice */
        $invoice = DB::transaction(function () use ($business, $plan, $billingCycle) {
            $subscription = $business->subscriptions()
                ->whereIn('status', [SubscriptionStatus::Active, SubscriptionStatus::Suspended])
                ->latest()
                ->first();

            if (! $subscription) {
                throw new BadRequestHttpException('Tidak ada langganan yang dapat diperpanjang.');
            }

            if ($subscription->plan_id !== $plan->id) {
                throw new BadRequestHttpException('Paket perpanjangan harus sama dengan paket saat ini.');
            }

            // Change in memory so BillingEngine calculates based on the selected cycle
            $subscription->billing_cycle = $billingCycle;

            $invoice = $this->billingEngine->generateRecurringInvoice($business, $subscription, 'plan_renewal');

            return $invoice;
        });

        return $invoice;
    }
}
