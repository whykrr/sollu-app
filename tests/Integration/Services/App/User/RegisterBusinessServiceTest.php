<?php

namespace Tests\Integration\Services\App\User;

use App\Enums\BusinessStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Invoice;
use App\Models\Outlet;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Services\App\Invoice\CompleteInvoiceService;
use App\Services\App\Outlet\OutletProvisioningService;
use App\Services\App\Subscription\BillingEngine;
use App\Services\App\User\RegisterBusinessService;
use Database\Seeders\Production\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class RegisterBusinessServiceTest extends TestCase
{
    use RefreshDatabase;

    protected OutletProvisioningService $provisioningServiceMock;

    protected RegisterBusinessService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolePermissionSeeder::class);
        $this->provisioningServiceMock = Mockery::mock(OutletProvisioningService::class);
        $this->service = new RegisterBusinessService(
            $this->provisioningServiceMock,
            app(BillingEngine::class),
            app(CompleteInvoiceService::class)
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_successfully_registers_business_with_trial_subscription(): void
    {
        // Setup business type & subscription plan
        $type = BusinessType::factory()->forType('coffee_shop')->create();
        $plan = SubscriptionPlan::create([
            'code' => 'micro_test',
            'name' => 'Paket Mikro Test',
            'price_per_outlet' => 50000,
            'yearly_discount_percent' => 0,
            'max_outlets' => 1,
            'has_trial' => true,
            'trial_duration_days' => 14,
            'is_active' => true,
            'is_public' => true,
        ]);

        $this->provisioningServiceMock->shouldReceive('provisionAll')
            ->once()
            ->with(Mockery::type(Outlet::class))
            ->andReturnNull();

        $data = [
            'name' => 'Kopi Maju Jaya',
            'owner_name' => 'Budi Santoso',
            'outlet_name' => 'Outlet Sudirman',
            'email' => 'budi@kopimaju.test',
            'phone' => '081234567890',
            'business_type_id' => $type->id,
            'plan_id' => $plan->id,
            'use_trial' => true,
            'password' => 'secret123',
        ];

        $result = $this->service->execute($data);

        // Assert structure
        $this->assertIsArray($result);
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('business', $result);
        $this->assertArrayHasKey('outlet', $result);
        $this->assertArrayHasKey('subscription', $result);
        $this->assertNull($result['invoice']);

        $business = $result['business'];
        $outlet = $result['outlet'];
        $user = $result['user'];
        $subscription = $result['subscription'];

        // Assert Business & has_used_trial
        $this->assertInstanceOf(Business::class, $business);
        $this->assertEquals('Kopi Maju Jaya', $business->name);
        $this->assertTrue($business->has_used_trial);
        $this->assertEquals(BusinessStatus::Active, $business->status);

        // Assert Subscription is trial & active
        $this->assertInstanceOf(Subscription::class, $subscription);
        $this->assertTrue($subscription->is_trial);
        $this->assertEquals(SubscriptionStatus::Active, $subscription->status);
        $this->assertEquals($plan->id, $subscription->plan_id);
        $this->assertEquals(14, (int) round(now()->diffInDays($subscription->expired_at)));

        // Assert outlet attached to subscription
        $this->assertDatabaseHas('subscription_outlets', [
            'subscription_id' => $subscription->id,
            'outlet_id' => $outlet->id,
        ]);

        // Assert no invoices generated
        $this->assertDatabaseCount('invoices', 0);
    }

    public function test_it_successfully_registers_business_with_direct_paid_subscription(): void
    {
        $type = BusinessType::factory()->forType('coffee_shop')->create();
        $plan = SubscriptionPlan::create([
            'code' => 'basic_test',
            'name' => 'Paket Basic Test',
            'price_per_outlet' => 100000,
            'yearly_discount_percent' => 20,
            'max_outlets' => null,
            'has_trial' => true,
            'is_active' => true,
            'is_public' => true,
        ]);

        $this->provisioningServiceMock->shouldReceive('provisionAll')
            ->once()
            ->with(Mockery::type(Outlet::class))
            ->andReturnNull();

        $data = [
            'name' => 'Kopi Berbayar Direct',
            'owner_name' => 'Rina Wijaya',
            'outlet_name' => 'Outlet Gatot Subroto',
            'email' => 'rina@kopidirect.test',
            'phone' => '081234567891',
            'business_type_id' => $type->id,
            'plan_id' => $plan->id,
            'use_trial' => false,
            'billing_cycle' => 'yearly',
            'password' => 'secret123',
        ];

        $result = $this->service->execute($data);

        $business = $result['business'];
        $subscription = $result['subscription'];
        $invoice = $result['invoice'];

        $this->assertFalse($business->has_used_trial);
        $this->assertFalse($subscription->is_trial);
        $this->assertEquals(SubscriptionStatus::Pending, $subscription->status);
        $this->assertEquals('yearly', $subscription->billing_cycle);

        // Verify initial invoice created
        $this->assertNotNull($invoice);
        $this->assertInstanceOf(Invoice::class, $invoice);
        // 100000 * 12 = 1200000 - 20% = 960000
        $this->assertEquals(960000, (float) $invoice->total_amount);
        $this->assertDatabaseHas('invoices', [
            'id' => $invoice->id,
            'business_id' => $business->id,
            'total_amount' => 960000,
        ]);
    }

    public function test_it_forces_paid_if_plan_does_not_have_trial(): void
    {
        $type = BusinessType::factory()->forType('coffee_shop')->create();
        $plan = SubscriptionPlan::create([
            'code' => 'pro_test',
            'name' => 'Paket Pro Test',
            'price_per_outlet' => 250000,
            'yearly_discount_percent' => 0,
            'has_trial' => false,
            'is_active' => true,
            'is_public' => true,
        ]);

        $this->provisioningServiceMock->shouldReceive('provisionAll')
            ->once()
            ->with(Mockery::type(Outlet::class))
            ->andReturnNull();

        $data = [
            'name' => 'Pro Enterprise Co',
            'owner_name' => 'Doni Darmawan',
            'outlet_name' => 'HQ Jakarta',
            'email' => 'doni@enterprise.test',
            'phone' => '081234567892',
            'business_type_id' => $type->id,
            'plan_id' => $plan->id,
            'use_trial' => true, // Attempted to get trial
            'billing_cycle' => 'monthly',
            'password' => 'secret123',
        ];

        $result = $this->service->execute($data);

        // Must NOT be trial
        $this->assertFalse($result['business']->has_used_trial);
        $this->assertFalse($result['subscription']->is_trial);
        $this->assertEquals(SubscriptionStatus::Pending, $result['subscription']->status);
        $this->assertNotNull($result['invoice']);
        $this->assertEquals(250000, (float) $result['invoice']->total_amount);
    }

    public function test_it_rolls_back_database_transaction_on_failure(): void
    {
        $type = BusinessType::factory()->forType('minimarket')->create();
        $plan = SubscriptionPlan::create([
            'code' => 'fail_plan',
            'name' => 'Fail Plan',
            'price_per_outlet' => 10000,
            'yearly_discount_percent' => 0,
            'has_trial' => true,
            'is_active' => true,
            'is_public' => true,
        ]);

        $this->provisioningServiceMock->shouldReceive('provisionAll')
            ->once()
            ->andThrow(new \RuntimeException('Provisioning failed unexpectedly'));

        $data = [
            'name' => 'Toko Serba Ada',
            'owner_name' => 'Siti Aminah',
            'outlet_name' => 'Toko Cabang 1',
            'email' => 'siti@tokoserba.test',
            'phone' => '081298765432',
            'business_type_id' => $type->id,
            'plan_id' => $plan->id,
            'use_trial' => true,
            'password' => 'password123',
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Provisioning failed unexpectedly');

        try {
            $this->service->execute($data);
        } finally {
            // Verify rollback occurred: no records created in database
            $this->assertDatabaseCount('businesses', 0);
            $this->assertDatabaseCount('outlets', 0);
            $this->assertDatabaseCount('users', 0);
            $this->assertDatabaseCount('outlet_user', 0);
            $this->assertDatabaseCount('subscriptions', 0);
        }
    }
}
