<?php

namespace Tests\Feature\Settings;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\PermissionEnum;
use App\Enums\PlanEnum;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Outlet;
use App\Models\OutletSetting;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\Pos\PosSettingsPayloadBuilder;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class SalesSettingTest extends TestCase
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

    protected function createMerchantUser(bool $withPermission = true, bool $withFeature = true): array
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

        $outlet = Outlet::create([
            'business_id' => $business->id,
            'name' => 'Cabang Utama',
            'slug' => 'cabang-utama-'.uniqid(),
            'is_active' => true,
        ]);

        setPermissionsTeamId($business->id);

        if ($withPermission) {
            $user->givePermissionTo(PermissionEnum::SETTING_SALES->value);
        }

        if ($withFeature) {
            $plan = SubscriptionPlan::where('code', PlanEnum::PRO->value)->first();
            if ($plan) {
                Subscription::create([
                    'business_id' => $business->id,
                    'plan_id' => $plan->id,
                    'status' => 'active',
                    'start_date' => Carbon::now(),
                    'end_date' => Carbon::now()->addYear(),
                    'is_auto_renew' => true,
                ]);
            }
        }

        return [$user, $business, $outlet];
    }

    public function test_user_with_permission_can_access_sales_settings_page(): void
    {
        [$user, $business, $outlet] = $this->createMerchantUser();

        $response = $this->actingAs($user, 'business')
            ->get("http://{$this->appDomain}/settings/sales");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Settings/Sales/Index')
            ->has('outlets')
            ->has('selectedOutlet')
            ->has('salesSettings')
            ->where('salesSettings.allow_negative_stock_b2b', false)
            ->where('salesSettings.allow_custom_price_b2b', true)
        );
    }

    public function test_user_without_permission_cannot_access_sales_settings_page(): void
    {
        [$user, $business, $outlet] = $this->createMerchantUser(withPermission: false);

        $response = $this->actingAs($user, 'business')
            ->get("http://{$this->appDomain}/settings/sales");

        $response->assertRedirect();
        $response->assertSessionHas(FlashDataVariable::FAILED->value);
    }

    public function test_user_can_update_sales_settings_successfully(): void
    {
        [$user, $business, $outlet] = $this->createMerchantUser();

        $payload = [
            'outlet_id' => $outlet->id,
            'allow_negative_stock_b2b' => true,
            'allow_custom_price_b2b' => false,
            'pos_order_types' => ['walk_in', 'dine_in'],
            'default_due_days_b2b' => 30,
            'default_terms_and_conditions_b2b' => 'Syarat khusus outlet cabang.',
            'b2b_invoice_prefix' => 'INV',
        ];

        $response = $this->actingAs($user, 'business')
            ->from("http://{$this->appDomain}/settings/sales")
            ->put("http://{$this->appDomain}/settings/sales", $payload);

        $response->assertRedirect("http://{$this->appDomain}/settings/sales");
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::UPDATE_SUCCESS);

        $this->assertDatabaseHas('outlet_settings', [
            'outlet_id' => $outlet->id,
            'category' => 'sales',
            'key' => 'allow_negative_stock_b2b',
        ]);

        $this->assertDatabaseHas('outlet_settings', [
            'outlet_id' => $outlet->id,
            'category' => 'sales',
            'key' => 'b2b_invoice_prefix',
        ]);

        $savedPrefix = OutletSetting::where('outlet_id', $outlet->id)
            ->where('key', 'b2b_invoice_prefix')
            ->value('value');
        $this->assertEquals('INV', $savedPrefix);
    }

    public function test_user_cannot_update_settings_for_outlet_belonging_to_another_business(): void
    {
        [$user, $business, $outlet] = $this->createMerchantUser();
        [$otherUser, $otherBusiness, $otherOutlet] = $this->createMerchantUser();

        $payload = [
            'outlet_id' => $otherOutlet->id, // Belongs to otherBusiness
            'allow_negative_stock_b2b' => true,
            'allow_custom_price_b2b' => true,
            'pos_order_types' => ['walk_in'],
            'default_due_days_b2b' => 14,
            'default_terms_and_conditions_b2b' => 'T&C',
            'b2b_invoice_prefix' => 'INV',
        ];

        $response = $this->actingAs($user, 'business')
            ->from("http://{$this->appDomain}/settings/sales")
            ->put("http://{$this->appDomain}/settings/sales", $payload);

        $response->assertSessionHasErrors('outlet_id');
    }

    public function test_sales_setting_validation_fails_with_invalid_data(): void
    {
        [$user, $business, $outlet] = $this->createMerchantUser();

        $payload = [
            'outlet_id' => $outlet->id,
            'allow_negative_stock_b2b' => 'invalid-boolean',
            'allow_custom_price_b2b' => true,
            'pos_order_types' => ['invalid_order_type'],
            'default_due_days_b2b' => -5,
            'b2b_invoice_prefix' => '',
        ];

        $response = $this->actingAs($user, 'business')
            ->from("http://{$this->appDomain}/settings/sales")
            ->put("http://{$this->appDomain}/settings/sales", $payload);

        $response->assertSessionHasErrors([
            'allow_negative_stock_b2b',
            'pos_order_types.0',
            'default_due_days_b2b',
            'b2b_invoice_prefix',
        ]);
    }

    public function test_user_with_double_authorization_can_enable_supervisor_pin(): void
    {
        [$user, $business, $outlet] = $this->createMerchantUser(withFeature: true);

        $payload = [
            'outlet_id' => $outlet->id,
            'enable_supervisor_pin' => true,
        ];

        $response = $this->actingAs($user, 'business')
            ->from("http://{$this->appDomain}/settings/sales")
            ->put("http://{$this->appDomain}/settings/sales", $payload);

        $response->assertRedirect("http://{$this->appDomain}/settings/sales");
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::UPDATE_SUCCESS);

        $this->assertDatabaseHas('outlet_settings', [
            'outlet_id' => $outlet->id,
            'category' => 'pos',
            'key' => 'enable_supervisor_pin',
            'value' => 'true',
        ]);
    }

    public function test_user_without_double_authorization_cannot_enable_supervisor_pin(): void
    {
        [$user, $business, $outlet] = $this->createMerchantUser(withFeature: false);

        $payload = [
            'outlet_id' => $outlet->id,
            'enable_supervisor_pin' => true,
        ];

        $response = $this->actingAs($user, 'business')
            ->from("http://{$this->appDomain}/settings/sales")
            ->put("http://{$this->appDomain}/settings/sales", $payload);

        $response->assertStatus(403);
    }

    public function test_pos_settings_payload_builder_handles_double_authorization_correctly(): void
    {
        [$userWithFeature, $businessWithFeature, $outletWithFeature] = $this->createMerchantUser(withFeature: true);
        [$userWithoutFeature, $businessWithoutFeature, $outletWithoutFeature] = $this->createMerchantUser(withFeature: false);

        OutletSetting::create([
            'outlet_id' => $outletWithFeature->id,
            'category' => 'pos',
            'key' => 'enable_supervisor_pin',
            'value' => true,
        ]);

        OutletSetting::create([
            'outlet_id' => $outletWithoutFeature->id,
            'category' => 'pos',
            'key' => 'enable_supervisor_pin',
            'value' => true,
        ]);

        $builder = app(PosSettingsPayloadBuilder::class);

        $settingsWithFeature = $builder->buildStructuredSettings(
            $outletWithFeature,
            OutletSetting::where('outlet_id', $outletWithFeature->id)->get()
        );

        $settingsWithoutFeature = $builder->buildStructuredSettings(
            $outletWithoutFeature,
            OutletSetting::where('outlet_id', $outletWithoutFeature->id)->get()
        );

        $this->assertTrue($settingsWithFeature['enable_supervisor_pin']);
        $this->assertFalse($settingsWithoutFeature['enable_supervisor_pin']);
    }

    public function test_user_with_double_authorization_can_disable_supervisor_pin(): void
    {
        [$user, $business, $outlet] = $this->createMerchantUser(withFeature: true);

        $payload = [
            'outlet_id' => $outlet->id,
            'enable_supervisor_pin' => false,
        ];

        $response = $this->actingAs($user, 'business')
            ->from("http://{$this->appDomain}/settings/sales")
            ->put("http://{$this->appDomain}/settings/sales", $payload);

        $response->assertRedirect("http://{$this->appDomain}/settings/sales");
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::UPDATE_SUCCESS);

        $this->assertDatabaseHas('outlet_settings', [
            'outlet_id' => $outlet->id,
            'category' => 'pos',
            'key' => 'enable_supervisor_pin',
            'value' => 'false',
        ]);

        $builder = app(PosSettingsPayloadBuilder::class);
        $settings = $builder->buildStructuredSettings(
            $outlet,
            OutletSetting::where('outlet_id', $outlet->id)->get()
        );

        $this->assertFalse($settings['enable_supervisor_pin']);
    }

    public function test_user_with_only_tax_permission_can_access_sales_settings_and_defaults_to_tax_tab(): void
    {
        [$user, $business, $outlet] = $this->createMerchantUser(withFeature: true);
        $user->syncPermissions([PermissionEnum::SETTING_TAX->value]);

        $response = $this->actingAs($user, 'business')
            ->get("http://{$this->appDomain}/settings/sales");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Settings/Sales/Index')
            ->where('initialTab', 'tax')
        );
    }
}
