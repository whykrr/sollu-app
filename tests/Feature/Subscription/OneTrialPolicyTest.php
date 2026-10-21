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
use App\Services\App\Invoice\CompleteInvoiceService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OneTrialPolicyTest extends TestCase
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

    protected function createMerchantWithTrial(): array
    {
        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = Business::create([
            'name' => 'Trial Merchant Business',
            'owner_name' => 'Trial Owner',
            'email' => 'trial_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'has_used_trial' => true,
            'business_type_id' => $type->id,
        ]);

        $user = User::create([
            'business_id' => $business->id,
            'name' => 'Trial User',
            'email' => 'trial_user_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        setPermissionsTeamId($business->id);
        $user->givePermissionTo(PermissionEnum::BUSINESS_SUBSCRIPTION->value);

        $outlet = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Cabang Tunggal',
            'slug' => 'cabang-tunggal',
            'is_active' => true,
            'is_main_outlet' => true,
        ]);

        $microPlan = SubscriptionPlan::create([
            'code' => 'micro-plan',
            'name' => 'Paket Mikro',
            'price_per_outlet' => 29000,
            'yearly_discount_percent' => 0,
            'max_outlets' => 1,
            'has_trial' => true,
            'trial_duration_days' => 14,
            'is_active' => true,
            'is_public' => true,
        ]);

        $trialSubscription = Subscription::create([
            'business_id' => $business->id,
            'plan_id' => $microPlan->id,
            'status' => SubscriptionStatus::Active,
            'billing_cycle' => 'monthly',
            'is_trial' => true,
            'started_at' => now(),
            'expired_at' => now()->addDays(14),
        ]);

        $trialSubscription->subscriptionOutlets()->create([
            'outlet_id' => $outlet->id,
            'activated_at' => now(),
        ]);

        $basicPlan = SubscriptionPlan::create([
            'code' => 'basic-plan',
            'name' => 'Paket Basic',
            'price_per_outlet' => 99000,
            'yearly_discount_percent' => 0,
            'max_outlets' => null,
            'has_trial' => true,
            'trial_duration_days' => 14,
            'is_active' => true,
            'is_public' => true,
        ]);

        return [$user, $business, $trialSubscription, $microPlan, $basicPlan];
    }

    public function test_active_trial_is_eliminated_immediately_when_merchant_upgrades(): void
    {
        [$user, $business, $trialSub, $microPlan, $basicPlan] = $this->createMerchantWithTrial();

        $this->assertTrue($trialSub->is_trial);
        $this->assertSame(SubscriptionStatus::Active, $trialSub->status);

        $response = $this->actingAs($user, 'business')
            ->post("http://{$this->appDomain}/settings/subscriptions/change-plan", [
                'plan_id' => $basicPlan->id,
                'billing_cycle' => 'monthly',
                'payment_method' => 'manual',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value);

        // Verify trial remains active while invoice is pending (so merchant isn't locked out prematurely)
        $trialSub->refresh();
        $this->assertSame(SubscriptionStatus::Active, $trialSub->status);

        // Verify new subscription is non-trial (paid) with pending status
        $newSub = $business->subscriptions()->where('plan_id', $basicPlan->id)->first();
        $this->assertNotNull($newSub);
        $this->assertFalse((bool) $newSub->is_trial);
        $this->assertSame(SubscriptionStatus::Pending, $newSub->status);

        // Verify invoice was issued for full payment
        $invoice = $business->invoices()->latest()->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(99000, (float) $invoice->total_amount);

        // Complete the invoice payment and verify trial is canceled and new subscription is activated
        app(CompleteInvoiceService::class)->execute($invoice);

        $trialSub->refresh();
        $this->assertSame(SubscriptionStatus::Canceled, $trialSub->status);
        $this->assertNotNull($trialSub->canceled_at);

        $newSub->refresh();
        $this->assertSame(SubscriptionStatus::Active, $newSub->status);
    }

    public function test_business_has_used_trial_flag_persists_and_prevents_multiple_trials(): void
    {
        [$user, $business, $trialSub, $microPlan, $basicPlan] = $this->createMerchantWithTrial();

        $this->assertTrue((bool) $business->has_used_trial);

        // Subscribing again to another plan marks has_used_trial as true and creates paid subscription
        $this->actingAs($user, 'business')
            ->post("http://{$this->appDomain}/settings/subscriptions/change-plan", [
                'plan_id' => $basicPlan->id,
                'billing_cycle' => 'monthly',
                'payment_method' => 'manual',
            ]);

        $business->refresh();
        $this->assertTrue((bool) $business->has_used_trial);
    }
}
