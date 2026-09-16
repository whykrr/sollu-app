<?php

namespace Tests\Unit\Services\App;

use App\Enums\SubscriptionInvoice\Status;
use App\Models\Business;
use App\Models\Invoice;
use App\Models\Outlet;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\App\Subscription\BillingEngine;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingEngineTest extends TestCase
{
    use RefreshDatabase;

    protected BillingEngine $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new BillingEngine;
    }

    public function test_it_calculates_prorated_cost_and_generates_invoice()
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $businessType = \App\Models\BusinessType::create(['name' => 'F&B', 'code' => 'fnb']);
        $business = Business::create([
            'business_type_id' => $businessType->id,
            'name' => 'Test Business',
            'owner_name' => 'John Doe',
            'email' => 'test@business.com',
            'phone' => '08123456789',
            'trial_end_at' => Carbon::now()->addDays(14),
        ]);
        $plan = SubscriptionPlan::first() ?? SubscriptionPlan::factory()->create();

        $now = Carbon::now()->startOfDay();
        Carbon::setTestNow($now);
        $subscription = Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'started_at' => $now->copy()->subDays(10),
            'expired_at' => $now->copy()->addDays(20),
        ]);

        // Monthly total days = 30
        // Price = 100,000
        // Remaining days = 20
        // Prorated cost = (20 / 30) * 100,000 = 66666.67

        $price = $plan->price_per_outlet;
        $prorated = $this->service->calculateProratedCost($subscription);
        $expected = round((20 / 30) * $price, 2);
        $this->assertEquals($expected, $prorated);

        $outlet = Outlet::create([
            'business_id' => $business->id,
            'name' => 'New Outlet',
        ]);

        $invoice = $this->service->generateOutletProratedInvoice($business, $subscription, $outlet);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $this->assertEquals($business->id, $invoice->business_id);
        $this->assertEquals($expected, $invoice->total_amount);
        $this->assertEquals(Status::Open, $invoice->status);
    }

    public function test_it_generates_recurring_invoice()
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $businessType = \App\Models\BusinessType::create(['name' => 'F&B', 'code' => 'fnb']);
        $business = Business::create([
            'business_type_id' => $businessType->id,
            'name' => 'Test Business',
            'owner_name' => 'John Doe',
            'email' => 'test@business.com',
            'phone' => '08123456789',
            'trial_end_at' => Carbon::now()->addDays(14),
        ]);
        $plan = SubscriptionPlan::first() ?? SubscriptionPlan::factory()->create();

        $now = Carbon::now()->startOfDay();
        Carbon::setTestNow($now);
        $subscription = Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'yearly',
            'started_at' => $now->copy()->subDays(10),
            'expired_at' => $now->copy()->addDays(355),
        ]);

        // Price = 100000 * 12 = 1,200,000
        // Discount 10% = 120,000
        // Net Price = 1,080,000 per outlet

        // Create 2 active subscription outlets
        $outlet1 = Outlet::create(['business_id' => $business->id, 'name' => 'Outlet 1']);
        $outlet2 = Outlet::create(['business_id' => $business->id, 'name' => 'Outlet 2']);

        $subscription->subscriptionOutlets()->create([
            'outlet_id' => $outlet1->id,
        ]);
        $subscription->subscriptionOutlets()->create([
            'outlet_id' => $outlet2->id,
        ]);

        $invoice = $this->service->generateRecurringInvoice($business, $subscription);

        $this->assertInstanceOf(Invoice::class, $invoice);
        $price = $plan->price_per_outlet;
        $yearly_price = $price * 12;
        $discount = $yearly_price * ($plan->yearly_discount_percent / 100);
        $net_price = $yearly_price - $discount;
        $expected = 2 * $net_price;
        $this->assertEquals($expected, $invoice->total_amount);
        $this->assertCount(1, $invoice->items);
        $this->assertEquals(2, $invoice->items[0]->quantity);
    }
}
