<?php

namespace Tests\Feature\Settings;

use App\Constants\FlashDataVariable;
use App\Enums\PermissionEnum;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SubscriptionControllerTest extends TestCase
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

    protected function createMerchantUser(): User
    {
        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = Business::create([
            'name' => 'Merchant Test Business',
            'owner_name' => 'Merchant Owner',
            'email' => 'merchant_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $user = User::create([
            'business_id' => $business->id,
            'name' => 'Merchant User',
            'email' => 'user_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        setPermissionsTeamId($business->id);

        return $user;
    }

    public function test_merchant_cannot_subscribe_to_foreign_custom_plan(): void
    {
        $user = $this->createMerchantUser();
        $user->givePermissionTo(PermissionEnum::BUSINESS_SUBSCRIPTION->value);

        $type = BusinessType::first();
        $otherBusiness = Business::create([
            'name' => 'Other Business',
            'owner_name' => 'Other Owner',
            'email' => 'other_'.uniqid().'@test.test',
            'phone' => '081299998888',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $foreignCustomPlan = SubscriptionPlan::create([
            'code' => 'other-custom-plan',
            'name' => 'Other Custom Plan',
            'price_per_outlet' => 750000,
            'yearly_discount_percent' => 0,
            'is_active' => true,
            'is_public' => false,
            'business_id' => $otherBusiness->id,
        ]);

        SubscriptionPlan::clearCache();

        $response = $this->actingAs($user, 'business')
            ->post("http://{$this->appDomain}/settings/subscriptions/subscribe", [
                'plan_id' => $foreignCustomPlan->id,
                'billing_cycle' => 'monthly',
                'payment_method' => 'manual',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas(FlashDataVariable::FAILED->value, 'Anda tidak memiliki akses ke paket langganan ini.');
    }

    public function test_merchant_with_custom_plan_cannot_subscribe_to_unassigned_public_plan(): void
    {
        $user = $this->createMerchantUser();
        $user->givePermissionTo(PermissionEnum::BUSINESS_SUBSCRIPTION->value);
        $business = $user->business;

        SubscriptionPlan::create([
            'code' => 'my-assigned-plan',
            'name' => 'My Assigned Plan',
            'price_per_outlet' => 600000,
            'yearly_discount_percent' => 0,
            'is_active' => true,
            'is_public' => false,
            'business_id' => $business->id,
        ]);

        $publicPlan = SubscriptionPlan::whereNull('business_id')->where('is_public', true)->first();
        SubscriptionPlan::clearCache();

        $response = $this->actingAs($user, 'business')
            ->post("http://{$this->appDomain}/settings/subscriptions/subscribe", [
                'plan_id' => $publicPlan->id,
                'billing_cycle' => 'monthly',
                'payment_method' => 'manual',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas(FlashDataVariable::FAILED->value, 'Bisnis Anda terikat pada paket kustom khusus. Silakan pilih paket yang tersedia untuk akun Anda.');
    }

    public function test_merchant_can_subscribe_to_own_assigned_custom_plan(): void
    {
        $user = $this->createMerchantUser();
        $user->givePermissionTo(PermissionEnum::BUSINESS_SUBSCRIPTION->value);
        $business = $user->business;

        $ownCustomPlan = SubscriptionPlan::create([
            'code' => 'exclusive-biz-plan',
            'name' => 'Exclusive Biz Plan',
            'price_per_outlet' => 550000,
            'yearly_discount_percent' => 0,
            'is_active' => true,
            'is_public' => false,
            'business_id' => $business->id,
        ]);

        SubscriptionPlan::clearCache();

        $response = $this->actingAs($user, 'business')
            ->post("http://{$this->appDomain}/settings/subscriptions/subscribe", [
                'plan_id' => $ownCustomPlan->id,
                'billing_cycle' => 'monthly',
                'payment_method' => 'manual',
            ]);

        $response->assertRedirect(route('settings.billing.index'));
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value);

        $this->assertDatabaseHas('subscriptions', [
            'business_id' => $business->id,
            'plan_id' => $ownCustomPlan->id,
            'billing_cycle' => 'monthly',
        ]);
    }
}
