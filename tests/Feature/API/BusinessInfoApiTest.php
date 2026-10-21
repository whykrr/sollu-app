<?php

namespace Tests\Feature\API;

use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
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
        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true, 'features' => []]
        );

        $business = Business::create([
            'name' => 'API Business',
            'owner_name' => 'API Owner',
            'email' => 'api_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'business_type_id' => $type->id,
            'settings' => [],
        ]);

        $microPlan = SubscriptionPlan::where('code', 'micro')->first();
        if ($microPlan) {
            Subscription::create([
                'business_id' => $business->id,
                'plan_id' => $microPlan->id,
                'status' => 'active',
                'is_trial' => true,
                'billing_cycle' => 'monthly',
                'started_at' => now(),
                'expired_at' => now()->addDays(14),
            ]);
        }

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
        $this->assertStringContainsString('(Trial)', $data['plan_name']);
        $this->assertNotNull($data['expired_at']);
        $this->assertIsInt($data['outlet_count']);
    }
}
