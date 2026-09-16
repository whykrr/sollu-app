<?php

namespace Tests\Feature\Cockpit;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\PermissionEnum;
use App\Models\CockpitUser;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SubscriptionPlanTest extends TestCase
{
    use RefreshDatabase;

    protected CockpitUser $admin;

    protected string $cockpitHost;

    protected string $appHost;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.testing.page_paths' => [
            resource_path('js/Pages'),
        ]]);
        $this->seed(DatabaseSeeder::class);

        $this->cockpitHost = config('domain.cockpit', 'cockpit.sollu.test');
        $this->appHost = config('domain.app', 'app.sollu.test');

        $this->admin = CockpitUser::create([
            'name' => 'Cockpit Admin',
            'email' => 'admin_test@sollu.test',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
    }

    public function test_guest_cannot_access_cockpit_subscription_plans(): void
    {
        $response = $this->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->get("http://{$this->cockpitHost}/subscription-plans");

        $response->assertStatus(302);
        $response->assertRedirect(route('cockpit.login'));
    }

    public function test_admin_can_view_subscription_plans_index(): void
    {
        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->get("http://{$this->cockpitHost}/subscription-plans");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Cockpit/SubscriptionPlan/Index')
            ->has('plans', fn (Assert $plans) => $plans
                ->each(fn (Assert $plan) => $plan
                    ->hasAll(['id', 'code', 'name', 'price_per_outlet', 'yearly_discount_percent', 'is_active', 'features'])
                    ->etc()
                )
            )
        );
    }

    public function test_admin_can_sort_subscription_plans_by_price_and_name(): void
    {
        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->get("http://{$this->cockpitHost}/subscription-plans?sort=name&direction=desc");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Cockpit/SubscriptionPlan/Index')
            ->where('params.sort', 'name')
            ->where('params.direction', 'desc')
            ->has('plans')
        );

        $responseAsc = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->get("http://{$this->cockpitHost}/subscription-plans?sort=price_per_outlet&direction=asc");

        $responseAsc->assertStatus(200);
        $responseAsc->assertInertia(fn (Assert $page) => $page
            ->component('Cockpit/SubscriptionPlan/Index')
            ->where('params.sort', 'price_per_outlet')
            ->where('params.direction', 'asc')
            ->has('plans')
        );
    }

    public function test_admin_can_view_single_plan_json(): void
    {
        $plan = SubscriptionPlan::first();

        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->get("http://{$this->cockpitHost}/subscription-plans/{$plan->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $plan->id,
            'name' => $plan->name,
        ]);
    }

    public function test_admin_can_update_subscription_plan(): void
    {
        $plan = SubscriptionPlan::first();

        $payload = [
            'name' => 'Paket Mikro Super Updated',
            'price_per_outlet' => 75000,
            'yearly_discount_percent' => 25,
            'features' => [
                ['title' => 'Fitur Kasir Cepat', 'detail' => 'Checkout cepat dalam 3 detik'],
                ['title' => 'Laporan Harian', 'detail' => 'Laporan otomatis via email'],
            ],
            'is_active' => true,
        ];

        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->put("http://{$this->cockpitHost}/subscription-plans/{$plan->id}", $payload);

        $response->assertRedirect();
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::UPDATE_SUCCESS);

        $plan->refresh();
        $this->assertSame('Paket Mikro Super Updated', $plan->name);
        $this->assertEquals(75000, (float) $plan->price_per_outlet);
        $this->assertSame(25, $plan->yearly_discount_percent);
        $this->assertCount(2, $plan->features);
    }

    public function test_admin_can_toggle_plan_active_status(): void
    {
        $plan = SubscriptionPlan::first();
        $this->assertTrue($plan->is_active);

        // Deactivate
        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->post("http://{$this->cockpitHost}/subscription-plans/{$plan->id}/toggle-status");

        $response->assertRedirect();
        $plan->refresh();
        $this->assertFalse($plan->is_active);

        // Reactivate
        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->post("http://{$this->cockpitHost}/subscription-plans/{$plan->id}/toggle-status");

        $response->assertRedirect();
        $plan->refresh();
        $this->assertTrue($plan->is_active);
    }

    public function test_admin_can_create_subscription_plan(): void
    {
        $payload = [
            'code' => 'starter-plus',
            'name' => 'Paket Starter Plus',
            'price_per_outlet' => 89000,
            'yearly_discount_percent' => 15,
            'is_active' => true,
            'is_public' => true,
            'features' => [
                ['title' => 'Cetak Struk Bluetooth', 'detail' => 'Kompatibel dengan semua printer thermal 58/80mm'],
            ],
        ];

        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->post("http://{$this->cockpitHost}/subscription-plans", $payload);

        $response->assertRedirect();
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::CREATE_SUCCESS);

        $this->assertDatabaseHas('subscription_plans', [
            'code' => 'starter-plus',
            'name' => 'Paket Starter Plus',
            'is_active' => true,
            'is_public' => true,
        ]);
    }

    public function test_admin_can_toggle_plan_visibility(): void
    {
        $plan = SubscriptionPlan::first();
        $this->assertTrue($plan->is_public);

        // Hide from catalog
        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->post("http://{$this->cockpitHost}/subscription-plans/{$plan->id}/toggle-visibility");

        $response->assertRedirect();
        $plan->refresh();
        $this->assertFalse($plan->is_public);

        // Show back in catalog
        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->post("http://{$this->cockpitHost}/subscription-plans/{$plan->id}/toggle-visibility");

        $response->assertRedirect();
        $plan->refresh();
        $this->assertTrue($plan->is_public);
    }

    public function test_admin_can_update_plan_features_in_separate_endpoint(): void
    {
        $plan = SubscriptionPlan::first();
        $features = \App\Models\Feature::take(3)->get();
        $featureIds = $features->pluck('id')->all();

        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->put("http://{$this->cockpitHost}/subscription-plans/{$plan->id}/features", [
                'feature_ids' => $featureIds,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value);

        $this->assertCount(3, $plan->fresh()->systemFeatures);
        $this->assertEqualsCanonicalizing($featureIds, $plan->fresh()->systemFeatures->pluck('id')->all());
    }

    public function test_admin_cannot_delete_plan_with_existing_subscriptions(): void
    {
        $plan = SubscriptionPlan::first();

        $type = \App\Models\BusinessType::create([
            'code' => 'retail',
            'name' => 'Retail',
            'sort_order' => 1,
            'is_visible' => true,
        ]);

        $business = \App\Models\Business::create([
            'name' => 'Test Business',
            'owner_name' => 'Owner Test',
            'email' => 'owner@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        \App\Models\Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
        ]);

        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->delete("http://{$this->cockpitHost}/subscription-plans/{$plan->id}");

        $response->assertRedirect();
        $response->assertSessionHas(FlashDataVariable::FAILED->value);
        $this->assertDatabaseHas('subscription_plans', ['id' => $plan->id]);
    }

    public function test_admin_can_delete_unused_plan(): void
    {
        $plan = SubscriptionPlan::create([
            'code' => 'temporary-plan',
            'name' => 'Temporary Plan To Delete',
            'price_per_outlet' => 10000,
            'yearly_discount_percent' => 0,
            'is_active' => false,
            'is_public' => false,
        ]);

        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->delete("http://{$this->cockpitHost}/subscription-plans/{$plan->id}");

        $response->assertRedirect();
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::PURGE_SUCCESS);
        $this->assertDatabaseMissing('subscription_plans', ['id' => $plan->id]);
    }

    public function test_merchant_cannot_checkout_deactivated_plan(): void
    {
        $type = \App\Models\BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = \App\Models\Business::firstOrCreate(
            ['email' => 'merchant_biz@test.test'],
            [
                'name' => 'Merchant Business',
                'owner_name' => 'Merchant Owner',
                'phone' => '081234567891',
                'status' => 'active',
                'trial_end_at' => now()->addDays(14),
                'business_type_id' => $type->id,
            ]
        );

        $merchantUser = User::create([
            'business_id' => $business->id,
            'name' => 'Merchant User',
            'email' => 'merchant_user@test.test',
            'password' => bcrypt('password'),
        ]);

        setPermissionsTeamId($business->id);
        $merchantUser->givePermissionTo(PermissionEnum::BUSINESS_BILLING->value);

        $plan = SubscriptionPlan::first();
        $plan->update(['is_active' => false]);

        $response = $this->actingAs($merchantUser, 'business')
            ->withServerVariables(['HTTP_HOST' => $this->appHost])
            ->get("http://{$this->appHost}/settings/billing/checkout/{$plan->id}");

        $response->assertRedirect(route('settings.billing.plans'));
        $response->assertSessionHas(FlashDataVariable::WARNING->value, 'Paket langganan ini sudah tidak aktif.');
    }

    public function test_admin_can_search_merchants_for_plan_assignment(): void
    {
        $type = \App\Models\BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = \App\Models\Business::create([
            'name' => 'Kopi Kenangan Senopati',
            'owner_name' => 'Kenangan Owner',
            'email' => 'kopikenangan@test.test',
            'phone' => '081299998888',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->get("http://{$this->cockpitHost}/merchants/search?query=Kenangan");

        $response->assertStatus(200);
        $response->assertJsonFragment([
            'id' => $business->id,
            'name' => 'Kopi Kenangan Senopati',
        ]);
    }

    public function test_admin_can_create_plan_assigned_to_specific_merchant(): void
    {
        $type = \App\Models\BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = \App\Models\Business::create([
            'name' => 'Enterprise Client A',
            'owner_name' => 'Director A',
            'email' => 'client_a@test.test',
            'phone' => '081299991111',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $payload = [
            'code' => 'enterprise-custom-client-a',
            'name' => 'Enterprise Custom Plan A',
            'price_per_outlet' => 350000,
            'yearly_discount_percent' => 30,
            'is_active' => true,
            'is_public' => false,
            'business_id' => $business->id,
            'features' => [
                ['title' => 'Dedicated SLA & Support', 'detail' => '24/7 Dedicated Account Manager'],
            ],
        ];

        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->post("http://{$this->cockpitHost}/subscription-plans", $payload);

        $response->assertRedirect();
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::CREATE_SUCCESS);

        $this->assertDatabaseHas('subscription_plans', [
            'code' => 'enterprise-custom-client-a',
            'business_id' => $business->id,
            'is_public' => false,
        ]);
    }

    public function test_admin_can_update_plan_merchant_assignment(): void
    {
        $plan = SubscriptionPlan::first();

        $type = \App\Models\BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = \App\Models\Business::create([
            'name' => 'Client B',
            'owner_name' => 'Director B',
            'email' => 'client_b@test.test',
            'phone' => '081299992222',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        // Assign to business
        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->put("http://{$this->cockpitHost}/subscription-plans/{$plan->id}", [
                'name' => $plan->name,
                'price_per_outlet' => $plan->price_per_outlet,
                'yearly_discount_percent' => $plan->yearly_discount_percent,
                'is_active' => true,
                'is_public' => false,
                'business_id' => $business->id,
            ]);

        $response->assertRedirect();
        $this->assertSame($business->id, $plan->fresh()->business_id);

        // Remove assignment
        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->put("http://{$this->cockpitHost}/subscription-plans/{$plan->id}", [
                'name' => $plan->name,
                'price_per_outlet' => $plan->price_per_outlet,
                'yearly_discount_percent' => $plan->yearly_discount_percent,
                'is_active' => true,
                'is_public' => true,
                'business_id' => null,
            ]);

        $response->assertRedirect();
        $this->assertNull($plan->fresh()->business_id);
    }
}
