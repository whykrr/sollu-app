<?php

namespace Tests\Unit\Services\Audit;

use App\Contracts\Audit\ActivityLoggerInterface;
use App\DTO\Audit\ActivityLogDTO;
use App\Enums\AuditModuleEnum;
use App\Jobs\Audit\RecordActivityLogJob;
use App\Models\Audit\ActivityLog;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\User;
use App\Services\App\Audit\ActivityLogService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ActivityLogServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    protected function createMerchantUser(): User
    {
        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = Business::create([
            'name' => 'Test Business',
            'owner_name' => 'Owner',
            'email' => 'business_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        return User::create([
            'business_id' => $business->id,
            'name' => 'Test User',
            'email' => 'user_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);
    }

    public function test_activity_logger_interface_is_bound_to_activity_log_service(): void
    {
        $service = app(ActivityLoggerInterface::class);

        $this->assertInstanceOf(ActivityLogService::class, $service);
    }

    public function test_log_dispatches_record_activity_log_job_with_auto_resolved_context(): void
    {
        Queue::fake();

        $user = $this->createMerchantUser();
        Auth::login($user);

        /** @var ActivityLoggerInterface $service */
        $service = app(ActivityLoggerInterface::class);

        $service->log(
            module: AuditModuleEnum::PRODUCTS->value,
            action: 'product.created',
            description: 'Membuat produk baru',
            properties: ['name' => 'Kopi Tubruk']
        );

        Queue::assertPushed(RecordActivityLogJob::class, function (RecordActivityLogJob $job) use ($user) {
            return $job->payload['module'] === AuditModuleEnum::PRODUCTS->value
                && $job->payload['action'] === 'product.created'
                && $job->payload['description'] === 'Membuat produk baru'
                && $job->payload['business_id'] === $user->business_id
                && $job->payload['causer_id'] === $user->id
                && $job->payload['properties']['name'] === 'Kopi Tubruk';
        });
    }

    public function test_log_now_persists_record_immediately_to_database(): void
    {
        $user = $this->createMerchantUser();

        /** @var ActivityLoggerInterface $service */
        $service = app(ActivityLoggerInterface::class);

        $dto = new ActivityLogDTO(
            module: AuditModuleEnum::SETTINGS->value,
            action: 'settings.updated',
            description: 'Update setting bisnis',
            businessId: $user->business_id,
            causerId: $user->id,
            causerType: User::class,
            properties: ['theme' => 'dark']
        );

        $record = $service->logNow($dto);

        $this->assertInstanceOf(ActivityLog::class, $record);
        $this->assertDatabaseHas('activity_logs', [
            'id' => $record->id,
            'module' => AuditModuleEnum::SETTINGS->value,
            'action' => 'settings.updated',
            'business_id' => $user->business_id,
        ]);
    }
}
