<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use Carbon\Carbon;
use Illuminate\Support\Str;

class BillingEngine
{
    /**
     * Calculate prorated cost for adding an outlet
     */
    public function calculateProratedCost(Subscription $subscription): float
    {
        $plan = $subscription->plan;
        
        $price = $plan->price_per_outlet;
        if ($subscription->billing_cycle === 'yearly') {
            $yearly_price = $price * 12;
            $discount = $yearly_price * ($plan->yearly_discount_percent / 100);
            $price = $yearly_price - $discount;
        }
        
        $now = Carbon::now();
        $expiredAt = $subscription->expired_at;
        
        if (!$expiredAt || $expiredAt->isPast()) {
            return $price;
        }

        $totalBillingDays = $subscription->started_at ? $subscription->started_at->diffInDays($expiredAt) : ($subscription->billing_cycle === 'yearly' ? 365 : 30);
        if ($totalBillingDays <= 0) {
            $totalBillingDays = $subscription->billing_cycle === 'yearly' ? 365 : 30;
        }

        $remainingDays = $now->diffInDays($expiredAt);
        if ($remainingDays <= 0) {
            return 0;
        }

        $proratedCost = ($remainingDays / $totalBillingDays) * $price;
        
        return round($proratedCost, 2);
    }

    /**
     * Generate prorated invoice when a new outlet is added
     */
    public function generateOutletProratedInvoice(Business $business, Subscription $subscription): ?Invoice
    {
        $proratedCost = $this->calculateProratedCost($subscription);
        
        if ($proratedCost <= 0) {
            return null;
        }

        $invoice = Invoice::create([
            'business_id' => $business->id,
            'invoice_number' => 'INV-' . strtoupper(Str::random(8)),
            'status' => 'open',
            'subtotal' => $proratedCost,
            'tax_amount' => 0, // Simplified tax
            'total_amount' => $proratedCost,
            'due_date' => Carbon::now()->addDays(3),
        ]);

        $invoice->items()->create([
            'item_type' => 'outlet_addition',
            'description' => 'Prorated billing for new outlet',
            'quantity' => 1,
            'unit_price' => $proratedCost,
            'subtotal' => $proratedCost,
            'metadata' => [
                'remaining_days' => Carbon::now()->diffInDays($subscription->expired_at),
            ],
        ]);

        return $invoice;
    }

    /**
     * Generate a recurring invoice for the business
     */
    public function generateRecurringInvoice(Business $business, Subscription $subscription): Invoice
    {
        $plan = $subscription->plan;
        
        // Count active outlets
        $activeOutlets = $subscription->subscriptionOutlets()->whereNull('deactivated_at')->count();
        
        $price = $plan->price_per_outlet;
        if ($subscription->billing_cycle === 'yearly') {
            $yearly_price = $price * 12;
            $discount = $yearly_price * ($plan->yearly_discount_percent / 100);
            $price = $yearly_price - $discount;
        }

        $subtotal = $activeOutlets * $price;

        $invoice = Invoice::create([
            'business_id' => $business->id,
            'invoice_number' => 'INV-' . strtoupper(Str::random(8)),
            'status' => 'open',
            'subtotal' => $subtotal,
            'tax_amount' => 0,
            'total_amount' => $subtotal,
            'due_date' => Carbon::now()->addDays(7),
        ]);

        $invoice->items()->create([
            'item_type' => 'recurring_plan',
            'description' => 'Recurring billing for ' . $plan->name . ' (' . $activeOutlets . ' outlets)',
            'quantity' => $activeOutlets,
            'unit_price' => $price,
            'subtotal' => $subtotal,
            'metadata' => [
                'active_outlets' => $activeOutlets,
            ],
        ]);

        return $invoice;
    }
}
