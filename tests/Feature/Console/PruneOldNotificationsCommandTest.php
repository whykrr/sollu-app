<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PruneOldNotificationsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_prunes_notifications_read_over_retention_days(): void
    {
        $oldReadId = (string) Str::uuid();
        $recentReadId = (string) Str::uuid();
        $unreadId = (string) Str::uuid();
        $userId = (string) Str::uuid();

        // 1. Read 400 days ago (should be pruned)
        DB::table('notifications')->insert([
            'id' => $oldReadId,
            'type' => 'App\Notifications\WelcomeUser',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $userId,
            'data' => json_encode(['category' => 'system', 'title' => 'Old Read']),
            'read_at' => Carbon::now()->subDays(400),
            'created_at' => Carbon::now()->subDays(400),
            'updated_at' => Carbon::now()->subDays(400),
        ]);

        // 2. Read 10 days ago (should NOT be pruned)
        DB::table('notifications')->insert([
            'id' => $recentReadId,
            'type' => 'App\Notifications\WelcomeUser',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $userId,
            'data' => json_encode(['category' => 'system', 'title' => 'Recent Read']),
            'read_at' => Carbon::now()->subDays(10),
            'created_at' => Carbon::now()->subDays(20),
            'updated_at' => Carbon::now()->subDays(10),
        ]);

        // 3. Unread notification (should NOT be pruned)
        DB::table('notifications')->insert([
            'id' => $unreadId,
            'type' => 'App\Notifications\WelcomeUser',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $userId,
            'data' => json_encode(['category' => 'system', 'title' => 'Unread']),
            'read_at' => null,
            'created_at' => Carbon::now()->subDays(100),
            'updated_at' => Carbon::now()->subDays(100),
        ]);

        $this->artisan('notifications:prune --days=365')
            ->expectsOutputToContain('Successfully pruned 1 notification records')
            ->assertSuccessful();

        $this->assertDatabaseMissing('notifications', ['id' => $oldReadId]);
        $this->assertDatabaseHas('notifications', ['id' => $recentReadId]);
        $this->assertDatabaseHas('notifications', ['id' => $unreadId]);
    }

    public function test_prune_supports_dry_run(): void
    {
        $oldReadId = (string) Str::uuid();
        $userId = (string) Str::uuid();

        DB::table('notifications')->insert([
            'id' => $oldReadId,
            'type' => 'App\Notifications\WelcomeUser',
            'notifiable_type' => 'App\Models\User',
            'notifiable_id' => $userId,
            'data' => json_encode(['category' => 'system', 'title' => 'Old Read']),
            'read_at' => Carbon::now()->subDays(400),
            'created_at' => Carbon::now()->subDays(400),
            'updated_at' => Carbon::now()->subDays(400),
        ]);

        $this->artisan('notifications:prune --days=365 --dry-run')
            ->expectsOutputToContain('[DRY RUN] Would delete 1 old notification records.')
            ->assertSuccessful();

        $this->assertDatabaseHas('notifications', ['id' => $oldReadId]);
    }
}
