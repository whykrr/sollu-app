<?php

namespace App\Services\App\User;

use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Invoice;
use App\Models\Outlet;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\App\Invoice\CompleteInvoiceService;
use App\Services\App\Outlet\OutletProvisioningService;
use App\Services\App\Role\RoleProvisioningService;
use App\Services\App\Subscription\BillingEngine;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RegisterBusinessService
{
    public function __construct(
        protected OutletProvisioningService $provisioningService,
        protected BillingEngine $billingEngine,
        protected CompleteInvoiceService $completeInvoiceService
    ) {}

    /**
     * Register a new business, default main outlet, owner user, and selected subscription plan.
     *
     * @param  array<string, mixed>  $data
     * @return array{user: User, business: Business, outlet: Outlet, subscription: Subscription, invoice: ?Invoice}
     */
    public function execute(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $plan = SubscriptionPlan::findOrFail($data['plan_id']);
            $isTrial = ! empty($data['use_trial']) && (bool) $plan->has_trial;

            $type = BusinessType::find($data['business_type_id']);

            $business = Business::create([
                'name' => $data['name'],
                'owner_name' => $data['owner_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'status' => 'active',
                'has_used_trial' => $isTrial,
                'business_type_id' => $data['business_type_id'],
                'settings' => $type?->default_settings ?? null,
            ]);

            $outlet = $business->outlets()->create([
                'name' => $data['outlet_name'],
                'is_main_outlet' => true,
            ]);

            /** @var User $user */
            $user = $business->users()->create([
                'name' => $data['owner_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'password' => $data['password'],
                'is_root_user' => true,
            ]);

            // Provision roles for this business
            app(RoleProvisioningService::class)->provision($business);

            // Attach root user to main outlet (Root user has wildcard * and no role)
            $user->outlets()->attach($outlet->id);

            // Provision default settings & payment methods
            $this->provisioningService->provisionAll($outlet);

            // Subscription provisioning
            if ($isTrial) {
                $durationDays = $plan->getTrialDays();

                /** @var Subscription $subscription */
                $subscription = $business->subscriptions()->create([
                    'plan_id' => $plan->id,
                    'status' => SubscriptionStatus::Active,
                    'billing_cycle' => 'monthly',
                    'is_trial' => true,
                    'started_at' => Carbon::now(),
                    'expired_at' => Carbon::now()->addDays($durationDays),
                ]);

                $subscription->subscriptionOutlets()->create([
                    'outlet_id' => $outlet->id,
                    'activated_at' => Carbon::now(),
                ]);

                $invoice = null;
            } else {
                $billingCycle = ! empty($data['billing_cycle']) && in_array($data['billing_cycle'], ['monthly', 'yearly'], true)
                    ? $data['billing_cycle']
                    : 'monthly';

                $durationDays = $billingCycle === 'yearly' ? 365 : 30;

                /** @var Subscription $subscription */
                $subscription = $business->subscriptions()->create([
                    'plan_id' => $plan->id,
                    'status' => SubscriptionStatus::Pending,
                    'billing_cycle' => $billingCycle,
                    'is_trial' => false,
                    'started_at' => Carbon::now(),
                    'expired_at' => Carbon::now()->addDays($durationDays),
                ]);

                $subscription->subscriptionOutlets()->create([
                    'outlet_id' => $outlet->id,
                    'activated_at' => Carbon::now(),
                ]);

                $invoice = $this->billingEngine->generateRecurringInvoice($business, $subscription);

                if ($invoice->total_amount == 0) {
                    $this->completeInvoiceService->execute($invoice);
                }
            }

            return [
                'user' => $user,
                'business' => $business,
                'outlet' => $outlet,
                'subscription' => $subscription,
                'invoice' => $invoice,
            ];
        });
    }
}
