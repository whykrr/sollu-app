<?php

namespace Tests\Feature\Outlet;

use App\Enums\PermissionEnum;
use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Outlet;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OutletQuotaLimitTest extends TestCase
{
    use RefreshDatabase;

    protected string $appDomain;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.testing.page_paths' => [resource_path('js/Pages/App')]]);
        $this->seed(DatabaseSeeder::class);
        $this->appDomain = config('domain.app', 'app.sollu.test');
    }

    protected function createMerchantWithPlan(?int $maxOutlets = 1): array
    {
        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = Business::create([
            'name' => 'Quota Test Store',
            'owner_name' => 'Quota Owner',
            'email' => 'quota_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'business_type_id' => $type->id,
        ]);

        $user = User::create([
            'business_id' => $business->id,
            'name' => 'Merchant User',
            'email' => 'user_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        setPermissionsTeamId($business->id);
        $user->givePermissionTo([
            PermissionEnum::OUTLET_CREATE->value,
            PermissionEnum::OUTLET_UPDATE->value,
        ]);

        $plan = SubscriptionPlan::create([
            'code' => $maxOutlets ? 'micro-plan-'.$maxOutlets : 'basic-plan-unlimited',
            'name' => $maxOutlets ? 'Paket Mikro' : 'Paket Basic',
            'price_per_outlet' => 29000,
            'yearly_discount_percent' => 0,
            'max_outlets' => $maxOutlets,
            'is_active' => true,
            'is_public' => true,
        ]);

        $sub = Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'billing_cycle' => 'monthly',
            'is_trial' => false,
            'started_at' => now(),
            'expired_at' => now()->addDays(30),
        ]);

        $outlet1 = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Cabang 1',
            'slug' => 'cabang-1',
            'is_active' => true,
            'is_main_outlet' => true,
        ]);

        $sub->subscriptionOutlets()->create([
            'outlet_id' => $outlet1->id,
            'activated_at' => now(),
        ]);

        return [$user, $business, $outlet1, $plan];
    }

    public function test_merchant_on_micro_plan_blocked_from_creating_outlet_exceeding_quota(): void
    {
        [$user, $business, $outlet1, $plan] = $this->createMerchantWithPlan(maxOutlets: 1);

        $response = $this->actingAs($user, 'business')
            ->post("http://{$this->appDomain}/settings/outlets", [
                'name' => 'Cabang 2 Ilegal',
                'phone' => '081298765432',
                'email' => 'cabang2@test.test',
                'address' => 'Jl. Merdeka No. 2',
                'timezone' => 'Asia/Jakarta',
            ]);

        $response->assertSessionHasErrors('name');
        $this->assertEquals(1, $business->outlets()->count());
    }

    public function test_merchant_on_micro_plan_blocked_from_activating_inactive_outlet_when_quota_reached(): void
    {
        [$user, $business, $outlet1, $plan] = $this->createMerchantWithPlan(maxOutlets: 1);

        $inactiveOutlet = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Cabang Nonaktif',
            'slug' => 'cabang-nonaktif',
            'is_active' => false,
            'is_main_outlet' => false,
        ]);

        $response = $this->actingAs($user, 'business')
            ->put("http://{$this->appDomain}/settings/outlets/{$inactiveOutlet->id}/enabled");

        $response->assertSessionHasErrors('error');
        $this->assertFalse((bool) $inactiveOutlet->fresh()->is_active);
    }

    public function test_merchant_on_unlimited_basic_plan_can_create_new_outlet(): void
    {
        [$user, $business, $outlet1, $plan] = $this->createMerchantWithPlan(maxOutlets: null);

        $response = $this->actingAs($user, 'business')
            ->post("http://{$this->appDomain}/settings/outlets", [
                'name' => 'Cabang 2 Sukses',
                'phone' => '081298765432',
                'email' => 'cabang2@test.test',
                'address' => 'Jl. Merdeka No. 2',
                'timezone' => 'Asia/Jakarta',
            ]);

        $response->assertRedirect();
        $this->assertEquals(2, $business->outlets()->count());
    }
}
