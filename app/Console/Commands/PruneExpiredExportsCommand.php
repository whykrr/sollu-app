<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PruneExpiredExportsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'exports:prune
                            {--hours=24 : Masa retensi berkas ekspor sementara dalam jam}
                            {--dry-run : Jalankan simulasi tanpa menghapus berkas}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Membersihkan berkas ekspor dan impor sementara yang telah kedaluwarsa dari storage';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $hours = (int) $this->option('hours');
        $isDryRun = (bool) $this->option('dry-run');
        $cutoffTimestamp = now()->subHours($hours)->timestamp;

        $this->info("Menjalankan pembersihan berkas kedaluwarsa (Masa aktif > {$hours} jam)...");

        $targets = [
            ['disk' => 'public', 'directory' => 'exports'],
            ['disk' => 'local', 'directory' => 'exports'],
            ['disk' => 'local', 'directory' => 'imports'],
        ];

        $totalFilesFound = 0;
        $totalFilesDeleted = 0;
        $totalBytesFreed = 0;

        foreach ($targets as $target) {
            $diskName = $target['disk'];
            $directory = $target['directory'];

            if (! Storage::disk($diskName)->exists($directory)) {
                continue;
            }

            $files = Storage::disk($diskName)->files($directory);

            foreach ($files as $file) {
                // Skip placeholder or hidden files (.gitignore, etc.)
                if (str_starts_with(basename($file), '.')) {
                    continue;
                }

                $lastModified = Storage::disk($diskName)->lastModified($file);

                if ($lastModified < $cutoffTimestamp) {
                    $totalFilesFound++;
                    $fileSize = Storage::disk($diskName)->size($file);
                    $totalBytesFreed += $fileSize;

                    $humanSize = $this->formatBytes($fileSize);

                    if ($isDryRun) {
                        $this->line("[DRY RUN] Akan menghapus [{$diskName}]: {$file} ({$humanSize})");
                    } else {
                        Storage::disk($diskName)->delete($file);
                        $totalFilesDeleted++;
                        $this->line("✓ Berhasil menghapus [{$diskName}]: {$file} ({$humanSize})");
                    }
                }
            }
        }

        $formattedTotalReclaimed = $this->formatBytes($totalBytesFreed);

        if ($isDryRun) {
            $this->warn("[DRY RUN] Total {$totalFilesFound} berkas kedaluwarsa terdeteksi ({$formattedTotalReclaimed} dapat dihemat).");

            return self::SUCCESS;
        }

        if ($totalFilesDeleted === 0) {
            $this->info('Tidak ada berkas ekspor/impor kedaluwarsa yang perlu dibersihkan.');

            return self::SUCCESS;
        }

        $this->info("Selesai! Berhasil membersihkan {$totalFilesDeleted} berkas kedaluwarsa (Menghemat {$formattedTotalReclaimed} storage).");
        Log::info("PruneExpiredExports completed: {$totalFilesDeleted} files deleted ({$formattedTotalReclaimed} reclaimed).");

        return self::SUCCESS;
    }

    /**
     * Format bytes to human readable format.
     */
    protected function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, $precision).' '.$units[$pow];
    }
}
