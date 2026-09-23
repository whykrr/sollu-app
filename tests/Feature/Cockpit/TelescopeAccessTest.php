<?php

namespace Tests\Feature\Cockpit;

use App\Models\CockpitUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Telescope\EntryType;
use Laravel\Telescope\IncomingEntry;
use Laravel\Telescope\Telescope;
use Tests\TestCase;

class TelescopeAccessTest extends TestCase
{
    use RefreshDatabase;

    protected CockpitUser $activeAdmin;

    protected CockpitUser $inactiveAdmin;

    protected string $cockpitHost;

    protected function setUp(): void
    {
        parent::setUp();

        config(['telescope.enabled' => true]);
        config(['telescope.domain' => 'cockpit.sollu.test']);
        config(['telescope.storage.database.connection' => 'sqlite']);

        if (! $this->app->providerIsLoaded(\Laravel\Telescope\TelescopeServiceProvider::class)) {
            $this->app->register(\Laravel\Telescope\TelescopeServiceProvider::class);
            $this->app->register(\App\Providers\TelescopeServiceProvider::class);
            
            $this->artisan('migrate', [
                '--path' => 'vendor/laravel/telescope/database/migrations'
            ]);
        }

        $this->cockpitHost = config('domain.cockpit', 'cockpit.sollu.test');

        $this->activeAdmin = CockpitUser::create([
            'name' => 'Active Admin',
            'email' => 'active_admin@sollu.test',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $this->inactiveAdmin = CockpitUser::create([
            'name' => 'Inactive Admin',
            'email' => 'inactive_admin@sollu.test',
            'password' => bcrypt('password'),
            'status' => 'inactive',
        ]);
    }

    public function test_unauthenticated_guest_is_redirected_to_cockpit_login(): void
    {
        $response = $this->get("http://{$this->cockpitHost}/telescope");

        $response->assertRedirect(route('cockpit.login'));
    }

    public function test_active_cockpit_user_can_access_telescope(): void
    {
        $response = $this->actingAs($this->activeAdmin, 'cockpit')
            ->get("http://{$this->cockpitHost}/telescope");

        $response->assertStatus(200);
    }

    public function test_cockpit_navigation_link_is_present_on_telescope(): void
    {
        $response = $this->actingAs($this->activeAdmin, 'cockpit')
            ->get("http://{$this->cockpitHost}/telescope");

        $response->assertStatus(200);
        $response->assertSee(route('cockpit.dashboard'), false);
        $response->assertSee('Kembali ke Cockpit', false);
        $response->assertSee('Ke Cockpit', false);
    }

    public function test_navigation_bar_only_displays_active_watchers(): void
    {
        $response = $this->actingAs($this->activeAdmin, 'cockpit')
            ->get("http://{$this->cockpitHost}/telescope");

        $response->assertStatus(200);

        // Active watchers should appear in the navigation
        if (Telescope::hasWatcher(\Laravel\Telescope\Watchers\RequestWatcher::class)) {
            $response->assertSee('to="/requests"', false);
            $response->assertSee('<span>Requests</span>', false);
        }

        // Inactive watchers should NOT appear in the navigation
        if (! Telescope::hasWatcher(\Laravel\Telescope\Watchers\ModelWatcher::class)) {
            $response->assertDontSee('to="/models"', false);
            $response->assertDontSee('<span>Models</span>', false);
        }

        if (! Telescope::hasWatcher(\Laravel\Telescope\Watchers\ViewWatcher::class)) {
            $response->assertDontSee('to="/views"', false);
            $response->assertDontSee('<span>Views</span>', false);
        }
    }

    public function test_inactive_cockpit_user_is_forbidden_from_telescope(): void
    {
        $response = $this->actingAs($this->inactiveAdmin, 'cockpit')
            ->get("http://{$this->cockpitHost}/telescope");

        $response->assertStatus(403);
    }

    public function test_regular_business_user_cannot_access_telescope(): void
    {
        $type = \App\Models\BusinessType::create(['name' => 'F&B', 'code' => 'fnb_iso']);
        $business = \App\Models\Business::create([
            'name' => 'Merchant Test',
            'owner_name' => 'Test Owner',
            'email' => 'merchant@test.com',
            'phone' => '081234567891',
            'business_type_id' => $type->id,
            'trial_end_at' => now()->addDays(14),
        ]);

        $regularUser = User::factory()->create(['business_id' => $business->id]);

        $response = $this->actingAs($regularUser, 'business')
            ->get("http://{$this->cockpitHost}/telescope");

        $response->assertRedirect(route('cockpit.login'));
    }

    public function test_allowed_emails_env_restricts_access(): void
    {
        putenv('TELESCOPE_ALLOWED_EMAILS=superadmin@sollu.test');

        $response = $this->actingAs($this->activeAdmin, 'cockpit')
            ->get("http://{$this->cockpitHost}/telescope");
        $response->assertStatus(403);

        $superAdmin = CockpitUser::create([
            'name' => 'Super Admin',
            'email' => 'superadmin@sollu.test',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $response = $this->actingAs($superAdmin, 'cockpit')
            ->get("http://{$this->cockpitHost}/telescope");
        $response->assertStatus(200);

        putenv('TELESCOPE_ALLOWED_EMAILS');
    }

    public function test_filtering_only_active_in_production(): void
    {
        $filter = Telescope::$filterUsing[0] ?? null;
        $this->assertNotNull($filter, 'Telescope filter should be registered');

        $normalEntry = new IncomingEntry([
            'response_status' => 200,
            'uri' => '/merchants',
        ]);
        $normalEntry->type = EntryType::REQUEST;

        // In non-production (testing environment), everything is recorded without filtering
        $this->assertTrue($filter($normalEntry));

        // When environment is production, filtering is applied
        $this->app['env'] = 'production';

        $this->assertFalse($filter($normalEntry));

        $failedRequestEntry = new IncomingEntry([
            'response_status' => 500,
            'uri' => '/api/crash',
        ]);
        $failedRequestEntry->type = EntryType::REQUEST;
        $this->assertTrue($filter($failedRequestEntry));

        $slowQueryEntry = new IncomingEntry([
            'slow' => true,
            'time' => 800,
            'sql' => 'select * from transactions',
        ]);
        $slowQueryEntry->type = EntryType::QUERY;
        $this->assertTrue($filter($slowQueryEntry));

        $fastQueryEntry = new IncomingEntry([
            'slow' => false,
            'time' => 2,
            'sql' => 'select * from users where id = ?',
        ]);
        $fastQueryEntry->type = EntryType::QUERY;
        $this->assertFalse($filter($fastQueryEntry));

        $this->app['env'] = 'testing';
    }
}
