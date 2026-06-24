<?php

namespace Tests\Feature;

use App\Models\Outlet;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutletTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_user_can_view_outlets()
    {
        $user = User::first();
        $response = $this->actingAs($user)->get('/settings/outlets');
        $response->assertStatus(200);
    }

    public function test_user_can_create_outlet()
    {
        $user = User::first();
        $business = $user->business;

        // Give the business a plan with a limit of 5 outlets so it does not fail the limit check of 1
        $plan = \App\Models\SubscriptionPlan::create([
            'code' => 'plan-unlimited',
            'name' => 'Unlimited Plan',
            'price_per_outlet' => 50000,
            'max_outlet' => 5,
            'yearly_discount_percent' => 0,
        ]);
        \App\Models\Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'started_at' => now(),
            'expired_at' => now()->addDays(30),
        ]);

        $response = $this->actingAs($user)->post('/settings/outlets', [
            'name' => 'New Outlet',
            'address' => 'Jakarta',
        ]);
        $response->assertRedirect();
        
        $this->assertDatabaseHas('outlets', [
            'name' => 'New Outlet',
            'business_id' => $user->business_id,
        ]);
    }

    public function test_user_can_update_outlet()
    {
        $user = User::first();
        $outlet = Outlet::where('business_id', $user->business_id)->first();
        
        $response = $this->actingAs($user)->put('/settings/outlets/' . $outlet->id, [
            'name' => 'Updated Outlet Name',
            'address' => 'Bandung',
        ]);
        $response->assertRedirect();
        
        $this->assertDatabaseHas('outlets', [
            'id' => $outlet->id,
            'name' => 'Updated Outlet Name',
        ]);
    }

    public function test_user_can_disable_and_enable_outlet()
    {
        $user = User::first();
        $outlet = Outlet::where('business_id', $user->business_id)->first();
        
        $response = $this->actingAs($user)->delete('/settings/outlets/' . $outlet->id);
        $response->assertRedirect();
        
        $this->assertDatabaseHas('outlets', [
            'id' => $outlet->id,
            'is_active' => false,
        ]);

        $response = $this->actingAs($user)->put('/settings/outlets/' . $outlet->id . '/enabled');
        $response->assertRedirect();
        
        $this->assertDatabaseHas('outlets', [
            'id' => $outlet->id,
            'is_active' => true,
        ]);
    }

    public function test_user_can_soft_delete_and_restore_outlet()
    {
        $user = User::first();
        $outlet = Outlet::where('business_id', $user->business_id)->first();
        
        $response = $this->actingAs($user)->delete('/settings/outlets/' . $outlet->id . '/destroy');
        $response->assertRedirect();
        
        $this->assertSoftDeleted('outlets', [
            'id' => $outlet->id,
        ]);

        $response = $this->actingAs($user)->put('/settings/outlets/' . $outlet->id . '/restore');
        $response->assertRedirect();
        
        $this->assertDatabaseHas('outlets', [
            'id' => $outlet->id,
            'deleted_at' => null,
        ]);
    }

    public function test_trial_business_cannot_exceed_one_outlet()
    {
        $user = User::first();
        $business = $user->business;
        
        // Ensure there is already 1 outlet
        $this->assertEquals(1, $business->outlets()->count());

        // Attempting to create a second outlet should fail validation
        $response = $this->actingAs($user)->post('/settings/outlets', [
            'name' => 'Second Outlet',
            'address' => 'Surabaya',
        ]);
        
        $response->assertSessionHasErrors(['name']);
        
        $this->assertDatabaseMissing('outlets', [
            'name' => 'Second Outlet',
            'business_id' => $business->id,
        ]);
    }

    public function test_business_with_active_subscription_can_create_outlets_up_to_limit()
    {
        $user = User::first();
        $business = $user->business;

        // Create a subscription plan with limit = 2
        $plan = \App\Models\SubscriptionPlan::create([
            'code' => 'plan-standard',
            'name' => 'Standard Plan',
            'price_per_outlet' => 50000,
            'max_outlet' => 2,
            'yearly_discount_percent' => 0,
        ]);

        // Subscribe business to plan and activate it
        $subscription = \App\Models\Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'started_at' => now(),
            'expired_at' => now()->addDays(30),
        ]);

        // Creating 2nd outlet should succeed (total 2 outlets)
        $response = $this->actingAs($user)->post('/settings/outlets', [
            'name' => 'Second Outlet',
            'address' => 'Surabaya',
        ]);
        $response->assertRedirect();
        
        $this->assertDatabaseHas('outlets', [
            'name' => 'Second Outlet',
            'business_id' => $business->id,
        ]);

        // Attempting to create 3rd outlet should fail (exceeds limit of 2)
        $response = $this->actingAs($user)->post('/settings/outlets', [
            'name' => 'Third Outlet',
            'address' => 'Medan',
        ]);
        $response->assertSessionHasErrors(['name']);

        $this->assertDatabaseMissing('outlets', [
            'name' => 'Third Outlet',
            'business_id' => $business->id,
        ]);
    }

    public function test_business_cannot_restore_outlet_if_limit_reached()
    {
        $user = User::first();
        $business = $user->business;
        
        $firstOutlet = Outlet::where('business_id', $business->id)->first();
        
        // Soft delete the first outlet (active count becomes 0)
        $response = $this->actingAs($user)->delete('/settings/outlets/' . $firstOutlet->id . '/destroy');
        $response->assertRedirect();
        $this->assertSoftDeleted('outlets', ['id' => $firstOutlet->id]);

        // Create a new outlet (active count becomes 1, which reaches the limit of 1 for trial business)
        $response = $this->actingAs($user)->post('/settings/outlets', [
            'name' => 'Temporary Outlet',
            'address' => 'Surabaya',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('outlets', [
            'name' => 'Temporary Outlet',
            'business_id' => $business->id,
        ]);

        // Trying to restore the first outlet should fail because we are already at 1/1 active outlets
        $response = $this->actingAs($user)->put('/settings/outlets/' . $firstOutlet->id . '/restore');
        $response->assertSessionHasErrors(['name']);
        
        // Confirm first outlet is still soft deleted
        $this->assertSoftDeleted('outlets', ['id' => $firstOutlet->id]);
    }
}
