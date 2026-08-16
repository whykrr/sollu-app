<?php

namespace Tests\Feature\API\POS;

use App\Models\Outlet;
use App\Models\OutletDevice;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class LogControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        $outlet = Outlet::first();
        $this->device = OutletDevice::create([
            'outlet_id' => $outlet->id,
            'device_name' => 'Test Device',
            'device_type' => 'pos',
            'serial_number' => 'POS-001',
            'client_device_uuid' => 'test-uuid',
            'hardware_fingerprint' => 'test-fingerprint',
            'is_active' => true,
        ]);
    }

    public function test_it_does_not_forward_logs_in_non_production_environment()
    {
        Config::set('app.env', 'local');
        Config::set('services.discord.allow_non_prod', false);
        Http::fake();

        $token = $this->device->createToken('test')->plainTextToken;

        $response = $this->postJson('/api/pos/logs/error', [
            'error' => 'Test error message',
        ], [
            'Authorization' => 'Bearer '.$token,
            'X-DEVICE-UUID' => 'test-uuid',
            'X-HARDWARE-SIGNATURE' => 'test-fingerprint',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Logs are only forwarded to Discord in production.']);

        Http::assertNothingSent();
    }

    public function test_it_forwards_logs_in_non_production_environment_if_allow_non_prod_is_true()
    {
        Config::set('app.env', 'local');
        Config::set('services.discord.allow_non_prod', true);
        Config::set('services.discord.webhook_url', 'https://discord.com/api/webhooks/test/test');
        Http::fake([
            '*' => Http::response('ok', 200),
        ]);

        $token = $this->device->createToken('test')->plainTextToken;

        $response = $this->postJson('/api/pos/logs/error', [
            'error' => 'Test error message',
        ], [
            'Authorization' => 'Bearer '.$token,
            'X-DEVICE-UUID' => 'test-uuid',
            'X-HARDWARE-SIGNATURE' => 'test-fingerprint',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Error logged successfully.']);

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return $request->url() == 'https://discord.com/api/webhooks/test/test';
        });
    }

    public function test_it_forwards_logs_and_strips_slack_suffix_from_webhook_url()
    {
        Config::set('app.env', 'production');
        Config::set('services.discord.webhook_url', 'https://discord.com/api/webhooks/test/test/slack');
        Http::fake([
            '*' => Http::response('ok', 200),
        ]);

        $token = $this->device->createToken('test')->plainTextToken;

        $response = $this->postJson('/api/pos/logs/error', [
            'error' => 'Test error message',
            'stack_trace' => 'Test stack trace',
            'device_info' => ['os' => 'android'],
            'app_version' => '1.0.0',
        ], [
            'Authorization' => 'Bearer '.$token,
            'X-DEVICE-UUID' => 'test-uuid',
            'X-HARDWARE-SIGNATURE' => 'test-fingerprint',
        ]);

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Error logged successfully.']);

        Http::assertSent(function (\Illuminate\Http\Client\Request $request) {
            return $request->url() == 'https://discord.com/api/webhooks/test/test' && isset($request['embeds'][0]['title']) && $request['embeds'][0]['title'] === '🚨 POS Client Error';
        });
    }

    public function test_it_validates_required_fields()
    {
        $token = $this->device->createToken('test')->plainTextToken;

        $response = $this->postJson('/api/pos/logs/error', [], [
            'Authorization' => 'Bearer '.$token,
            'X-DEVICE-UUID' => 'test-uuid',
            'X-HARDWARE-SIGNATURE' => 'test-fingerprint',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['error']);
    }
}
