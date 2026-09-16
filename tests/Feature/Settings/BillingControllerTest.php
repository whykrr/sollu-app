<?php

namespace Tests\Feature\Settings;

use App\Constants\FlashDataVariable;
use App\Enums\PermissionEnum;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BillingControllerTest extends TestCase
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

        $user = User::create([
            'business_id' => $business->id,
            'name' => 'Merchant User',
            'email' => 'user_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        setPermissionsTeamId($business->id);

        return $user;
    }

    public function test_unauthenticated_user_cannot_access_billing_page(): void
    {
        $response = $this->get("http://{$this->appDomain}/settings/billing");

        $response->assertStatus(302);
    }

    public function test_user_without_permission_cannot_access_billing_page(): void
    {
        $user = $this->createMerchantUser();
        $user->syncPermissions([]);
        $user->syncRoles([]);

        $response = $this->actingAs($user, 'business')->get("http://{$this->appDomain}/settings/billing");

        $response->assertRedirect();
        $response->assertSessionHas('failed');
    }

    public function test_authorized_user_can_access_billing_page_with_expected_props(): void
    {
        $user = $this->createMerchantUser();
        $user->givePermissionTo(PermissionEnum::BUSINESS_BILLING->value);

        $business = $user->business;
        $plan = SubscriptionPlan::first();

        $now = Carbon::now();
        Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'started_at' => $now->copy()->subDays(5),
            'expired_at' => $now->copy()->addDays(25),
        ]);

        $pendingInvoice = Invoice::create([
            'business_id' => $business->id,
            'invoice_number' => 'INV-TEST-001',
            'status' => 'open',
            'subtotal' => 100000,
            'tax_amount' => 11000,
            'total_amount' => 111000,
            'due_date' => $now->copy()->addDays(3),
        ]);

        $response = $this->actingAs($user, 'business')->get("http://{$this->appDomain}/settings/billing");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Settings/Billing/Index')
            ->has('subscription')
            ->has('pendingInvoice')
            ->has('invoices')
            ->where('pendingInvoice.id', $pendingInvoice->id)
        );
    }

    public function test_authorized_user_can_access_billing_page_with_void_invoices(): void
    {
        $user = $this->createMerchantUser();
        $user->givePermissionTo(PermissionEnum::BUSINESS_BILLING->value);

        $business = $user->business;

        Invoice::create([
            'business_id' => $business->id,
            'invoice_number' => 'INV-TEST-VOID-001',
            'status' => 'void',
            'subtotal' => 100000,
            'tax_amount' => 0,
            'total_amount' => 100000,
            'due_date' => Carbon::now()->addDays(3),
        ]);

        $response = $this->actingAs($user, 'business')->get("http://{$this->appDomain}/settings/billing");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Settings/Billing/Index')
            ->has('invoices.data', 1)
            ->where('invoices.data.0.status', 'void')
        );
    }

    public function test_merchant_with_assigned_custom_plan_only_sees_custom_plan_in_catalog(): void
    {
        $user = $this->createMerchantUser();
        $user->givePermissionTo(PermissionEnum::BUSINESS_BILLING->value);
        $business = $user->business;

        // Create custom plan assigned to this merchant
        $customPlan = SubscriptionPlan::create([
            'code' => 'custom-exclusive-plan',
            'name' => 'Paket Kustom Eksklusif',
            'price_per_outlet' => 500000,
            'yearly_discount_percent' => 10,
            'is_active' => true,
            'is_public' => false,
            'business_id' => $business->id,
        ]);

        SubscriptionPlan::clearCache();

        $response = $this->actingAs($user, 'business')->get("http://{$this->appDomain}/settings/billing/plans");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Settings/Billing/Plans')
            ->where('isCustomCatalog', true)
            ->has('plans', 1)
            ->where('plans.0.id', $customPlan->id)
            ->where('plans.0.code', 'custom-exclusive-plan')
        );
    }

    public function test_merchant_without_custom_plan_sees_standard_public_catalog(): void
    {
        $user = $this->createMerchantUser();
        $user->givePermissionTo(PermissionEnum::BUSINESS_BILLING->value);

        SubscriptionPlan::clearCache();

        $response = $this->actingAs($user, 'business')->get("http://{$this->appDomain}/settings/billing/plans");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Settings/Billing/Plans')
            ->where('isCustomCatalog', false)
            ->has('plans', fn ($plans) => $plans
                ->each(fn (Assert $plan) => $plan
                    ->where('is_public', true)
                    ->where('business_id', null)
                    ->etc()
                )
            )
        );
    }

    public function test_merchant_cannot_checkout_custom_plan_belonging_to_another_merchant(): void
    {
        $user = $this->createMerchantUser();
        $user->givePermissionTo(PermissionEnum::BUSINESS_BILLING->value);

        // Create another merchant and assign custom plan to that merchant
        $type = \App\Models\BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $otherBusiness = \App\Models\Business::create([
            'name' => 'Other Business',
            'owner_name' => 'Other Owner',
            'email' => 'other_biz_'.uniqid().'@test.test',
            'phone' => '081299993333',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $foreignCustomPlan = SubscriptionPlan::create([
            'code' => 'foreign-custom-plan',
            'name' => 'Foreign Custom Plan',
            'price_per_outlet' => 900000,
            'yearly_discount_percent' => 0,
            'is_active' => true,
            'is_public' => false,
            'business_id' => $otherBusiness->id,
        ]);

        SubscriptionPlan::clearCache();

        $response = $this->actingAs($user, 'business')
            ->get("http://{$this->appDomain}/settings/billing/checkout/{$foreignCustomPlan->id}");

        $response->assertRedirect(route('settings.billing.plans'));
        $response->assertSessionHas(FlashDataVariable::WARNING->value, 'Anda tidak memiliki akses ke paket langganan ini.');
    }

    public function test_merchant_with_custom_plan_cannot_checkout_unassigned_public_plan(): void
    {
        $user = $this->createMerchantUser();
        $user->givePermissionTo(PermissionEnum::BUSINESS_BILLING->value);
        $business = $user->business;

        // Assign custom plan to this business
        SubscriptionPlan::create([
            'code' => 'my-custom-plan',
            'name' => 'My Custom Plan',
            'price_per_outlet' => 450000,
            'yearly_discount_percent' => 0,
            'is_active' => true,
            'is_public' => false,
            'business_id' => $business->id,
        ]);

        $publicPlan = SubscriptionPlan::whereNull('business_id')->where('is_public', true)->first();
        SubscriptionPlan::clearCache();

        $response = $this->actingAs($user, 'business')
            ->get("http://{$this->appDomain}/settings/billing/checkout/{$publicPlan->id}");

        $response->assertRedirect(route('settings.billing.plans'));
        $response->assertSessionHas(FlashDataVariable::WARNING->value, 'Bisnis Anda terikat pada paket kustom khusus. Silakan pilih paket yang tersedia untuk akun Anda.');
    }
}
