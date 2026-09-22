<?php

namespace Tests\Feature\Customer;

use App\Constants\AuthorizationMessage;
use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Enums\PlanEnum;
use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Master\Customer;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class CustomerControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

    protected string $appDomain;

    protected function setUp(): void
    {
        parent::setUp();

        config(['inertia.testing.page_paths' => [
            resource_path('js/Pages'),
            resource_path('js/Pages/App'),
        ]]);

        $this->seed(DatabaseSeeder::class);
        $this->appDomain = config('domain.app', 'app.sollu.test');

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true, 'features' => [FeatureEnum::CUSTOMER_MANAGEMENT->value]]
        );

        $this->business = Business::create([
            'name' => 'Test Merchant',
            'owner_name' => 'Merchant Owner',
            'email' => 'merchant_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
            'settings' => [
                'active_features' => [FeatureEnum::CUSTOMER_MANAGEMENT->value],
            ],
        ]);

        $basicPlan = SubscriptionPlan::where('code', PlanEnum::BASIC->value)->first();
        Subscription::create([
            'business_id' => $this->business->id,
            'plan_id' => $basicPlan->id,
            'status' => SubscriptionStatus::Active,
            'billing_cycle' => 'monthly',
            'started_at' => now()->subDay(),
            'expired_at' => now()->addMonth(),
        ]);
        $this->business->clearMemoizedFeatures();

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Customer Manager',
            'email' => 'user_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        setPermissionsTeamId($this->business->id);

        foreach ([
            PermissionEnum::CUSTOMER_VIEW->value,
            PermissionEnum::CUSTOMER_CREATE->value,
            PermissionEnum::CUSTOMER_UPDATE->value,
            PermissionEnum::CUSTOMER_DELETE->value,
            PermissionEnum::CUSTOMER_LOYALTY->value,
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'business']);
            $this->user->givePermissionTo($perm);
        }
    }

    public function test_guest_cannot_access_customers(): void
    {
        $response = $this->get("http://{$this->appDomain}/customers");

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_view_customers(): void
    {
        $unauthorizedUser = User::create([
            'business_id' => $this->business->id,
            'name' => 'No Perm User',
            'email' => 'noperm_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($unauthorizedUser, 'business')
            ->get("http://{$this->appDomain}/customers");

        $response->assertStatus(302);
        $response->assertSessionHas(FlashDataVariable::FAILED->value, AuthorizationMessage::CANT_ACCESS_PAGE);
    }

    public function test_authorized_user_can_view_customers_page(): void
    {
        Customer::create([
            'business_id' => $this->business->id,
            'name' => 'Budi Santoso',
            'phone' => '081234567891',
            'email' => 'budi@test.com',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/customers");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Customer/CustomerIndex')
            ->has('customers.data', 1)
            ->where('customers.data.0.name', 'Budi Santoso')
        );
    }

    public function test_customers_list_is_isolated_to_business(): void
    {
        Customer::create([
            'business_id' => $this->business->id,
            'name' => 'Customer Tenant A',
            'phone' => '081234567891',
            'is_active' => true,
        ]);

        $otherBusiness = Business::create([
            'name' => 'Other Merchant',
            'owner_name' => 'Other Owner',
            'email' => 'other_'.uniqid().'@test.test',
            'phone' => '08999999999',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $this->business->business_type_id,
        ]);

        Customer::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Customer Tenant B',
            'phone' => '081234567892',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/customers");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Customer/CustomerIndex')
            ->has('customers.data', 1)
            ->where('customers.data.0.name', 'Customer Tenant A')
        );
    }

    public function test_user_can_filter_and_sort_customers(): void
    {
        Customer::create([
            'business_id' => $this->business->id,
            'name' => 'Anton',
            'phone' => '08111111111',
            'is_active' => true,
        ]);

        Customer::create([
            'business_id' => $this->business->id,
            'name' => 'Bambang',
            'phone' => '08222222222',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/customers?search=Anton&is_active=1&sort=name&direction=asc");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Customer/CustomerIndex')
            ->has('customers.data', 1)
            ->where('customers.data.0.name', 'Anton')
        );
    }

    public function test_authorized_user_can_create_customer(): void
    {
        $payload = [
            'name' => 'Citra Dewi',
            'phone' => '081333444555',
            'email' => 'citra@test.com',
            'gender' => 'female',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/customers", $payload);

        $response->assertStatus(302);
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::CREATE_SUCCESS);

        $this->assertDatabaseHas('customers', [
            'business_id' => $this->business->id,
            'name' => 'Citra Dewi',
            'phone' => '081333444555',
        ]);
    }

    public function test_authorized_user_can_update_customer(): void
    {
        $customer = Customer::create([
            'business_id' => $this->business->id,
            'name' => 'Old Name',
            'phone' => '081234567890',
            'is_active' => true,
        ]);

        $payload = [
            'name' => 'Updated Name',
            'phone' => '081234567890',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user, 'business')
            ->put("http://{$this->appDomain}/customers/{$customer->id}", $payload);

        $response->assertStatus(302);
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::UPDATE_SUCCESS);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Updated Name',
        ]);
    }

    public function test_authorized_user_cannot_update_customer_from_another_business(): void
    {
        $otherBusiness = Business::create([
            'name' => 'Other Merchant',
            'owner_name' => 'Other Owner',
            'email' => 'other_'.uniqid().'@test.test',
            'phone' => '08999999999',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $this->business->business_type_id,
        ]);

        $customer = Customer::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other Customer',
            'phone' => '08999999991',
            'is_active' => true,
        ]);

        $payload = [
            'name' => 'Hacked Name',
            'phone' => '08999999991',
        ];

        $response = $this->actingAs($this->user, 'business')
            ->put("http://{$this->appDomain}/customers/{$customer->id}", $payload);

        $response->assertStatus(403);
    }

    public function test_authorized_user_can_delete_customer(): void
    {
        $customer = Customer::create([
            'business_id' => $this->business->id,
            'name' => 'Delete Me',
            'phone' => '081234567899',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->delete("http://{$this->appDomain}/customers/{$customer->id}");

        $response->assertStatus(302);
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::DELETE_SUCCESS);

        $this->assertDatabaseMissing('customers', [
            'id' => $customer->id,
        ]);
    }

    public function test_authorized_user_cannot_delete_customer_from_another_business(): void
    {
        $otherBusiness = Business::create([
            'name' => 'Other Merchant',
            'owner_name' => 'Other Owner',
            'email' => 'other_'.uniqid().'@test.test',
            'phone' => '08999999999',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $this->business->business_type_id,
        ]);

        $customer = Customer::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other Customer',
            'phone' => '08999999991',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->delete("http://{$this->appDomain}/customers/{$customer->id}");

        $response->assertStatus(403);
    }

    public function test_authorized_user_can_view_customer_show(): void
    {
        $customer = Customer::create([
            'business_id' => $this->business->id,
            'name' => 'Detail Customer',
            'phone' => '081234567890',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/customers/{$customer->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'name',
                'phone',
                'summary' => ['total_transactions', 'total_spent', 'average_spent'],
            ],
        ]);
    }

    public function test_authorized_user_cannot_view_customer_show_from_another_business(): void
    {
        $otherBusiness = Business::create([
            'name' => 'Other Merchant',
            'owner_name' => 'Other Owner',
            'email' => 'other_'.uniqid().'@test.test',
            'phone' => '08999999999',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $this->business->business_type_id,
        ]);

        $customer = Customer::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other Customer',
            'phone' => '08999999991',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/customers/{$customer->id}");

        $response->assertStatus(403);
    }

    public function test_search_active_customers_is_scoped_to_business(): void
    {
        Customer::create([
            'business_id' => $this->business->id,
            'name' => 'Searchable Current',
            'phone' => '081234567890',
            'is_active' => true,
        ]);

        $otherBusiness = Business::create([
            'name' => 'Other Merchant',
            'owner_name' => 'Other Owner',
            'email' => 'other_'.uniqid().'@test.test',
            'phone' => '08999999999',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $this->business->business_type_id,
        ]);

        Customer::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Searchable Other',
            'phone' => '08999999991',
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/customers/search?q=Searchable");

        $response->assertStatus(200);
        $data = $response->json();
        $this->assertCount(1, $data);
        $this->assertEquals('Searchable Current', $data[0]['name']);
    }
}
