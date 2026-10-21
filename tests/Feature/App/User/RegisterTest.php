<?php

namespace Tests\Feature\App\User;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\SubscriptionStatus;
use App\Events\User\BusinessRegistered;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\VerifyEmailBusiness;
use App\Notifications\WelcomeUser;
use App\Services\App\Outlet\OutletProvisioningService;
use Database\Seeders\Production\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    public function test_register_page_can_be_rendered(): void
    {
        BusinessType::factory()->forType('restaurant')->create();

        $response = $this->get(route('register'));

        $response->assertStatus(200);
    }

    public function test_register_page_displays_business_types_and_subscription_plans(): void
    {
        BusinessType::clearCache();

        $type = BusinessType::factory()->create([
            'name' => 'F&B Restaurant',
            'code' => 'fnb',
            'sort_order' => 1,
            'is_visible' => true,
        ]);

        $plan = SubscriptionPlan::create([
            'code' => 'micro',
            'name' => 'Paket Mikro',
            'price_per_outlet' => 59000,
            'yearly_discount_percent' => 20,
            'max_outlets' => 1,
            'has_trial' => true,
            'trial_duration_days' => 14,
            'is_active' => true,
            'is_public' => true,
        ]);

        $response = $this->get(route('register'));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('User/Register', false)
            ->has('business_types', 1)
            ->where('business_types.0.value', $type->id)
            ->has('plans', 1)
            ->where('plans.0.id', $plan->id)
            ->where('plans.0.name', 'Paket Mikro')
            ->where('plans.0.has_trial', true)
            ->where('plans.0.max_outlets', 1)
        );
    }

    public function test_register_page_parses_external_cta_query_parameters(): void
    {
        $response = $this->get(route('register', [
            'plan' => 'micro',
            'trial' => 'false',
            'billing_cycle' => 'yearly',
        ]));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('User/Register', false)
            ->where('initial_plan_code', 'micro')
            ->where('initial_use_trial', false)
            ->where('initial_billing_cycle', 'yearly')
        );
    }

    public function test_user_can_register_business_with_trial_and_notifications_are_queued(): void
    {
        Notification::fake();
        Event::fake([BusinessRegistered::class]);

        $this->seed(RolePermissionSeeder::class);

        $type = BusinessType::factory()->forType('minimarket')->create();
        $plan = SubscriptionPlan::create([
            'code' => 'basic',
            'name' => 'Paket Basic',
            'price_per_outlet' => 129000,
            'yearly_discount_percent' => 20,
            'max_outlets' => null,
            'has_trial' => true,
            'trial_duration_days' => 14,
            'is_active' => true,
            'is_public' => true,
        ]);

        $provisioningMock = Mockery::mock(OutletProvisioningService::class);
        $provisioningMock->shouldReceive('provisionAll')->once()->andReturnNull();
        $this->app->instance(OutletProvisioningService::class, $provisioningMock);

        $payload = [
            'plan_id' => $plan->id,
            'use_trial' => true,
            'name' => 'Warung Makan Barokah',
            'owner_name' => 'Ahmad Dahlan',
            'outlet_name' => 'Pusat Solo',
            'email' => 'ahmad@barokah.test',
            'phone' => '081234567890',
            'business_type_id' => $type->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post(route('register.store'), $payload);

        $response->assertRedirect(route('overview'));
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::REGISTER_SUCCESS);

        // Verify authenticated as business guard
        $this->assertTrue(Auth::guard('business')->check());
        $user = Auth::guard('business')->user();
        $this->assertEquals('ahmad@barokah.test', $user->email);

        // Verify business has_used_trial
        $this->assertTrue($user->business->has_used_trial);

        // Verify subscription is trial & active
        $subscription = $user->business->subscriptions()->first();
        $this->assertNotNull($subscription);
        $this->assertTrue($subscription->is_trial);
        $this->assertEquals(SubscriptionStatus::Active, $subscription->status);

        // Verify async notifications were dispatched
        Notification::assertSentTo(
            $user,
            VerifyEmailBusiness::class
        );
        Notification::assertSentTo(
            $user,
            WelcomeUser::class
        );

        Event::assertDispatched(BusinessRegistered::class);
    }

    public function test_user_can_register_business_with_paid_plan_and_is_redirected_to_invoice_payment(): void
    {
        Notification::fake();
        Event::fake([BusinessRegistered::class]);

        $this->seed(RolePermissionSeeder::class);

        $type = BusinessType::factory()->forType('minimarket')->create();
        $plan = SubscriptionPlan::create([
            'code' => 'micro_paid',
            'name' => 'Paket Mikro Paid',
            'price_per_outlet' => 59000,
            'yearly_discount_percent' => 0,
            'max_outlets' => 1,
            'has_trial' => true,
            'is_active' => true,
            'is_public' => true,
        ]);

        $provisioningMock = Mockery::mock(OutletProvisioningService::class);
        $provisioningMock->shouldReceive('provisionAll')->once()->andReturnNull();
        $this->app->instance(OutletProvisioningService::class, $provisioningMock);

        $payload = [
            'plan_id' => $plan->id,
            'use_trial' => false,
            'billing_cycle' => 'monthly',
            'name' => 'Kopi Direct Paid',
            'owner_name' => 'Bayu Pratama',
            'outlet_name' => 'Cabang Tebet',
            'email' => 'bayu@kopidirect.test',
            'phone' => '081234567899',
            'business_type_id' => $type->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post(route('register.store'), $payload);

        $user = Auth::guard('business')->user();
        $this->assertNotNull($user);

        // Verify business has_used_trial is false
        $this->assertFalse($user->business->has_used_trial);

        // Verify subscription is pending and NOT trial
        $subscription = $user->business->subscriptions()->first();
        $this->assertNotNull($subscription);
        $this->assertFalse($subscription->is_trial);
        $this->assertEquals(SubscriptionStatus::Pending, $subscription->status);

        // Verify invoice was generated
        $invoice = $user->business->invoices()->first();
        $this->assertNotNull($invoice);
        $this->assertEquals(59000, (float) $invoice->total_amount);

        // Verify redirected to billing index with open_invoice query param
        $response->assertRedirect(route('settings.billing.index', [
            'open_invoice' => $invoice->invoice_number,
        ]));
    }

    public function test_registration_validation_fails_with_invalid_data(): void
    {
        $response = $this->post(route('register.store'), [
            'name' => '',
            'email' => 'not-an-email',
            'business_type_id' => 999999,
        ]);

        $response->assertSessionHasErrors(['plan_id', 'use_trial', 'name', 'owner_name', 'outlet_name', 'email', 'phone', 'business_type_id', 'password']);
    }

    public function test_former_employee_can_register_new_business_with_same_email(): void
    {
        Notification::fake();
        Event::fake([BusinessRegistered::class]);
        $this->seed(RolePermissionSeeder::class);

        $type = BusinessType::factory()->forType('minimarket')->create();
        $plan = SubscriptionPlan::create([
            'code' => 'plan_emp',
            'name' => 'Plan Emp',
            'price_per_outlet' => 50000,
            'yearly_discount_percent' => 0,
            'has_trial' => true,
            'is_active' => true,
            'is_public' => true,
        ]);

        $provisioningMock = Mockery::mock(OutletProvisioningService::class);
        $provisioningMock->shouldReceive('provisionAll')->once()->andReturnNull();
        $this->app->instance(OutletProvisioningService::class, $provisioningMock);

        // Pre-existing employee in another business
        $otherBusiness = Business::create([
            'name' => 'Previous Employer',
            'owner_name' => 'Old Boss',
            'email' => 'boss_'.uniqid().'@test.test',
            'phone' => '081122334455',
            'status' => 'active',
            'business_type_id' => $type->id,
        ]);

        $employeeEmail = 'budi.barista@test.test';
        $employeePhone = '081234567899';

        User::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Budi Barista',
            'email' => $employeeEmail,
            'phone' => $employeePhone,
            'password' => bcrypt('password123'),
            'is_root_user' => false,
        ]);

        // Budi now starts his own coffee shop
        $payload = [
            'plan_id' => $plan->id,
            'use_trial' => true,
            'name' => 'Kopi Budi Senja',
            'owner_name' => 'Budi Mandiri',
            'outlet_name' => 'Kedai Pusat',
            'email' => $employeeEmail,
            'phone' => $employeePhone,
            'business_type_id' => $type->id,
            'password' => 'kopi123456',
            'password_confirmation' => 'kopi123456',
        ];

        $response = $this->post(route('register.store'), $payload);

        $response->assertRedirect(route('overview'));
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::REGISTER_SUCCESS);

        // Verify Budi is authenticated as root user of new business
        $this->assertTrue(Auth::guard('business')->check());
        $newRoot = Auth::guard('business')->user();
        $this->assertEquals($employeeEmail, $newRoot->email);
        $this->assertTrue((bool) $newRoot->is_root_user);
        $this->assertNotEquals($otherBusiness->id, $newRoot->business_id);
    }

    public function test_existing_root_owner_cannot_register_new_business_with_same_email(): void
    {
        $type = BusinessType::factory()->forType('minimarket')->create();
        $plan = SubscriptionPlan::create([
            'code' => 'plan_owner',
            'name' => 'Plan Owner',
            'price_per_outlet' => 50000,
            'yearly_discount_percent' => 0,
            'has_trial' => true,
            'is_active' => true,
            'is_public' => true,
        ]);

        $existingOwnerEmail = 'owner.active@test.test';

        $existingBusiness = Business::create([
            'name' => 'Active Business',
            'owner_name' => 'Active Owner',
            'email' => $existingOwnerEmail,
            'phone' => '081999888777',
            'status' => 'active',
            'business_type_id' => $type->id,
        ]);

        User::create([
            'business_id' => $existingBusiness->id,
            'name' => 'Active Owner',
            'email' => $existingOwnerEmail,
            'phone' => '081999888777',
            'password' => bcrypt('password123'),
            'is_root_user' => true,
        ]);

        $payload = [
            'plan_id' => $plan->id,
            'use_trial' => true,
            'name' => 'Second Business',
            'owner_name' => 'Active Owner',
            'outlet_name' => 'Second Outlet',
            'email' => $existingOwnerEmail,
            'phone' => '081999888777',
            'business_type_id' => $type->id,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->post(route('register.store'), $payload);

        $response->assertSessionHasErrors(['email', 'phone']);
    }
}
