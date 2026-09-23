<?php

namespace Tests\Feature\Settings;

use App\Contracts\Audit\ActivityLoggerInterface;
use App\DTO\Audit\ActivityLogDTO;
use App\Enums\AuditModuleEnum;
use App\Enums\PermissionEnum;
use App\Enums\PlanEnum;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    protected string $appDomain;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.testing.page_paths' => [resource_path('js/Pages/App')]]);
        $this->seed(DatabaseSeeder::class);
        $this->appDomain = config('domain.app', 'app.sollu.test');
    }

    protected function createMerchantUser(): User
    {
        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = Business::create([
            'name' => 'Merchant Test Business',
            'owner_name' => 'Merchant Owner',
            'email' => 'merchant_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $user = User::create([
            'business_id' => $business->id,
            'name' => 'Merchant User',
            'email' => 'user_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        setPermissionsTeamId($business->id);

        return $user;
    }

    protected function subscribeBusinessToPlan(User $user, PlanEnum $planEnum = PlanEnum::PRO): void
    {
        setPermissionsTeamId($user->business_id);
        $plan = SubscriptionPlan::where('code', $planEnum->value)->first();
        Subscription::create([
            'business_id' => $user->business_id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'started_at' => Carbon::now()->subDays(1),
            'expired_at' => Carbon::now()->addDays(29),
        ]);
    }

    public function test_unauthenticated_user_cannot_access_activity_logs(): void
    {
        $response = $this->get("http://{$this->appDomain}/settings/activity-logs");

        $response->assertStatus(302);
    }

    public function test_authorized_user_can_view_activity_logs_page_with_expected_props(): void
    {
        $user = $this->createMerchantUser();
        $this->subscribeBusinessToPlan($user);
        $user->givePermissionTo(PermissionEnum::SETTING_AUDIT->value);

        /** @var ActivityLoggerInterface $logger */
        $logger = app(ActivityLoggerInterface::class);

        // Buat log secara langsung
        $logger->logNow(new ActivityLogDTO(
            module: AuditModuleEnum::SETTINGS->value,
            action: 'tax.updated',
            description: 'Mengubah tarif pajak menjadi 11%',
            businessId: $user->business_id,
            causerId: $user->id,
            causerType: User::class,
            properties: [
                'old' => ['tax_rate' => 10],
                'new' => ['tax_rate' => 11],
            ]
        ));

        $response = $this->actingAs($user)
            ->get("http://{$this->appDomain}/settings/activity-logs");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Settings/ActivityLog/Index')
            ->has('logs.data', 1)
            ->has('filters')
            ->has('stats')
            ->has('modules')
        );
    }

    public function test_tenant_isolation_ensures_user_only_sees_their_own_business_logs(): void
    {
        $user1 = $this->createMerchantUser();
        $this->subscribeBusinessToPlan($user1);
        $user1->givePermissionTo(PermissionEnum::SETTING_AUDIT->value);

        $user2 = $this->createMerchantUser();
        $this->subscribeBusinessToPlan($user2);
        $user2->givePermissionTo(PermissionEnum::SETTING_AUDIT->value);

        /** @var ActivityLoggerInterface $logger */
        $logger = app(ActivityLoggerInterface::class);

        $logger->logNow(new ActivityLogDTO(
            module: AuditModuleEnum::POS->value,
            action: 'shift.opened',
            description: 'Buka shift kasir Tenant 1',
            businessId: $user1->business_id,
            causerId: $user1->id,
        ));

        $logger->logNow(new ActivityLogDTO(
            module: AuditModuleEnum::POS->value,
            action: 'shift.opened',
            description: 'Buka shift kasir Tenant 2',
            businessId: $user2->business_id,
            causerId: $user2->id,
        ));

        $response = $this->actingAs($user1)
            ->get("http://{$this->appDomain}/settings/activity-logs");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Settings/ActivityLog/Index')
            ->has('logs.data', 1)
            ->where('logs.data.0.description', 'Buka shift kasir Tenant 1')
        );
    }

    public function test_show_endpoint_returns_json_diff_detail(): void
    {
        $user = $this->createMerchantUser();
        $this->subscribeBusinessToPlan($user);
        $user->givePermissionTo(PermissionEnum::SETTING_AUDIT->value);

        /** @var ActivityLoggerInterface $logger */
        $logger = app(ActivityLoggerInterface::class);

        $log = $logger->logNow(new ActivityLogDTO(
            module: AuditModuleEnum::INVENTORY->value,
            action: 'adjustment.approved',
            description: 'Menyetujui penyesuaian stok bahan',
            businessId: $user->business_id,
            causerId: $user->id,
            causerType: User::class,
            properties: [
                'old' => ['status' => 'draft'],
                'new' => ['status' => 'approved'],
            ]
        ));

        $response = $this->actingAs($user)
            ->get("http://{$this->appDomain}/settings/activity-logs/{$log->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $log->id)
            ->assertJsonPath('data.properties.old.status', 'draft')
            ->assertJsonPath('data.properties.new.status', 'approved');
    }

    public function test_async_logging_dispatches_queue_job(): void
    {
        Queue::fake();

        /** @var ActivityLoggerInterface $logger */
        $logger = app(ActivityLoggerInterface::class);

        $logger->log(
            module: AuditModuleEnum::AUTH->value,
            action: 'auth.login',
            description: 'Pengguna berhasil masuk',
            businessId: 'dummy-business-id',
        );

        Queue::assertPushed(\App\Jobs\Audit\RecordActivityLogJob::class);
    }

    public function test_manage_audit_partitions_command_prunes_records_older_than_retention_days(): void
    {
        $user = $this->createMerchantUser();

        /** @var ActivityLoggerInterface $logger */
        $logger = app(ActivityLoggerInterface::class);

        // Buat log lama (400 hari lalu)
        $oldLog = $logger->logNow(new ActivityLogDTO(
            module: AuditModuleEnum::SETTINGS->value,
            action: 'old.action',
            description: 'Aktivitas 400 hari lalu',
            businessId: $user->business_id,
        ));
        $oldLog->created_at = Carbon::now()->subDays(400);
        $oldLog->save();

        // Buat log baru (30 hari lalu)
        $newLog = $logger->logNow(new ActivityLogDTO(
            module: AuditModuleEnum::SETTINGS->value,
            action: 'new.action',
            description: 'Aktivitas 30 hari lalu',
            businessId: $user->business_id,
        ));
        $newLog->created_at = Carbon::now()->subDays(30);
        $newLog->save();

        $this->artisan('audit:manage-partitions --prune-days=365')
            ->assertSuccessful();

        $this->assertDatabaseMissing('activity_logs', ['id' => $oldLog->id]);
        $this->assertDatabaseHas('activity_logs', ['id' => $newLog->id]);
    }
}
