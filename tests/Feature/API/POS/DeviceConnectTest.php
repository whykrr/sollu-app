<?php

namespace Tests\Feature\API\POS;

use App\Models\Outlet;
use App\Models\OutletDevice;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DeviceConnectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_connect_device_fails_with_invalid_or_expired_otp(): void
    {
        $response = $this->postJson('/api/pos/device/connect', [
            'otp' => '99999999',
            'device_uuid' => '3cdb7ce9-3d06-48cc-8dc4-9b764a2e0818',
            'hardware_fingerprint' => 'ef15135f0738b1a757f45e521ec065db31f5175223d5212671d33f8f4698ac0b',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Kode OTP tidak valid atau sudah kadaluarsa.',
            ]);
    }

    public function test_connect_device_success_with_valid_otp(): void
    {
        $outlet = Outlet::first();
        $device = OutletDevice::create([
            'outlet_id' => $outlet->id,
            'device_name' => 'Kasir Utama',
            'device_type' => 'pos',
            'serial_number' => 'POS-001',
            'is_active' => true,
        ]);

        $otp = '12345678';
        Cache::put("device_otp_{$otp}", $device->id, now()->addMinutes(5));

        $deviceUuid = '3cdb7ce9-3d06-48cc-8dc4-9b764a2e0818';
        $hardwareFingerprint = 'ef15135f0738b1a757f45e521ec065db31f5175223d5212671d33f8f4698ac0b';

        $response = $this->postJson('/api/pos/device/connect', [
            'otp' => $otp,
            'device_uuid' => $deviceUuid,
            'hardware_fingerprint' => $hardwareFingerprint,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'device' => ['id', 'name', 'type'],
                    'outlet' => ['id', 'name', 'address', 'phone_number'],
                    'business' => ['id', 'name'],
                ],
                'message',
            ])
            ->assertJson([
                'message' => 'Perangkat berhasil dihubungkan.',
                'data' => [
                    'device' => [
                        'id' => $device->id,
                        'name' => 'Kasir Utama',
                        'type' => 'pos',
                    ],
                ],
            ]);

        // Verify database updated
        $this->assertDatabaseHas('outlet_devices', [
            'id' => $device->id,
            'client_device_uuid' => $deviceUuid,
            'hardware_fingerprint' => $hardwareFingerprint,
            'is_active' => true,
        ]);

        // Verify OTP is forgotten
        $this->assertNull(Cache::get("device_otp_{$otp}"));

        // Verify device cache exists
        $cached = Cache::get("pos_device_{$device->id}");
        $this->assertNotNull($cached);
        $this->assertEquals($deviceUuid, $cached['client_device_uuid']);
        $this->assertEquals($hardwareFingerprint, $cached['hardware_fingerprint']);

        // Verify authenticated endpoint with token and headers
        $token = $response->json('data.token');
        $statusResponse = $this->withHeaders([
            'Authorization' => "Bearer {$token}",
            'X-DEVICE-UUID' => $deviceUuid,
            'X-HARDWARE-SIGNATURE' => $hardwareFingerprint,
        ])->getJson('/api/pos/device/status');

        $statusResponse->assertStatus(200)
            ->assertJson([
                'message' => 'Device terkoneksi dan valid.',
            ]);
    }
}
