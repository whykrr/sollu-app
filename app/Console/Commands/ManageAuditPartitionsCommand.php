<?php

namespace App\Console\Commands;

use App\Models\Audit\ActivityLog;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ManageAuditPartitionsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'audit:manage-partitions {--prune-days=365 : Jumlah hari retensi log sebelum dihapus}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Otomatisasi pembuatan partisi audit log bulan depan dan pembersihan partisi lebih dari 1 tahun';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $pruneDays = (int) $this->option('prune-days');
        $driver = DB::getDriverName();

        $this->info("Menjalankan manajemen partisi audit log (Retensi: {$pruneDays} hari, Driver: {$driver})...");

        if ($driver === 'pgsql') {
            $this->managePostgreSqlPartitions($pruneDays);
        } else {
            $this->manageSqliteFallback($pruneDays);
        }

        $this->info('Manajemen partisi audit log selesai.');

        return self::SUCCESS;
    }

    /**
     * Manajemen partisi native PostgreSQL.
     */
    protected function managePostgreSqlPartitions(int $pruneDays): void
    {
        // 1. Pastikan partisi bulan ini & 2 bulan ke depan ada
        $now = Carbon::now()->startOfMonth();
        for ($i = 0; $i <= 2; $i++) {
            $targetMonth = $now->copy()->addMonths($i);
            $partitionName = 'activity_logs_'.$targetMonth->format('Y_m');
            $from = $targetMonth->copy()->startOfMonth()->toDateTimeString();
            $to = $targetMonth->copy()->addMonth()->startOfMonth()->toDateTimeString();

            DB::statement("
                CREATE TABLE IF NOT EXISTS audit.{$partitionName}
                PARTITION OF audit.activity_logs
                FOR VALUES FROM ('{$from}') TO ('{$to}');
            ");

            $this->line("  ✓ Partisi audit.{$partitionName} siap.");
        }

        // 2. Cari dan drop partisi yang lebih tua dari pruneDays (365 hari)
        $cutoffDate = Carbon::now()->subDays($pruneDays)->startOfMonth();

        $tables = DB::select("
            SELECT tablename 
            FROM pg_tables 
            WHERE schemaname = 'audit' 
              AND tablename LIKE 'activity_logs_%'
        ");

        foreach ($tables as $table) {
            $tableName = $table->tablename;
            // Format nama: activity_logs_YYYY_MM
            if (preg_match('/^activity_logs_(\d{4})_(\d{2})$/', $tableName, $matches)) {
                $tableDate = Carbon::createFromDate((int) $matches[1], (int) $matches[2], 1)->startOfMonth();

                if ($tableDate->lessThan($cutoffDate)) {
                    DB::statement("DROP TABLE IF EXISTS audit.{$tableName};");
                    $this->warn("  🗑️ Menghapus partisi usang: audit.{$tableName} (Bulan: {$tableDate->format('M Y')})");
                }
            }
        }
    }

    /**
     * Fallback pembersihan untuk SQLite (Lingkungan testing / lokal).
     */
    protected function manageSqliteFallback(int $pruneDays): void
    {
        $cutoff = Carbon::now()->subDays($pruneDays);
        $deleted = ActivityLog::where('created_at', '<', $cutoff)->delete();

        $this->line("  ✓ {$deleted} record log usang dibersihkan dari SQLite.");
    }
}
