<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class EnsureStorageDirectories extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'storage:ensure-directories';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Membuat direktori storage yang dibutuhkan untuk fitur ekspor dan impor';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $directories = [
            'exports',
            'imports',
        ];

        $disk = Storage::disk('local');

        foreach ($directories as $directory) {
            $disk->makeDirectory($directory);
            $this->info("✓ Direktori '{$directory}' siap.");
        }

        $this->newLine();
        $this->info('Semua direktori storage berhasil dipastikan tersedia.');

        return self::SUCCESS;
    }
}
