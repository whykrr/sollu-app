<?php

namespace Tests\Feature\Cockpit;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Models\CockpitUser;
use App\Models\SystemSetting;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ConfigControllerTest extends TestCase
{
    use RefreshDatabase;

    protected CockpitUser $admin;

    protected string $cockpitHost;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.testing.page_paths' => [
            resource_path('js/Pages'),
        ]]);
        $this->seed(DatabaseSeeder::class);

        $this->cockpitHost = config('domain.cockpit', 'cockpit.sollu.test');

        $this->admin = CockpitUser::create([
            'name' => 'Cockpit Admin',
            'email' => 'admin_config_test@sollu.test',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
    }

    public function test_can_view_config_page_with_settings(): void
    {
        SystemSetting::set('help_center_url', 'https://help.sollu.id');

        $response = $this->actingAs($this->admin, 'cockpit')
            ->get("http://{$this->cockpitHost}/config");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Cockpit/Config/Index')
            ->has('settings.help_center_url')
            ->where('settings.help_center_url', 'https://help.sollu.id')
        );
    }

    public function test_can_update_help_center_url_setting(): void
    {
        $response = $this->actingAs($this->admin, 'cockpit')
            ->put("http://{$this->cockpitHost}/config/settings", [
                'help_center_url' => 'https://wa.me/628123456789',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );

        $this->assertEquals('https://wa.me/628123456789', SystemSetting::get('help_center_url'));
    }

    public function test_auto_prefixes_https_when_scheme_missing(): void
    {
        $response = $this->actingAs($this->admin, 'cockpit')
            ->put("http://{$this->cockpitHost}/config/settings", [
                'help_center_url' => 'help.sollu.id',
            ]);

        $response->assertRedirect();
        $this->assertEquals('https://help.sollu.id', SystemSetting::get('help_center_url'));
    }

    public function test_app_inertia_shares_help_center_url(): void
    {
        SystemSetting::set('help_center_url', 'https://help.sollu.id');

        $type = \App\Models\BusinessType::create([
            'code' => 'retail',
            'name' => 'Retail',
            'sort_order' => 1,
            'is_visible' => true,
        ]);

        $business = \App\Models\Business::create([
            'name' => 'Test Business',
            'owner_name' => 'Owner',
            'email' => 'owner@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $user = \App\Models\User::create([
            'business_id' => $business->id,
            'name' => 'Test User',
            'email' => 'user@test.test',
            'password' => bcrypt('password'),
        ]);

        $appHost = config('domain.app', 'app.sollu.test');

        $response = $this->actingAs($user, 'business')
            ->get("http://{$appHost}/");

        $response->assertInertia(fn (Assert $page) => $page
            ->has('app.help_center_url')
            ->where('app.help_center_url', 'https://help.sollu.id')
        );
    }

    public function test_can_update_midtrans_payment_flag_setting(): void
    {
        $response = $this->actingAs($this->admin, 'cockpit')
            ->patch("http://{$this->cockpitHost}/config/feature-flag", [
                'feature_name' => 'midtrans_payment_enabled',
                'enabled' => true,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas(
            FlashDataVariable::SUCCESS->value,
            ResourceMessage::UPDATE_SUCCESS
        );

        $this->assertTrue(SystemSetting::isMidtransEnabled());
        $this->assertEquals('1', SystemSetting::get('midtrans_payment_enabled'));

        // Toggle back to false
        $response = $this->actingAs($this->admin, 'cockpit')
            ->patch("http://{$this->cockpitHost}/config/feature-flag", [
                'feature_name' => 'midtrans_payment_enabled',
                'enabled' => false,
            ]);

        $response->assertRedirect();
        $this->assertFalse(SystemSetting::isMidtransEnabled());
        $this->assertEquals('0', SystemSetting::get('midtrans_payment_enabled'));
    }
}
