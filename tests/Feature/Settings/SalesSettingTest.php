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
            'sales_channels_b2b' => ['direct', 'wholesale'],
            'default_due_days_b2b' => 30,
            'default_terms_and_conditions_b2b' => 'Syarat khusus outlet cabang.',
            'b2b_invoice_prefix' => 'FAKTUR',
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
        $this->assertEquals('FAKTUR', $savedPrefix);
    }

    public function test_user_cannot_update_settings_for_outlet_belonging_to_another_business(): void
    {
        [$user, $business, $outlet] = $this->createMerchantUser();
        [$otherUser, $otherBusiness, $otherOutlet] = $this->createMerchantUser();

        $payload = [
            'outlet_id' => $otherOutlet->id, // Belongs to otherBusiness
            'allow_negative_stock_b2b' => true,
            'allow_custom_price_b2b' => true,
            'sales_channels_b2b' => ['direct'],
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
            'sales_channels_b2b' => ['invalid_channel_name'],
            'default_due_days_b2b' => -5,
            'b2b_invoice_prefix' => '',
        ];

        $response = $this->actingAs($user, 'business')
            ->from("http://{$this->appDomain}/settings/sales")
            ->put("http://{$this->appDomain}/settings/sales", $payload);

        $response->assertSessionHasErrors([
            'allow_negative_stock_b2b',
            'sales_channels_b2b.0',
            'default_due_days_b2b',
            'b2b_invoice_prefix',
        ]);
    }
}
