<?php

namespace Tests\Unit\Services\Cockpit;

use App\Enums\BusinessStatus;
use App\Enums\PaymentManualValidationStatus;
use App\Enums\SubscriptionInvoice\Status as InvoiceStatusEnum;
use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Invoice;
use App\Models\Outlet;
use App\Models\PaymentManualValidation;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\Cockpit\CockpitDashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class CockpitDashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CockpitDashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CockpitDashboardService;
        Cache::flush();
    }

    public function test_get_dashboard_data_structure(): void
    {
        $data = $this->service->getDashboardData('this_month');

        $this->assertArrayHasKey('metrics', $data);
        $this->assertArrayHasKey('revenue_trend', $data);
        $this->assertArrayHasKey('acquisition_trend', $data);
        $this->assertArrayHasKey('plan_distribution', $data);
        $this->assertArrayHasKey('business_type_distribution', $data);
        $this->assertArrayHasKey('pending_invoices', $data);
        $this->assertArrayHasKey('recent_merchants', $data);
        $this->assertArrayHasKey('period_label', $data);
    }

    public function test_metrics_and_revenue_growth_calculation(): void
    {
        $type = BusinessType::create([
            'code' => 'retail',
            'name' => 'Retail Shop',
            'sort_order' => 1,
            'is_visible' => true,
        ]);

        $business = Business::create([
            'name' => 'Toko Barokah',
            'owner_name' => 'Ahmad',
            'email' => 'ahmad@barokah.test',
            'phone' => '081111111111',
            'status' => BusinessStatus::Active,
            'trial_end_at' => now()->addDays(7),
            'business_type_id' => $type->id,
        ]);

        Outlet::create([
            'business_id' => $business->id,
            'name' => 'Cabang 1',
            'is_active' => true,
        ]);

        $plan = SubscriptionPlan::create([
            'code' => 'starter',
            'name' => 'Paket Starter',
            'price_per_outlet' => 99000,
            'yearly_discount_percent' => 0,
            'is_active' => true,
            'is_public' => true,
        ]);

        Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'started_at' => now(),
            'expired_at' => now()->addMonth(),
        ]);

        // Create paid invoice this month
        Invoice::create([
            'business_id' => $business->id,
            'invoice_number' => 'INV-2026-0001',
            'status' => InvoiceStatusEnum::Paid,
            'subtotal' => 99000,
            'tax_amount' => 0,
            'total_amount' => 99000,
            'paid_at' => now(),
        ]);

        // Create paid invoice last month
        Invoice::create([
            'business_id' => $business->id,
            'invoice_number' => 'INV-2026-0000',
            'status' => InvoiceStatusEnum::Paid,
            'subtotal' => 50000,
            'tax_amount' => 0,
            'total_amount' => 50000,
            'paid_at' => now()->subMonth(),
        ]);

        // Create pending validation invoice
        $invPending = Invoice::create([
            'business_id' => $business->id,
            'invoice_number' => 'INV-2026-0002',
            'status' => InvoiceStatusEnum::Unpaid,
            'subtotal' => 99000,
            'tax_amount' => 0,
            'total_amount' => 99000,
        ]);

        PaymentManualValidation::create([
            'invoice_id' => $invPending->id,
            'validation_status' => PaymentManualValidationStatus::Pending,
            'payment_proof_url' => 'proofs/test.jpg',
        ]);

        $data = $this->service->getDashboardData('this_month');
        $metrics = $data['metrics'];

        $this->assertSame(99000.0, $metrics['current_revenue']);
        $this->assertSame(50000.0, $metrics['previous_revenue']);
        $this->assertSame(98.0, $metrics['revenue_growth_percent']); // ((99000-50000)/50000)*100 = 98.0%
        $this->assertSame(1, $metrics['total_merchants']);
        $this->assertSame(1, $metrics['active_merchants']);
        $this->assertSame(1, $metrics['total_outlets']);
        $this->assertSame(1, $metrics['pending_invoices_count']);
        $this->assertCount(1, $data['pending_invoices']);
        $this->assertSame('INV-2026-0002', $data['pending_invoices'][0]['invoice_number']);
        $this->assertCount(1, $data['recent_merchants']);
        $this->assertSame('Toko Barokah', $data['recent_merchants'][0]['name']);
    }

    public function test_distributions_and_acquisition_trends(): void
    {
        $typeFnb = BusinessType::create([
            'code' => 'fnb',
            'name' => 'F&B',
            'sort_order' => 1,
            'is_visible' => true,
        ]);

        $b = Business::create([
            'name' => 'Kopi Senja',
            'owner_name' => 'Rian',
            'email' => 'rian@senja.test',
            'phone' => '082222222222',
            'status' => BusinessStatus::Active,
            'trial_end_at' => now()->addDays(5),
            'business_type_id' => $typeFnb->id,
        ]);

        $planDist = $this->service->getPlanDistribution();
        $this->assertContains('Masa Trial', $planDist['labels']);

        $typeDist = $this->service->getBusinessTypeDistribution();
        $this->assertContains('F&B', $typeDist['labels']);

        $acq = $this->service->getAcquisitionTrend();
        $this->assertCount(6, $acq['labels']);
        $this->assertCount(6, $acq['merchants']);
        $this->assertCount(6, $acq['outlets']);
    }
}
