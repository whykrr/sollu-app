<?php

namespace Tests\Feature\Settings;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\InventoryCostingMethod;
use App\Enums\PermissionEnum;
use App\Enums\PlanEnum;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryItem;
use App\Models\Master\ProductItem;
use App\Models\Outlet;
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

        $outlet = Outlet::create([
            'business_id' => $user->business_id,
            'name' => 'Main Outlet',
            'is_active' => true,
        ]);

        $trackedProductItem = ProductItem::create([
            'business_id' => $user->business_id,
            'item_type' => 'variant_sku',
            'name' => 'Tracked Item',
            'track_inventory' => true,
            'is_active' => true,
        ]);

        $trackedInvItem = InventoryItem::create([
            'business_id' => $user->business_id,
            'product_item_id' => $trackedProductItem->id,
            'is_active' => true,
        ]);

        $untrackedProductItem = ProductItem::create([
            'business_id' => $user->business_id,
            'item_type' => 'variant_sku',
            'name' => 'Untracked Item',
            'track_inventory' => false,
            'is_active' => true,
        ]);

        InventoryItem::create([
            'business_id' => $user->business_id,
            'product_item_id' => $untrackedProductItem->id,
            'is_active' => true,
        ]);

        InventoryBalance::create([
            'business_id' => $user->business_id,
            'outlet_id' => $outlet->id,
            'inventory_item_id' => $trackedInvItem->id,
            'current_stock' => 10,
            'average_cost' => 15000,
            'last_cost' => 15000,
            'total_value' => 150000,
        ]);

        $response = $this->actingAs($user, 'business')->get("http://{$this->appDomain}/settings/inventory");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Settings/Inventory/Index')
            ->has('costingMethod')
            ->has('isConfigured')
            ->has('options')
            ->has('sodSettings')
            ->where('stats.tracked_items_count', 1)
            ->where('stats.total_stock_value', 150000)
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

    public function test_authorized_user_can_update_inventory_sod_settings(): void
    {
        $user = $this->createMerchantUser();
        $this->subscribeBusinessToPlan($user, PlanEnum::PRO);
        $user->givePermissionTo(PermissionEnum::BUSINESS_VIEW->value);
        $user->givePermissionTo(PermissionEnum::BUSINESS_UPDATE->value);

        $business = $user->business;
        $this->assertFalse($business->isInventorySodEnabled());

        $payload = [
            'enabled' => true,
            'allow_owner_bypass' => false,
            'rules' => [
                'stock_adjustment' => true,
                'stock_opname' => true,
                'stock_transfer_approval' => true,
                'stock_transfer_receive' => true,
                'purchase_order_receive' => true,
            ],
        ];

        $response = $this->actingAs($user, 'business')->put("http://{$this->appDomain}/settings/inventory/sod", $payload);

        $response->assertRedirect();
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::UPDATE_SUCCESS);

        $business->refresh();
        $this->assertTrue($business->isInventorySodEnabled());
        $this->assertFalse($business->allowsOwnerSodBypass());
        $this->assertTrue($business->getInventorySodSettings()['rules']['stock_adjustment']);
    }

    public function test_update_sod_settings_requires_valid_boolean_fields(): void
    {
        $user = $this->createMerchantUser();
        $this->subscribeBusinessToPlan($user, PlanEnum::PRO);
        $user->givePermissionTo(PermissionEnum::BUSINESS_VIEW->value);
        $user->givePermissionTo(PermissionEnum::BUSINESS_UPDATE->value);

        $response = $this->actingAs($user, 'business')->put("http://{$this->appDomain}/settings/inventory/sod", [
            'enabled' => 'not_a_boolean',
            'rules' => 'invalid_rules_array',
        ]);

        $response->assertSessionHasErrors(['enabled', 'rules']);
    }
}
