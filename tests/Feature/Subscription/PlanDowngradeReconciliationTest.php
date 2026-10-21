<?php

namespace Tests\Feature\Subscription;

use App\Constants\FlashDataVariable;
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

class PlanDowngradeReconciliationTest extends TestCase
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

    protected function createMerchantWithTwoOutlets(): array
    {
        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = Business::create([
            'name' => 'Multi Outlet Store',
            'owner_name' => 'Owner Store',
            'email' => 'store_'.uniqid().'@test.test',
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
        $user->givePermissionTo(PermissionEnum::BUSINESS_SUBSCRIPTION->value);

        $outlet1 = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Cabang Pusat',
            'slug' => 'cabang-pusat',
            'is_active' => true,
            'is_main_outlet' => true,
        ]);

        $outlet2 = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Cabang Kedua',
            'slug' => 'cabang-kedua',
            'is_active' => true,
            'is_main_outlet' => false,
        ]);

        $basicPlan = SubscriptionPlan::create([
            'code' => 'basic-unlimited',
            'name' => 'Paket Basic',
            'price_per_outlet' => 99000,
            'yearly_discount_percent' => 0,
            'max_outlets' => null, // unlimited
            'is_active' => true,
            'is_public' => true,
        ]);

        $subscription = Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $basicPlan->id,
            'status' => SubscriptionStatus::Active,
            'billing_cycle' => 'monthly',
            'is_trial' => false,
            'started_at' => now(),
            'expired_at' => now()->addDays(30),
        ]);

        $subscription->subscriptionOutlets()->create([
            'outlet_id' => $outlet1->id,
            'activated_at' => now(),
        ]);
        $subscription->subscriptionOutlets()->create([
            'outlet_id' => $outlet2->id,
            'activated_at' => now(),
        ]);

        $microPlan = SubscriptionPlan::create([
            'code' => 'micro-one',
            'name' => 'Paket Mikro',
            'price_per_outlet' => 29000,
            'yearly_discount_percent' => 0,
            'max_outlets' => 1,
            'is_active' => true,
            'is_public' => true,
        ]);

        return [$user, $business, $outlet1, $outlet2, $basicPlan, $microPlan];
    }

    public function test_downgrade_rejected_when_active_outlets_exceed_plan_limit_without_selection(): void
    {
        [$user, $business, $outlet1, $outlet2, $basicPlan, $microPlan] = $this->createMerchantWithTwoOutlets();

        $response = $this->actingAs($user, 'business')
            ->post("http://{$this->appDomain}/settings/subscriptions/change-plan", [
                'plan_id' => $microPlan->id,
                'billing_cycle' => 'monthly',
                'payment_method' => 'manual',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas(
            FlashDataVariable::FAILED->value,
            "Paket {$microPlan->name} dibatasi maksimal {$microPlan->max_outlets} cabang outlet. Silakan pilih {$microPlan->max_outlets} cabang yang ingin tetap aktif."
        );

        $this->assertEquals(2, $business->outlets()->where('is_active', true)->count());
    }

    public function test_downgrade_succeeds_with_valid_selected_outlets_and_deactivates_unselected(): void
    {
        [$user, $business, $outlet1, $outlet2, $basicPlan, $microPlan] = $this->createMerchantWithTwoOutlets();

        $response = $this->actingAs($user, 'business')
            ->post("http://{$this->appDomain}/settings/subscriptions/change-plan", [
                'plan_id' => $microPlan->id,
                'billing_cycle' => 'monthly',
                'payment_method' => 'manual',
                'selected_outlet_ids' => [$outlet1->id],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value);

        // Verify outlet states
        $this->assertTrue((bool) $outlet1->fresh()->is_active);
        $this->assertFalse((bool) $outlet2->fresh()->is_active);
        $this->assertEquals(1, $business->outlets()->where('is_active', true)->count());

        // Verify new pending subscription has exactly 1 outlet attached
        $newSub = $business->subscriptions()->where('plan_id', $microPlan->id)->first();
        $this->assertNotNull($newSub);
        $this->assertSame($microPlan->id, $newSub->plan_id);
        $this->assertEquals(1, $newSub->subscriptionOutlets()->whereNull('deactivated_at')->count());
        $this->assertSame($outlet1->id, $newSub->subscriptionOutlets()->first()->outlet_id);

        // Verify invoice is generated for 1 outlet
        $invoice = $business->invoices()->latest()->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(29000, (float) $invoice->total_amount);
    }

    public function test_downgrade_rejected_when_selected_outlets_exceed_limit(): void
    {
        [$user, $business, $outlet1, $outlet2, $basicPlan, $microPlan] = $this->createMerchantWithTwoOutlets();

        $response = $this->actingAs($user, 'business')
            ->post("http://{$this->appDomain}/settings/subscriptions/change-plan", [
                'plan_id' => $microPlan->id,
                'billing_cycle' => 'monthly',
                'payment_method' => 'manual',
                'selected_outlet_ids' => [$outlet1->id, $outlet2->id],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas(
            FlashDataVariable::FAILED->value,
            "Jumlah cabang yang dipilih melebihi kuota paket {$microPlan->name} (Maksimal {$microPlan->max_outlets} cabang)."
        );
    }

    public function test_downgrade_rejected_when_foreign_outlet_provided(): void
    {
        [$user, $business, $outlet1, $outlet2, $basicPlan, $microPlan] = $this->createMerchantWithTwoOutlets();

        $otherBusiness = Business::create([
            'name' => 'Foreign Business',
            'owner_name' => 'Foreign Owner',
            'email' => 'foreign_'.uniqid().'@test.test',
            'phone' => '081299990000',
            'status' => 'active',
            'business_type_id' => $business->business_type_id,
        ]);

        $foreignOutlet = Outlet::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Foreign Outlet',
            'slug' => 'foreign-outlet',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'business')
            ->post("http://{$this->appDomain}/settings/subscriptions/change-plan", [
                'plan_id' => $microPlan->id,
                'billing_cycle' => 'monthly',
                'payment_method' => 'manual',
                'selected_outlet_ids' => [$foreignOutlet->id],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas(
            FlashDataVariable::FAILED->value,
            'Terdapat cabang yang tidak valid atau bukan milik bisnis Anda.'
        );
    }
}
