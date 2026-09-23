<?php

namespace Tests\Feature\Auth;

use App\Constants\AuthorizationMessage;
use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\CockpitUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Tests\TestCase;

class SessionIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_cockpit_subdomain_uses_cockpit_session_cookie(): void
    {
        $cockpitHost = config('domain.cockpit', 'cockpit.sollu.test');

        $responseCockpit = $this->withServerVariables(['HTTP_HOST' => $cockpitHost])
            ->get("http://{$cockpitHost}/login");

        $this->assertSame('sollu_cockpit_session', config('session.cookie'));
        $responseCockpit->assertCookie('sollu_cockpit_session');
    }

    public function test_app_subdomain_uses_app_session_cookie(): void
    {
        $appHost = config('domain.app', 'app.sollu.test');

        $responseApp = $this->withServerVariables(['HTTP_HOST' => $appHost])
            ->get("http://{$appHost}/login");

        $this->assertSame('sollu_app_session', config('session.cookie'));
        $responseApp->assertCookie('sollu_app_session');
    }

    public function test_cockpit_impersonate_generates_token_and_redirects_to_app(): void
    {
        $cockpitUser = CockpitUser::create([
            'name' => 'Cockpit Admin',
            'email' => 'admin@sollu.test',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $type = BusinessType::create([
            'name' => 'F&B',
            'code' => 'fnb_iso',
            'is_visible' => true,
        ]);

        $business = Business::create([
            'name' => 'Merchant Test',
            'owner_name' => 'Test Owner',
            'email' => 'merchant@sollu.test',
            'phone' => '081234567891',
            'business_type_id' => $type->id,
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
        ]);

        $merchantUser = User::create([
            'business_id' => $business->id,
            'name' => 'Merchant Owner',
            'email' => 'owner@sollu.test',
            'password' => bcrypt('password'),
        ]);

        $cockpitHost = config('domain.cockpit', 'cockpit.sollu.test');
        $appHost = config('domain.app', 'app.sollu.test');

        $response = $this->actingAs($cockpitUser, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $cockpitHost])
            ->get(route('cockpit.merchants.impersonate', [
                'id' => $business->id,
                'userId' => $merchantUser->id,
            ]));

        $response->assertStatus(302);
        $redirectUrl = $response->headers->get('Location');
        $this->assertStringContainsString("http://{$appHost}/impersonate/", $redirectUrl);

        $token = basename(parse_url($redirectUrl, PHP_URL_PATH));
        $this->assertNotEmpty($token);

        $cachedPayload = Cache::get("impersonate:token:{$token}");
        $this->assertNotNull($cachedPayload);
        $this->assertSame($merchantUser->id, $cachedPayload['user_id']);
        $this->assertSame($business->id, $cachedPayload['business_id']);
        $this->assertSame($cockpitUser->id, $cachedPayload['admin_id']);
    }

    public function test_app_impersonate_authenticates_merchant_and_consumes_token_atomically(): void
    {
        $type = BusinessType::create([
            'name' => 'F&B',
            'code' => 'fnb_iso2',
            'is_visible' => true,
        ]);

        $business = Business::create([
            'name' => 'Merchant Test 2',
            'owner_name' => 'Test Owner 2',
            'email' => 'merchant2@sollu.test',
            'phone' => '081234567892',
            'business_type_id' => $type->id,
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
        ]);

        $merchantUser = User::create([
            'business_id' => $business->id,
            'name' => 'Merchant Owner 2',
            'email' => 'owner2@sollu.test',
            'password' => bcrypt('password'),
        ]);

        $token = Str::random(64);
        Cache::put("impersonate:token:{$token}", [
            'user_id' => $merchantUser->id,
            'business_id' => $business->id,
            'admin_id' => (string) Str::uuid(),
            'created_at' => now()->timestamp,
        ], now()->addMinutes(2));

        $appHost = config('domain.app', 'app.sollu.test');

        $response = $this->withServerVariables(['HTTP_HOST' => $appHost])
            ->get(route('impersonate.authenticate', ['token' => $token]));

        $response->assertRedirect(route('overview'));
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::IMPERSONATE_SUCCESS);

        $this->assertTrue(Auth::guard('business')->check());
        $this->assertSame($merchantUser->id, Auth::guard('business')->id());

        // Token must be consumed and removed (atomic pull)
        $this->assertNull(Cache::get("impersonate:token:{$token}"));
    }

    public function test_app_impersonate_fails_with_invalid_or_expired_token(): void
    {
        $appHost = config('domain.app', 'app.sollu.test');

        $response = $this->withServerVariables(['HTTP_HOST' => $appHost])
            ->get(route('impersonate.authenticate', ['token' => 'invalid-random-token']));

        $response->assertRedirect(route('login'));
        $response->assertSessionHas(FlashDataVariable::FAILED->value, AuthorizationMessage::IMPERSONATE_INVALID);

        $this->assertFalse(Auth::guard('business')->check());
    }

    public function test_cockpit_logout_only_invalidates_cockpit_session(): void
    {
        $cockpitUser = CockpitUser::create([
            'name' => 'Cockpit Admin',
            'email' => 'admin_logout@sollu.test',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $cockpitHost = config('domain.cockpit', 'cockpit.sollu.test');

        $response = $this->actingAs($cockpitUser, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $cockpitHost])
            ->delete("http://{$cockpitHost}/logout");

        $response->assertRedirect(route('cockpit.login'));
        $this->assertFalse(Auth::guard('cockpit')->check());
        $this->assertSame('sollu_cockpit_session', config('session.cookie'));
    }

    public function test_app_logout_only_invalidates_app_session(): void
    {
        $type = BusinessType::create([
            'name' => 'F&B',
            'code' => 'fnb_logout',
            'is_visible' => true,
        ]);

        $business = Business::create([
            'name' => 'Merchant Test Logout',
            'owner_name' => 'Test Owner',
            'email' => 'merchant_logout@sollu.test',
            'phone' => '081234567899',
            'business_type_id' => $type->id,
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
        ]);

        $merchantUser = User::create([
            'business_id' => $business->id,
            'name' => 'Merchant Owner Logout',
            'email' => 'owner_logout@sollu.test',
            'password' => bcrypt('password'),
        ]);

        $appHost = config('domain.app', 'app.sollu.test');

        $this->withoutExceptionHandling();
        $response = $this->actingAs($merchantUser, 'business')
            ->withServerVariables(['HTTP_HOST' => $appHost])
            ->delete("http://{$appHost}/logout");

        $response->assertRedirect(route('login'));
        $this->assertFalse(Auth::guard('business')->check());
        $this->assertSame('sollu_app_session', config('session.cookie'));
    }
}
