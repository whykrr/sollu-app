<?php

namespace App\Console\Commands;

use App\Models\Outlet;
use App\Services\Outlet\OutletProvisioningService;
use Illuminate\Console\Command;

class RepairDefaultOutletSettingsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sollu:repair-outlet-settings {--outlet= : Specific outlet ID to repair}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Provision missing default payment methods, receipt settings, financial settings, and operational hours for outlets';

    /**
     * Execute the console command.
     */
    public function handle(OutletProvisioningService $provisioningService): int
    {
        $outletId = $this->option('outlet');

        $query = Outlet::query();
        if ($outletId) {
            $query->where('id', $outletId);
        }

        $outlets = $query->get();
        if ($outlets->isEmpty()) {
            $this->warn('Tidak ada outlet yang ditemukan.');

            return self::SUCCESS;
        }

        $this->info("Memulai pengecekan dan perbaikan data awal untuk {$outlets->count()} outlet...");

        $bar = $this->output->createProgressBar($outlets->count());
        $bar->start();

        foreach ($outlets as $outlet) {
            $provisioningService->provisionAll($outlet);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Berhasil mem-provision pengaturan default dan metode pembayaran untuk seluruh outlet.');

        return self::SUCCESS;
    }
}
