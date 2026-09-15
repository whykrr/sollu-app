<?php

namespace Tests\Unit\Services\App\Invoice;

use App\Enums\SubscriptionInvoice\Status;
use App\Enums\SubscriptionStatus;
use App\Events\Invoice\InvoicePaid;
use App\Models\Business;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\SubscriptionActivatedNotification;
use App\Services\App\Invoice\CompleteInvoiceService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Mockery;
use Tests\TestCase;

class CompleteInvoiceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CompleteInvoiceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new CompleteInvoiceService;
        Notification::fake();
        Event::fake([InvoicePaid::class]);
    }

    public function test_it_marks_invoice_paid_and_activates_subscription_and_sends_notification()
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 1, 12, 0, 0));

        $invoiceMock = Mockery::mock(Invoice::class)->makePartial();
        $businessMock = Mockery::mock(Business::class)->makePartial();
        $subscriptionMock = Mockery::mock(Subscription::class)->makePartial();
        $planMock = Mockery::mock(SubscriptionPlan::class)->makePartial();
        $ownerMock = Mockery::mock(User::class)->makePartial();

        $planMock->id = 1;
        $planMock->name = 'Pro Plan';

        $businessMock->id = 1;

        $subscriptionMock->id = 1;
        $subscriptionMock->status = 'inactive';
        $subscriptionMock->plan = $planMock;
        $subscriptionMock->expired_at = Carbon::now()->addDays(30);

        $invoiceMock->business = $businessMock;

        // DB Transaction mock
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        // Mock invoice update
        $invoiceMock->shouldReceive('update')
            ->once()
            ->with([
                'status' => Status::Paid,
                'paid_at' => Carbon::now(),
            ])
            ->andReturnTrue();

        // Mock invoice->items()->where()->exists()
        $itemsQueryMock = Mockery::mock(\Illuminate\Database\Eloquent\Relations\HasMany::class);
        $invoiceMock->shouldReceive('items')->andReturn($itemsQueryMock);

        $outletAdditionQuery = Mockery::mock(\Illuminate\Database\Eloquent\Relations\HasMany::class);
        $outletAdditionQuery->shouldReceive('exists')->andReturn(false);
        $itemsQueryMock->shouldReceive('where')->with('item_type', 'outlet_addition')->andReturn($outletAdditionQuery);

        $planRenewalQuery = Mockery::mock(\Illuminate\Database\Eloquent\Relations\HasMany::class);
        $planRenewalQuery->shouldReceive('exists')->andReturn(false);
        $itemsQueryMock->shouldReceive('where')->with('item_type', 'plan_renewal')->andReturn($planRenewalQuery);

        // Mock business->subscriptions()->latest()->first()
        $subsQueryMock = Mockery::mock(\Illuminate\Database\Eloquent\Relations\HasMany::class);
        $businessMock->shouldReceive('subscriptions')->andReturn($subsQueryMock);
        $subsQueryMock->shouldReceive('latest')->andReturnSelf();
        $subsQueryMock->shouldReceive('first')->andReturn($subscriptionMock);

        // Mock business->subscriptions()->where()->where()->update()
        $subsQueryMock->shouldReceive('where')->with('id', '!=', 1)->andReturnSelf();
        $subsQueryMock->shouldReceive('where')->with('status', SubscriptionStatus::Active)->andReturnSelf();
        $subsQueryMock->shouldReceive('update')->andReturn(1);

        // Mock subscription update
        $subscriptionMock->shouldReceive('update')
            ->once()
            ->with(['status' => SubscriptionStatus::Active])
            ->andReturnTrue();

        // Mock business->users()->first()
        $usersQueryMock = Mockery::mock(\Illuminate\Database\Eloquent\Relations\HasMany::class);
        $businessMock->shouldReceive('users')->once()->andReturn($usersQueryMock);
        $usersQueryMock->shouldReceive('first')->once()->andReturn($ownerMock);

        // Mock notify
        $ownerMock->shouldReceive('notify')
            ->once()
            ->with(Mockery::type(SubscriptionActivatedNotification::class));

        // Act
        $result = $this->service->execute($invoiceMock);

        $this->assertSame($invoiceMock, $result);
    }

    public function test_it_does_not_activate_subscription_if_it_is_outlet_addition()
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 1, 12, 0, 0));

        $invoiceMock = Mockery::mock(Invoice::class)->makePartial();
        $businessMock = Mockery::mock(Business::class)->makePartial();
        $subscriptionMock = Mockery::mock(Subscription::class)->makePartial();

        $invoiceMock->business = $businessMock;
        $subscriptionMock->status = SubscriptionStatus::Active;

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $invoiceMock->shouldReceive('update')
            ->once()
            ->with([
                'status' => Status::Paid,
                'paid_at' => Carbon::now(),
            ])
            ->andReturnTrue();

        // outlet_addition exists is true
        $itemsQueryMock = Mockery::mock(\Illuminate\Database\Eloquent\Relations\HasMany::class);
        $invoiceMock->shouldReceive('items')->andReturn($itemsQueryMock);

        $outletAdditionQuery = Mockery::mock(\Illuminate\Database\Eloquent\Relations\HasMany::class);
        $outletAdditionQuery->shouldReceive('exists')->andReturn(true);
        $itemsQueryMock->shouldReceive('where')->with('item_type', 'outlet_addition')->andReturn($outletAdditionQuery);

        $planRenewalQuery = Mockery::mock(\Illuminate\Database\Eloquent\Relations\HasMany::class);
        $planRenewalQuery->shouldReceive('exists')->andReturn(false);
        $itemsQueryMock->shouldReceive('where')->with('item_type', 'plan_renewal')->andReturn($planRenewalQuery);

        // For the outlet addition logic
        $outletItemMock = Mockery::mock();
        $outletItemMock->metadata = ['outlet_id' => 99];
        $outletAdditionQuery->shouldReceive('first')->andReturn($outletItemMock);

        // Just let it find the outlet (or not) natively since we can't easily mock the static method here
        // without complex runInSeparateProcess setup that conflicts with prior tests.
        // We will just verify it doesn't fail.

        // Mock ManageOutletStatusService
        $ownerMock = Mockery::mock(User::class)->makePartial();

        $usersQueryMock = Mockery::mock(\Illuminate\Database\Eloquent\Relations\HasMany::class);
        $businessMock->shouldReceive('users')->andReturn($usersQueryMock);
        $usersQueryMock->shouldReceive('first')->andReturn($ownerMock);

        $manageServiceMock = Mockery::mock(\App\Services\App\Outlet\ManageOutletStatusService::class);
        $this->app->instance(\App\Services\App\Outlet\ManageOutletStatusService::class, $manageServiceMock);

        $subsQueryMock = Mockery::mock(\Illuminate\Database\Eloquent\Relations\HasMany::class);
        $businessMock->shouldReceive('subscriptions')->once()->andReturn($subsQueryMock);
        $subsQueryMock->shouldReceive('latest')->once()->andReturnSelf();
        $subsQueryMock->shouldReceive('first')->once()->andReturn($subscriptionMock);

        // Expect no update on subscription
        $subscriptionMock->shouldNotReceive('update');

        $result = $this->service->execute($invoiceMock);

        $this->assertSame($invoiceMock, $result);
    }

    public function test_it_extends_subscription_when_it_is_a_plan_renewal()
    {
        Carbon::setTestNow(Carbon::create(2026, 1, 1, 12, 0, 0));

        $invoiceMock = Mockery::mock(Invoice::class)->makePartial();
        $businessMock = Mockery::mock(Business::class)->makePartial();

        $planMock = Mockery::mock(SubscriptionPlan::class)->makePartial();
        $planMock->shouldReceive('getAttribute')->with('name')->andReturn('Pro Plan');

        $subscriptionMock = Mockery::mock(Subscription::class)->makePartial();
        $subscriptionMock->shouldReceive('getAttribute')->with('billing_cycle')->andReturn('yearly');

        $expiredDate = Carbon::now()->addDays(5);
        $subscriptionMock->shouldReceive('getAttribute')->with('expired_at')->andReturn($expiredDate);
        $subscriptionMock->shouldReceive('getAttribute')->with('plan')->andReturn($planMock);

        $invoiceMock->shouldReceive('getAttribute')->with('business')->andReturn($businessMock);

        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(function ($callback) {
                return $callback();
            });

        $invoiceMock->shouldReceive('update')
            ->once()
            ->with([
                'status' => Status::Paid,
                'paid_at' => Carbon::now(),
            ])
            ->andReturnTrue();

        $itemsQueryMock = Mockery::mock(\Illuminate\Database\Eloquent\Relations\HasMany::class);
        $invoiceMock->shouldReceive('items')->andReturn($itemsQueryMock);

        // where('item_type', 'outlet_addition')->exists() -> false
        $outletAdditionQuery = Mockery::mock(\Illuminate\Database\Eloquent\Relations\HasMany::class);
        $outletAdditionQuery->shouldReceive('exists')->andReturn(false);
        $itemsQueryMock->shouldReceive('where')->with('item_type', 'outlet_addition')->andReturn($outletAdditionQuery);

        // where('item_type', 'plan_renewal')->exists() -> true
        // where('item_type', 'plan_renewal')->first() -> $renewalItemMock
        $renewalItemMock = Mockery::mock();
        $renewalItemMock->metadata = ['subscription_id' => 99];

        $planRenewalQuery = Mockery::mock(\Illuminate\Database\Eloquent\Relations\HasMany::class);
        $planRenewalQuery->shouldReceive('exists')->andReturn(true);
        $planRenewalQuery->shouldReceive('first')->andReturn($renewalItemMock);
        $itemsQueryMock->shouldReceive('where')->with('item_type', 'plan_renewal')->andReturn($planRenewalQuery);

        $subsQueryMock = Mockery::mock(\Illuminate\Database\Eloquent\Relations\HasMany::class);
        $businessMock->shouldReceive('subscriptions')->andReturn($subsQueryMock);
        $subsQueryMock->shouldReceive('latest')->andReturnSelf();
        $subsQueryMock->shouldReceive('first')->andReturn($subscriptionMock);

        $subsQueryMock->shouldReceive('find')->with(99)->andReturn($subscriptionMock);

        $subscriptionMock->shouldReceive('update')
            ->once()
            ->withArgs(function ($args) use ($expiredDate) {
                return $args['status'] === SubscriptionStatus::Active &&
                       $args['expired_at']->equalTo($expiredDate->copy()->addDays(365));
            })
            ->andReturnTrue();

        $ownerMock = Mockery::mock(User::class)->makePartial();
        $usersQueryMock = Mockery::mock(\Illuminate\Database\Eloquent\Relations\HasMany::class);
        $businessMock->shouldReceive('users')->once()->andReturn($usersQueryMock);
        $usersQueryMock->shouldReceive('first')->once()->andReturn($ownerMock);

        $ownerMock->shouldReceive('notify')
            ->once()
            ->with(Mockery::type(SubscriptionActivatedNotification::class));

        $result = $this->service->execute($invoiceMock);
        $this->assertSame($invoiceMock, $result);
        Event::assertDispatched(InvoicePaid::class);
    }

    public function test_it_returns_early_if_invoice_is_already_paid()
    {
        $invoiceMock = Mockery::mock(Invoice::class)->makePartial();
        $invoiceMock->status = Status::Paid;

        DB::shouldReceive('transaction')->never();

        $result = $this->service->execute($invoiceMock);

        $this->assertSame($invoiceMock, $result);
        Event::assertNotDispatched(InvoicePaid::class);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        \Illuminate\Support\Facades\DB::clearResolvedInstances();
        parent::tearDown();
    }
}
