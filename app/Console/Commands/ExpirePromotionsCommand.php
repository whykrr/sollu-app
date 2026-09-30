<?php

namespace App\Console\Commands;

use App\Enums\PromotionStatus;
use App\Models\Promotion\Promotion;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class ExpirePromotionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'promotions:expire
                            {--dry-run : Simulate expiring promotions without updating database}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Transition active or inactive promotions past their end_date to expired status';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $isDryRun = (bool) $this->option('dry-run');
        $today = Carbon::today()->toDateString();

        $this->info("Checking for promotions ended before today ({$today})...");

        $baseQuery = Promotion::query()
            ->whereIn('status', [
                PromotionStatus::Active->value,
                PromotionStatus::Inactive->value,
            ])
            ->whereDate('end_date', '<', $today);

        $totalMatching = (clone $baseQuery)->count();

        if ($isDryRun) {
            $this->warn("[DRY RUN] Found {$totalMatching} promotions that would be marked as expired.");

            return self::SUCCESS;
        }

        if ($totalMatching === 0) {
            $this->info('No promotions need to be expired.');

            return self::SUCCESS;
        }

        $this->info("Found {$totalMatching} promotions to expire. Updating records...");

        $updatedCount = 0;
        $chunkSize = 200;

        $baseQuery->chunkById($chunkSize, function ($promotions) use (&$updatedCount) {
            foreach ($promotions as $promotion) {
                $promotion->update([
                    'status' => PromotionStatus::Expired->value,
                ]);
                $updatedCount++;
            }
        });

        $this->info("Successfully expired {$updatedCount} promotions.");
        Log::info("Promotion expire command completed: {$updatedCount} promotions transitioned to expired status.");

        return self::SUCCESS;
    }
}
