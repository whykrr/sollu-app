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
            'max_outlet' => 5,
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
        $this->assertSame(5, $plan->max_outlet);
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

    public function test_merchant_cannot_checkout_deactivated_plan(): void
    {
        $merchantUser = User::first();
        $merchantUser->givePermissionTo(PermissionEnum::BUSINESS_BILLING->value);

        $plan = SubscriptionPlan::first();
        $plan->update(['is_active' => false]);

        $response = $this->actingAs($merchantUser, 'business')
            ->withServerVariables(['HTTP_HOST' => $this->appHost])
            ->get("http://{$this->appHost}/settings/billing/checkout/{$plan->id}");

        $response->assertRedirect(route('settings.billing.plans'));
        $response->assertSessionHas(FlashDataVariable::WARNING->value, 'Paket langganan ini sudah tidak aktif.');
    }
}
