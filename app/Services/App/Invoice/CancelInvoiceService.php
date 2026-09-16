<?php

namespace App\Services\App\Invoice;

use App\Enums\SubscriptionInvoice\Status;
use App\Models\Invoice;
use App\Models\Outlet;
use App\Models\User;
use App\Services\App\Outlet\ManageOutletStatusService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class CancelInvoiceService
{
    public function __construct(
        protected ManageOutletStatusService $manageOutletStatusService
    ) {}

    /**
     * Cancel an open subscription invoice and perform required side-effects (outlet rollback, subscription rollback).
     *
     * @return array{invoice: Invoice, is_outlet_addition: bool}
     */
    public function execute(Invoice $invoice, User $user): array
    {
        return DB::transaction(function () use ($invoice, $user): array {
            $invoice->update([
                'status' => Status::Void,
            ]);

            $business = $invoice->business;
            $isOutletAddition = false;

            // Find associated outlet to delete if this invoice was for an outlet addition
            $outletAdditionItem = $invoice->items()->where('item_type', 'outlet_addition')->first();
            if ($outletAdditionItem && isset($outletAdditionItem->metadata['outlet_id'])) {
                $isOutletAddition = true;
                $outletId = $outletAdditionItem->metadata['outlet_id'];
                $outlet = Outlet::where('id', $outletId)
                    ->where('business_id', $business->id)
                    ->first();

                if ($outlet) {
                    $this->manageOutletStatusService->delete($outlet, $user);
                }
            }

            // Determine what type of subscription invoice this is
            $recurringPlanItem = $invoice->items()->where('item_type', 'recurring_plan')->first();
            $isPlanRenewal = $invoice->items()->where('item_type', 'plan_renewal')->exists();

            // Only cancel the subscription if it's a NEW subscription (recurring_plan) and NOT a renewal
            if ($recurringPlanItem && ! $isOutletAddition && ! $isPlanRenewal) {
                $subscriptionId = $recurringPlanItem->metadata['subscription_id'] ?? null;
                if ($subscriptionId) {
                    $subscription = $business->subscriptions()->find($subscriptionId);
                    if ($subscription && $subscription->status === 'inactive') {
                        $subscription->update([
                            'status' => 'canceled',
                            'canceled_at' => Carbon::now(),
                        ]);
                    }
                }
            }

            return [
                'invoice' => $invoice,
                'is_outlet_addition' => $isOutletAddition,
            ];
        });
    }
}
