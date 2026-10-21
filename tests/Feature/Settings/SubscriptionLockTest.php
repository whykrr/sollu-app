<?php

namespace Tests\Feature\Settings;

use App\Enums\SubscriptionInvoice\Status as InvoiceStatus;
use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SubscriptionLockTest extends TestCase
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

    protected function createMerchantUser(): array
    {
        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = Business::create([
            'name' => 'Lock Test Business',
            'owner_name' => 'Owner',
            'email' => 'lock_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'business_type_id' => $type->id,
        ]);

        $user = User::create([
            'business_id' => $business->id,
            'name' => 'Lock User',
            'email' => 'user_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
            'is_root_user' => true,
        ]);

        setPermissionsTeamId($business->id);

        return [$user, $business];
    }

    public function test_active_subscription_has_is_locked_false(): void
    {
        [$user, $business] = $this->createMerchantUser();

        $plan = SubscriptionPlan::create([
            'code' => 'basic-plan',
            'name' => 'Paket Basic',
            'price_per_outlet' => 99000,
            'yearly_discount_percent' => 0,
            'is_active' => true,
        ]);

        Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'billing_cycle' => 'monthly',
            'is_trial' => false,
            'started_at' => now(),
            'expired_at' => now()->addDays(30),
        ]);

        $response = $this->actingAs($user, 'business')
            ->get(route('overview'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->where('subscription_lock.is_locked', false)
        );
    }

    public function test_pending_payment_triggers_lock_with_payment_pending_reason(): void
    {
        [$user, $business] = $this->createMerchantUser();

        $plan = SubscriptionPlan::create([
            'code' => 'basic-plan-2',
            'name' => 'Paket Basic',
            'price_per_outlet' => 99000,
            'yearly_discount_percent' => 0,
            'is_active' => true,
        ]);

        Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Pending,
            'billing_cycle' => 'monthly',
            'is_trial' => false,
            'started_at' => now(),
            'expired_at' => now()->addDays(30),
        ]);

        $invoice = Invoice::create([
            'business_id' => $business->id,
            'invoice_number' => 'INV-LOCK-TEST',
            'status' => InvoiceStatus::Open,
            'subtotal' => 99000,
            'tax_amount' => 0,
            'total_amount' => 99000,
            'due_date' => now()->addDays(3),
        ]);

        $response = $this->actingAs($user, 'business')
            ->get(route('overview'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->where('subscription_lock.is_locked', true)
            ->where('subscription_lock.lock_reason', 'payment_pending')
            ->where('subscription_lock.invoice_url', route('settings.billing.invoices.show', $invoice->invoice_number))
        );
    }

    public function test_expired_trial_triggers_lock_with_trial_expired_reason(): void
    {
        [$user, $business] = $this->createMerchantUser();

        $plan = SubscriptionPlan::create([
            'code' => 'micro-plan-3',
            'name' => 'Paket Mikro',
            'price_per_outlet' => 29000,
            'yearly_discount_percent' => 0,
            'has_trial' => true,
            'is_active' => true,
        ]);

        Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $plan->id,
            'status' => SubscriptionStatus::Active,
            'billing_cycle' => 'monthly',
            'is_trial' => true,
            'started_at' => now()->subDays(20),
            'expired_at' => now()->subDays(6), // Trial expired
        ]);

        $response = $this->actingAs($user, 'business')
            ->get(route('overview'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->where('subscription_lock.is_locked', true)
            ->where('subscription_lock.lock_reason', 'trial_expired')
            ->where('subscription_lock.plans_url', route('settings.billing.plans'))
        );
    }
}
