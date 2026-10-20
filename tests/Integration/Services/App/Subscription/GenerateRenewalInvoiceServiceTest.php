<?php

namespace Tests\Integration\Services\App\Subscription;

use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\App\Subscription\BillingEngine;
use App\Services\App\Subscription\GenerateRenewalInvoiceService;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\MockInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Tests\TestCase;

class GenerateRenewalInvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    private GenerateRenewalInvoiceService $service;

    private MockInterface $billingEngineMock;

    protected function setUp(): void
    {
        parent::setUp();

        $this->billingEngineMock = Mockery::mock(BillingEngine::class);
        $this->service = new GenerateRenewalInvoiceService($this->billingEngineMock);
    }

    public function test_it_can_generate_renewal_invoice_successfully()
    {
        $businessMock = Mockery::mock(Business::class)->makePartial();
        $planMock = Mockery::mock(SubscriptionPlan::class)->makePartial();
        $planMock->shouldReceive('getAttribute')->with('id')->andReturn('plan-1');

        $subscriptionMock = Mockery::mock(Subscription::class)->makePartial();
        $subscriptionMock->shouldReceive('getAttribute')->with('id')->andReturn('sub-1');
        $subscriptionMock->shouldReceive('getAttribute')->with('plan_id')->andReturn('plan-1');
        $subscriptionMock->shouldReceive('getAttribute')->with('status')->andReturn(SubscriptionStatus::Active);
        $subscriptionMock->shouldReceive('setAttribute')->with('billing_cycle', 'yearly')->andReturnNull();

        $hasManyMock = Mockery::mock(HasMany::class);
        $hasManyMock->shouldReceive('where')->with('status', SubscriptionStatus::Active)->andReturn($hasManyMock);
        $hasManyMock->shouldReceive('first')->andReturn($subscriptionMock);

        $businessMock->shouldReceive('subscriptions')->andReturn($hasManyMock);

        $invoiceMock = Mockery::mock(Invoice::class)->makePartial();

        $this->billingEngineMock->shouldReceive('generateRecurringInvoice')
            ->once()
            ->with($businessMock, $subscriptionMock, 'plan_renewal')
            ->andReturn($invoiceMock);

        $result = $this->service->execute($businessMock, $planMock, 'yearly');

        $this->assertEquals($invoiceMock, $result);
    }

    public function test_it_throws_exception_if_no_active_subscription()
    {
        $businessMock = Mockery::mock(Business::class)->makePartial();
        $planMock = Mockery::mock(SubscriptionPlan::class)->makePartial();

        $hasManyMock = Mockery::mock(HasMany::class);
        $hasManyMock->shouldReceive('where')->with('status', SubscriptionStatus::Active)->andReturn($hasManyMock);
        $hasManyMock->shouldReceive('first')->andReturn(null);

        $businessMock->shouldReceive('subscriptions')->andReturn($hasManyMock);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Tidak ada langganan aktif untuk diperpanjang.');

        $this->service->execute($businessMock, $planMock, 'monthly');
    }

    public function test_it_throws_exception_if_plan_does_not_match()
    {
        $businessMock = Mockery::mock(Business::class)->makePartial();
        $planMock = Mockery::mock(SubscriptionPlan::class)->makePartial();
        $planMock->shouldReceive('getAttribute')->with('id')->andReturn('plan-1');

        $subscriptionMock = Mockery::mock(Subscription::class)->makePartial();
        $subscriptionMock->shouldReceive('getAttribute')->with('id')->andReturn('sub-1');
        $subscriptionMock->shouldReceive('getAttribute')->with('plan_id')->andReturn('plan-2');
        $subscriptionMock->shouldReceive('getAttribute')->with('status')->andReturn(SubscriptionStatus::Active);

        $hasManyMock = Mockery::mock(HasMany::class);
        $hasManyMock->shouldReceive('where')->with('status', SubscriptionStatus::Active)->andReturn($hasManyMock);
        $hasManyMock->shouldReceive('first')->andReturn($subscriptionMock);

        $businessMock->shouldReceive('subscriptions')->andReturn($hasManyMock);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('Paket perpanjangan harus sama dengan paket saat ini.');

        $this->service->execute($businessMock, $planMock, 'monthly');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        \Illuminate\Support\Facades\DB::clearResolvedInstances();
        parent::tearDown();
    }
}
