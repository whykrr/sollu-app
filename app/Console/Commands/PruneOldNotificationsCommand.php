<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PruneOldNotificationsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'notifications:prune
                            {--days=365 : Retention days for read notifications}
                            {--dry-run : Simulate pruning without actually deleting}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Prune old read notifications and expired file export notifications from database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $isDryRun = (bool) $this->option('dry-run');

        $readCutoff = Carbon::now()->subDays($days);
        $fileCutoff = Carbon::now()->subDays(30)->toISOString();
        $unreadAncientCutoff = Carbon::now()->subYears(2);

        $this->info('Pruning criteria:');
        $this->line("- Read notifications before: {$readCutoff->toDateTimeString()} ({$days} days)");
        $this->line("- Expired file notifications before: {$fileCutoff}");
        $this->line("- Ancient unread notifications before: {$unreadAncientCutoff->toDateTimeString()}");

        $baseQuery = DB::table('notifications')
            ->where(function ($query) use ($readCutoff, $fileCutoff, $unreadAncientCutoff) {
                $query->whereNotNull('read_at')
                    ->where('read_at', '<=', $readCutoff)
                    ->orWhere(function ($sub) use ($fileCutoff) {
                        $sub->whereNotNull('data->expires_at')
                            ->where('data->expires_at', '<=', $fileCutoff);
                    })
                    ->orWhere('created_at', '<=', $unreadAncientCutoff);
            });

        $totalMatching = (clone $baseQuery)->count();

        if ($isDryRun) {
            $this->warn("[DRY RUN] Would delete {$totalMatching} old notification records.");

            return self::SUCCESS;
        }

        if ($totalMatching === 0) {
            $this->info('No notifications matched pruning criteria.');

            return self::SUCCESS;
        }

        $this->info("Found {$totalMatching} records to prune. Starting chunked deletion...");

        $deletedCount = 0;
        $chunkSize = 1000;

        do {
            $ids = (clone $baseQuery)
                ->limit($chunkSize)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $affected = DB::table('notifications')
                ->whereIn('id', $ids)
                ->delete();

            $deletedCount += $affected;
            $this->line("Deleted batch: {$affected} records (Total: {$deletedCount}/{$totalMatching})");
        } while ($ids->count() === $chunkSize);

        $this->info("Successfully pruned {$deletedCount} notification records from database.");
        Log::info("Notification prune completed: {$deletedCount} records deleted.");

        return self::SUCCESS;
    }
}
