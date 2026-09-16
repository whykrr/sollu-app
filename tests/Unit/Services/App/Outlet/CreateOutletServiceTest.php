<?php

namespace Tests\Unit\Services\App\Outlet;

use App\Models\Outlet;
use App\Models\User;
use App\Services\App\Outlet\CreateOutletService;
use App\Services\App\Outlet\OutletProvisioningService;
use App\Services\App\Subscription\BillingEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CreateOutletServiceTest extends TestCase
{
    use RefreshDatabase;

    protected BillingEngine $billingEngineMock;

    protected OutletProvisioningService $provisioningServiceMock;

    protected CreateOutletService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->billingEngineMock = Mockery::mock(BillingEngine::class);
        $this->provisioningServiceMock = Mockery::mock(OutletProvisioningService::class);
        $this->provisioningServiceMock->shouldReceive('provisionAll')->andReturnNull();

        $this->service = new CreateOutletService(
            $this->billingEngineMock,
            $this->provisioningServiceMock
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    protected function createMerchantUser(): User
    {
        $type = \App\Models\BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = \App\Models\Business::create([
            'name' => 'Merchant Test Business',
            'owner_name' => 'Merchant Owner',
            'email' => 'merchant_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        return User::create([
            'business_id' => $business->id,
            'name' => 'Merchant User',
            'email' => 'user_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
            'is_root_user' => true,
        ]);
    }

    public function test_it_creates_outlet_without_active_subscription()
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $user = $this->createMerchantUser();

        $data = [
            'name' => 'New Outlet',
            'address' => 'Test Address',
            'phone' => '12345678',
            'email' => 'outlet@example.com',
        ];

        // Simulate request user for SummaryUser::cacheDelete()
        $this->actingAs($user);

        $result = $this->service->execute($data, $user);

        $this->assertIsArray($result);
        $this->assertInstanceOf(Outlet::class, $result['outlet']);
        $this->assertNull($result['invoice']);

        $this->assertEquals('New Outlet', $result['outlet']->name);
        $this->assertFalse($result['outlet']->is_active);

        // Assert it was attached to root user
        $this->assertTrue($user->fresh()->outlets->contains($result['outlet']->id));

        $this->assertDatabaseHas('outlet_audit_logs', [
            'outlet_id' => $result['outlet']->id,
            'user_id' => $user->id,
            'action' => 'created',
        ]);
    }

    public function test_it_creates_outlet_and_generates_invoice_for_active_subscription()
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $user = $this->createMerchantUser();
        $business = $user->business;

        // Mock active subscription
        $plan = \App\Models\SubscriptionPlan::first();

        $subscription = $business->subscriptions()->create([
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $mockInvoice = new \App\Models\Invoice(['id' => \Illuminate\Support\Str::uuid()]);

        $this->billingEngineMock->shouldReceive('generateOutletProratedInvoice')
            ->once()
            ->andReturn($mockInvoice);

        $data = [
            'name' => 'New Outlet With Invoice',
        ];

        $this->actingAs($user);

        $result = $this->service->execute($data, $user);

        $this->assertInstanceOf(Outlet::class, $result['outlet']);
        $this->assertEquals($mockInvoice, $result['invoice']);
    }
}
