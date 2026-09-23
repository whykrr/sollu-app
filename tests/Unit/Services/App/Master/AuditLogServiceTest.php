<?php

namespace Tests\Unit\Services\App\Master;

use App\Enums\AuditModuleEnum;
use App\Jobs\Audit\RecordActivityLogJob;
use App\Models\Business;
use App\Models\User;
use App\Services\App\Master\AuditLogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
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
        Queue::fake();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        [$business, $user] = $this->createTenant();

        $this->actingAs($user);

        $entityId = Str::uuid()->toString();
        $before = ['name' => 'Old Name'];
        $after = ['name' => 'New Name'];

        $this->service->log(
            $business->id,
            'product',
            $entityId,
            'update',
            $before,
            $after
        );

        Queue::assertPushed(RecordActivityLogJob::class, function (RecordActivityLogJob $job) use ($business, $user, $before, $after) {
            return $job->payload['business_id'] === $business->id
                && $job->payload['causer_id'] === $user->id
                && $job->payload['module'] === AuditModuleEnum::PRODUCTS->value
                && $job->payload['action'] === 'product.update'
                && $job->payload['properties']['old'] === $before
                && $job->payload['properties']['new'] === $after;
        });
    }

    public function test_it_logs_without_auth_user()
    {
        Queue::fake();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        [$business] = $this->createTenant();

        $entityId = Str::uuid()->toString();

        $this->service->log(
            $business->id,
            'product',
            $entityId,
            'delete'
        );

        Queue::assertPushed(RecordActivityLogJob::class, function (RecordActivityLogJob $job) use ($business) {
            return $job->payload['business_id'] === $business->id
                && $job->payload['module'] === AuditModuleEnum::PRODUCTS->value
                && $job->payload['action'] === 'product.delete';
        });
    }
}
