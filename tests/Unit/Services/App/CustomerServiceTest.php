<?php

namespace Tests\Unit\Services\App;

use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Master\Customer;
use App\Models\Outlet;
use App\Models\Sales\Transaction;
use App\Models\User;
use App\Services\App\Customer\CustomerService;
use App\Services\App\Master\ActivityLogService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class CustomerServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CustomerService $service;

    protected $activityLogServiceMock;

    protected Business $business;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(DatabaseSeeder::class);

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $this->business = Business::create([
            'name' => 'Test Merchant',
            'owner_name' => 'Merchant Owner',
            'email' => 'merchant_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Test User',
            'email' => 'user_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        $this->actingAs($this->user);

        $this->activityLogServiceMock = Mockery::mock(ActivityLogService::class);
        $this->service = new CustomerService($this->activityLogServiceMock);
    }

    public function test_it_gets_paginated_customers_isolated_to_business(): void
    {
        // Customer for current business
        Customer::create([
            'business_id' => $this->business->id,
            'name' => 'John Doe',
            'phone' => '08123456789',
            'email' => 'john@test.com',
            'is_active' => true,
        ]);

        Customer::create([
            'business_id' => $this->business->id,
            'name' => 'Jane Smith',
            'phone' => '08987654321',
            'email' => 'jane@test.com',
            'is_active' => false,
        ]);

        // Customer for another business
        $otherBusiness = Business::create([
            'name' => 'Other Merchant',
            'owner_name' => 'Other Owner',
            'email' => 'other_'.uniqid().'@test.test',
            'phone' => '08999999999',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $this->business->business_type_id,
        ]);

        Customer::create([
            'business_id' => $otherBusiness->id,
            'name' => 'John Other',
            'phone' => '08111111111',
            'email' => 'johnother@test.com',
            'is_active' => true,
        ]);

        $filters = ['search' => 'John', 'is_active' => true];
        $result = $this->service->getPaginated($filters);

        $this->assertEquals(1, $result->total());
        $this->assertEquals('John Doe', $result->items()[0]->name);
        $this->assertEquals($this->business->id, $result->items()[0]->business_id);
    }

    public function test_it_gets_summary_stats(): void
    {
        $outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Main Outlet',
        ]);

        $customer = Customer::create([
            'business_id' => $this->business->id,
            'name' => 'John Doe',
            'phone' => '08123456789',
        ]);

        Transaction::create([
            'outlet_id' => $outlet->id,
            'customer_id' => $customer->id,
            'status' => 'completed',
            'total' => 50000,
            'transaction_number' => 'TRX-001',
        ]);

        $stats = $this->service->getSummaryStats($customer);

        $this->assertEquals(1, $stats['total_transactions']);
        $this->assertEquals(50000, $stats['total_spent']);
        $this->assertEquals(50000, $stats['average_spent']);
        $this->assertCount(1, $stats['recent_transactions']);
    }

    public function test_it_creates_customer(): void
    {
        $data = [
            'business_id' => $this->business->id,
            'name' => 'New Customer',
            'phone' => '08000000000',
        ];

        $this->activityLogServiceMock
            ->shouldReceive('log')
            ->once()
            ->with(Mockery::type(Customer::class), 'created', $this->user);

        $customer = $this->service->create($data);

        $this->assertInstanceOf(Customer::class, $customer);
        $this->assertEquals('New Customer', $customer->name);
        $this->assertEquals($this->business->id, $customer->business_id);
        $this->assertDatabaseHas('customers', [
            'name' => 'New Customer',
            'business_id' => $this->business->id,
        ]);
    }

    public function test_it_updates_customer(): void
    {
        $customer = Customer::create([
            'business_id' => $this->business->id,
            'name' => 'Old Name',
            'phone' => '08000000000',
        ]);

        $this->activityLogServiceMock
            ->shouldReceive('log')
            ->once()
            ->with(Mockery::type(Customer::class), 'updated', $this->user);

        $updated = $this->service->update($customer, ['name' => 'New Name']);

        $this->assertEquals('New Name', $updated->name);
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'New Name',
        ]);
    }

    public function test_it_soft_deletes_customer_with_transactions(): void
    {
        $outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Main Outlet',
        ]);

        $customer = Customer::create([
            'business_id' => $this->business->id,
            'name' => 'John Doe',
            'phone' => '08123456789',
        ]);

        Transaction::create([
            'outlet_id' => $outlet->id,
            'customer_id' => $customer->id,
            'status' => 'completed',
            'total' => 50000,
            'transaction_number' => 'TRX-001',
        ]);

        $this->activityLogServiceMock
            ->shouldReceive('log')
            ->once()
            ->with(Mockery::type(Customer::class), 'deactivated (soft delete via update)', $this->user);

        $this->service->delete($customer);

        $customer->refresh();
        $this->assertFalse($customer->is_active);
    }

    public function test_it_hard_deletes_customer_without_transactions(): void
    {
        $customer = Customer::create([
            'business_id' => $this->business->id,
            'name' => 'John Doe',
            'phone' => '08123456789',
        ]);

        $this->activityLogServiceMock
            ->shouldReceive('log')
            ->once()
            ->with(Mockery::type(Customer::class), 'deleted', $this->user);

        $this->service->delete($customer);

        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }

    public function test_it_searches_active_customers_isolated_to_business(): void
    {
        Customer::create([
            'business_id' => $this->business->id,
            'name' => 'John Active',
            'phone' => '08123456789',
            'is_active' => true,
        ]);

        Customer::create([
            'business_id' => $this->business->id,
            'name' => 'John Inactive',
            'phone' => '08987654321',
            'is_active' => false,
        ]);

        $otherBusiness = Business::create([
            'name' => 'Other Merchant 2',
            'owner_name' => 'Other Owner 2',
            'email' => 'other2_'.uniqid().'@test.test',
            'phone' => '08888888888',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $this->business->business_type_id,
        ]);

        Customer::create([
            'business_id' => $otherBusiness->id,
            'name' => 'John Active in Other Business',
            'phone' => '08777777777',
            'is_active' => true,
        ]);

        $results = $this->service->searchActive('John');

        $this->assertCount(1, $results);
        $this->assertEquals('John Active', $results[0]->name);
        $this->assertEquals($this->business->id, $results[0]->business_id);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
