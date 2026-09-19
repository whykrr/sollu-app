<?php

namespace Tests\Feature\Settings;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\InventoryCostingMethod;
use App\Enums\PermissionEnum;
use App\Enums\PlanEnum;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InventorySettingTest extends TestCase
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

    protected function subscribeBusinessToPlan(User $user, PlanEnum $planEnum = PlanEnum::PRO): void
    {
        setPermissionsTeamId($user->business_id);
        $plan = SubscriptionPlan::where('code', $planEnum->value)->first();
        Subscription::create([
            'business_id' => $user->business_id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'started_at' => Carbon::now()->subDays(1),
            'expired_at' => Carbon::now()->addDays(29),
        ]);
    }

    public function test_unauthenticated_user_cannot_access_inventory_settings_page(): void
    {
        $response = $this->get("http://{$this->appDomain}/settings/inventory");

        $response->assertStatus(302);
    }

    public function test_authorized_user_can_view_inventory_settings_page_with_expected_props(): void
    {
        $user = $this->createMerchantUser();
        $this->subscribeBusinessToPlan($user, PlanEnum::PRO);
        $user->givePermissionTo(PermissionEnum::BUSINESS_VIEW->value);

        $response = $this->actingAs($user, 'business')->get("http://{$this->appDomain}/settings/inventory");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Settings/Inventory/Index')
            ->has('costingMethod')
            ->has('isConfigured')
            ->has('options')
            ->has('stats')
        );
    }

    public function test_authorized_user_can_switch_inventory_costing_method_to_average(): void
    {
        $user = $this->createMerchantUser();
        $this->subscribeBusinessToPlan($user, PlanEnum::PRO);
        $user->givePermissionTo(PermissionEnum::BUSINESS_VIEW->value);
        $user->givePermissionTo(PermissionEnum::BUSINESS_UPDATE->value);

        $business = $user->business;
        $this->assertEquals(InventoryCostingMethod::FIFO, $business->getCostingMethod());

        $response = $this->actingAs($user, 'business')->put("http://{$this->appDomain}/settings/inventory", [
            'costing_method' => InventoryCostingMethod::AVERAGE->value,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::UPDATE_SUCCESS);

        $business->refresh();
        $this->assertEquals(InventoryCostingMethod::AVERAGE, $business->getCostingMethod());
        $this->assertTrue($business->isCostingMethodConfigured());
        $this->assertEquals('average', $business->settings['inventory_costing_method']);
    }

    public function test_switch_costing_method_validates_invalid_values(): void
    {
        $user = $this->createMerchantUser();
        $this->subscribeBusinessToPlan($user, PlanEnum::PRO);
        $user->givePermissionTo(PermissionEnum::BUSINESS_VIEW->value);
        $user->givePermissionTo(PermissionEnum::BUSINESS_UPDATE->value);

        $response = $this->actingAs($user, 'business')->put("http://{$this->appDomain}/settings/inventory", [
            'costing_method' => 'invalid_method',
        ]);

        $response->assertSessionHasErrors(['costing_method']);
    }
}
