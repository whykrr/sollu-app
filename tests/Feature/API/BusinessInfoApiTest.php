<?php

namespace Tests\Feature\API;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessInfoApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_unauthenticated_user_cannot_access_business_info(): void
    {
        $appDomain = config('domain.app', 'app.sollu.test');
        $response = $this->get("http://{$appDomain}/api/internal/business-info");

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_retrieve_business_info(): void
    {
        $appDomain = config('domain.app', 'app.sollu.test');
        $type = \App\Models\BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true, 'features' => []]
        );

        $business = \App\Models\Business::create([
            'name' => 'API Business',
            'owner_name' => 'API Owner',
            'email' => 'api_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
            'settings' => [],
        ]);

        $user = User::create([
            'business_id' => $business->id,
            'name' => 'API User',
            'email' => 'api_user_'.uniqid().'@test.test',
            'password' => bcrypt('password123'),
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user, 'business')
            ->get("http://{$appDomain}/api/internal/business-info");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'business_type',
                'plan_name',
                'expired_at',
                'outlet_count',
            ]);

        $data = $response->json();
        $this->assertIsString($data['business_type']);
        $this->assertIsString($data['plan_name']);
        $this->assertIsInt($data['outlet_count']);
    }
}
