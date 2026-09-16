<?php

namespace Tests\Unit\Services\App\Outlet;

use App\Models\Invoice;
use App\Models\Outlet;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\App\Outlet\ManageOutletStatusService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManageOutletStatusServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ManageOutletStatusService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ManageOutletStatusService;
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

    public function test_it_toggles_status_to_active()
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $user = $this->createMerchantUser();
        $business = $user->business;
        $outlet = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Outlet Test',
            'is_active' => false,
        ]);

        // Mock subscription
        $plan = SubscriptionPlan::first();
        $subscription = $business->subscriptions()->create([
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $this->actingAs($user);

        $result = $this->service->toggleStatus($outlet, true, $user);

        $this->assertTrue($result->is_active);

        $this->assertDatabaseHas('subscription_outlets', [
            'subscription_id' => $subscription->id,
            'outlet_id' => $outlet->id,
            'deactivated_at' => null,
        ]);

        $this->assertDatabaseHas('outlet_audit_logs', [
            'outlet_id' => $outlet->id,
            'action' => 'enabled',
        ]);
    }

    public function test_it_fails_to_activate_if_unpaid_invoice()
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $user = $this->createMerchantUser();
        $business = $user->business;
        $outlet = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Outlet Test',
            'is_active' => false,
        ]);

        $invoice = Invoice::create([
            'business_id' => $business->id,
            'invoice_number' => 'INV-123',
            'status' => 'open',
            'subtotal' => 1000,
            'tax_amount' => 0,
            'total_amount' => 1000,
            'due_date' => now()->addDays(3),
        ]);

        $invoice->items()->create([
            'item_type' => 'outlet_addition',
            'description' => 'Outlet addition',
            'quantity' => 1,
            'unit_price' => 1000,
            'total_price' => 1000,
            'metadata' => ['outlet_id' => $outlet->id],
        ]);

        $this->actingAs($user);

        $this->service->toggleStatus($outlet, true, $user);
    }

    public function test_it_toggles_status_to_inactive()
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $user = $this->createMerchantUser();
        $business = $user->business;
        $outlet = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Outlet Test',
            'is_active' => true,
        ]);

        $plan = SubscriptionPlan::first();
        $subscription = $business->subscriptions()->create([
            'plan_id' => $plan->id,
            'status' => 'active',
            'current_period_start' => now(),
            'current_period_end' => now()->addMonth(),
        ]);

        $subscription->subscriptionOutlets()->create([
            'outlet_id' => $outlet->id,
            'activated_at' => now()->subDay(),
        ]);

        $this->actingAs($user);

        $result = $this->service->toggleStatus($outlet, false, $user);

        $this->assertFalse($result->is_active);

        $this->assertDatabaseMissing('subscription_outlets', [
            'subscription_id' => $subscription->id,
            'outlet_id' => $outlet->id,
            'deactivated_at' => null,
        ]);

        $this->assertDatabaseHas('outlet_audit_logs', [
            'outlet_id' => $outlet->id,
            'action' => 'disabled',
        ]);
    }

    public function test_it_deletes_outlet()
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $user = $this->createMerchantUser();
        $outlet = Outlet::create([
            'business_id' => $user->business_id,
            'name' => 'Outlet Delete',
        ]);

        $this->actingAs($user);

        $this->service->delete($outlet, $user);

        $this->assertSoftDeleted('outlets', ['id' => $outlet->id]);

        $this->assertDatabaseHas('outlet_audit_logs', [
            'outlet_id' => $outlet->id,
            'action' => 'deleted',
        ]);
    }

    public function test_it_restores_outlet()
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $user = $this->createMerchantUser();
        $outlet = Outlet::create([
            'business_id' => $user->business_id,
            'name' => 'Outlet Restore',
        ]);

        $outlet->delete();

        $this->actingAs($user);

        $result = $this->service->restore($outlet->id, $user);

        $this->assertEquals($outlet->id, $result->id);
        $this->assertDatabaseHas('outlets', [
            'id' => $outlet->id,
            'deleted_at' => null,
        ]);

        $this->assertDatabaseHas('outlet_audit_logs', [
            'outlet_id' => $outlet->id,
            'action' => 'restored',
        ]);
    }

    public function test_it_successfully_sets_an_outlet_as_main_outlet()
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $user = $this->createMerchantUser();
        $business = $user->business;

        // Current main outlet
        $mainOutlet = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Main Outlet Old',
            'is_active' => true,
            'is_main_outlet' => true,
        ]);

        // Branch outlet to become main
        $branchOutlet = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Branch Outlet New',
            'is_active' => true,
            'is_main_outlet' => false,
        ]);

        $this->actingAs($user);

        $result = $this->service->setMainOutlet($branchOutlet, $user);

        $this->assertTrue($result->is_main_outlet);
        $this->assertDatabaseHas('outlets', [
            'id' => $branchOutlet->id,
            'is_main_outlet' => true,
        ]);
        $this->assertDatabaseHas('outlets', [
            'id' => $mainOutlet->id,
            'is_main_outlet' => false,
        ]);
        $this->assertDatabaseHas('outlet_audit_logs', [
            'outlet_id' => $branchOutlet->id,
            'user_id' => $user->id,
            'action' => 'set_as_main',
        ]);
    }

    public function test_it_fails_to_set_inactive_outlet_as_main_outlet()
    {
        $this->expectException(\Illuminate\Validation\ValidationException::class);

        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        $user = $this->createMerchantUser();
        $business = $user->business;

        $inactiveOutlet = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Inactive Branch',
            'is_active' => false,
            'is_main_outlet' => false,
        ]);

        $this->actingAs($user);

        $this->service->setMainOutlet($inactiveOutlet, $user);
    }
}
