<?php

namespace Tests\Feature;

use App\Constants\FlashDataVariable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CsrfTokenFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_csrf_token_endpoint_returns_json_and_token_on_app_domain(): void
    {
        $appHost = config('domain.app', 'app.sollu.test');

        $response = $this->withServerVariables(['HTTP_HOST' => $appHost])
            ->get("http://{$appHost}/csrf-token");

        $response->assertOk();
        $response->assertJsonStructure([
            'csrf_token',
            'authenticated',
        ]);
        $response->assertJson([
            'authenticated' => false,
        ]);
    }

    public function test_csrf_token_endpoint_returns_authenticated_true_when_logged_in(): void
    {
        $appHost = config('domain.app', 'app.sollu.test');
        $type = \App\Models\BusinessType::create([
            'name' => 'Coffee Shop',
            'code' => 'coffee_shop',
        ]);
        $business = \App\Models\Business::create([
            'name' => 'Merchant Test',
            'owner_name' => 'Test Owner',
            'email' => 'merchant_csrf@test.com',
            'phone' => '081234567891',
            'business_type_id' => $type->id,
            'trial_end_at' => now()->addDays(14),
        ]);
        $user = User::factory()->create(['business_id' => $business->id]);

        $response = $this->withServerVariables(['HTTP_HOST' => $appHost])
            ->actingAs($user, 'business')
            ->get("http://{$appHost}/csrf-token");

        $response->assertOk();
        $response->assertJson([
            'authenticated' => true,
        ]);
    }

    public function test_csrf_token_endpoint_works_on_cockpit_domain(): void
    {
        $cockpitHost = config('domain.cockpit', 'cockpit.sollu.test');

        $response = $this->withServerVariables(['HTTP_HOST' => $cockpitHost])
            ->get("http://{$cockpitHost}/csrf-token");

        $response->assertOk();
        $response->assertJsonStructure([
            'csrf_token',
            'authenticated',
        ]);
    }

    public function test_token_mismatch_exception_returns_json_419_with_cookie_for_inertia_request(): void
    {
        $appHost = config('domain.app', 'app.sollu.test');

        Route::middleware('web')->post('/test-csrf-inertia-trigger', function () {
            throw new TokenMismatchException('CSRF token mismatch.');
        });

        $response = $this->withServerVariables(['HTTP_HOST' => $appHost])
            ->withHeaders([
                'X-Inertia' => 'true',
            ])
            ->post("http://{$appHost}/test-csrf-inertia-trigger");

        $response->assertStatus(419);
        $response->assertJsonStructure([
            'message',
            'csrf_token',
            'authenticated',
        ]);
        $response->assertCookie('XSRF-TOKEN');
    }

    public function test_token_mismatch_exception_redirects_back_with_input_for_standard_request(): void
    {
        $appHost = config('domain.app', 'app.sollu.test');

        Route::middleware('web')->post('/test-csrf-web-trigger', function () {
            throw new TokenMismatchException('CSRF token mismatch.');
        });

        $response = $this->withServerVariables(['HTTP_HOST' => $appHost])
            ->from("http://{$appHost}/form-page")
            ->post("http://{$appHost}/test-csrf-web-trigger", [
                'field_name' => 'sample_value',
            ]);

        $response->assertRedirect("http://{$appHost}/form-page");
        $response->assertSessionHas(FlashDataVariable::FAILED->value);
    }

    public function test_guest_login_attempt_with_token_mismatch_returns_json_419_for_inertia_request(): void
    {
        $appHost = config('domain.app', 'app.sollu.test');

        Route::middleware('web')->post('/test-login-csrf-trigger', function () {
            throw new TokenMismatchException('CSRF token mismatch.');
        });

        $response = $this->withServerVariables(['HTTP_HOST' => $appHost])
            ->withHeaders([
                'X-Inertia' => 'true',
            ])
            ->from("http://{$appHost}/login")
            ->post("http://{$appHost}/test-login-csrf-trigger", [
                'email' => 'admin@sollu.test',
                'password' => 'secret',
            ]);

        $response->assertStatus(419);
        $response->assertJson([
            'authenticated' => false,
        ]);
        $response->assertJsonStructure([
            'message',
            'csrf_token',
            'authenticated',
        ]);
        $response->assertCookie('XSRF-TOKEN');
    }
}
