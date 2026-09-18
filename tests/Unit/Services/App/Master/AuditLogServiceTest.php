<?php

namespace Tests\Unit\Services\App\Master;

use App\Models\Business;
use App\Models\User;
use App\Services\App\Master\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class AuditLogServiceTest extends TestCase
{
    use RefreshDatabase;

    protected AuditLogService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new AuditLogService;
    }

    protected function createTenant(): array
    {
        $type = \App\Models\BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = Business::create([
            'name' => 'Test Business',
            'owner_name' => 'Owner',
            'email' => 'owner_'.uniqid().'@test.com',
            'phone' => '08123456789',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $user = User::create([
            'business_id' => $business->id,
            'name' => 'Manager',
            'email' => 'manager_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
        ]);

        return [$business, $user];
    }

    public function test_it_logs_audit_trail_successfully()
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        [$business, $user] = $this->createTenant();

        $this->actingAs($user);

        $entityId = Str::uuid()->toString();
        $before = ['name' => 'Old Name'];
        $after = ['name' => 'New Name'];

        $this->service->log(
            $business->id,
            'App\\Models\\Product',
            $entityId,
            'update',
            $before,
            $after
        );

        $this->assertDatabaseHas('audit_logs', [
            'business_id' => $business->id,
            'actor_id' => $user->id,
            'entity_type' => 'App\\Models\\Product',
            'entity_id' => $entityId,
            'action' => 'update',
            'before_value' => json_encode($before),
            'after_value' => json_encode($after),
        ]);
    }

    public function test_it_logs_without_auth_user()
    {
        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        [$business] = $this->createTenant();

        $entityId = Str::uuid()->toString();

        $this->service->log(
            $business->id,
            'App\\Models\\Product',
            $entityId,
            'delete'
        );

        $this->assertDatabaseHas('audit_logs', [
            'business_id' => $business->id,
            'entity_type' => 'App\\Models\\Product',
            'entity_id' => $entityId,
            'action' => 'delete',
            'before_value' => null,
            'after_value' => null,
        ]);
    }
}
