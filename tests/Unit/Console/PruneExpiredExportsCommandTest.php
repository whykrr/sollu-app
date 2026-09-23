<?php

namespace Tests\Unit\Console;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PruneExpiredExportsCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        Storage::fake('local');
    }

    public function test_it_prunes_expired_export_files(): void
    {
        Storage::disk('public')->put('exports/recent_export.xlsx', 'recent content');
        Storage::disk('public')->put('exports/expired_export.xlsx', 'old content');
        Storage::disk('local')->put('imports/orphaned_import.xlsx', 'abandoned import');

        // Set modification time: recent = now, expired = 48 hours ago
        $now = now()->timestamp;
        $twoDaysAgo = now()->subHours(48)->timestamp;

        touch(Storage::disk('public')->path('exports/recent_export.xlsx'), $now);
        touch(Storage::disk('public')->path('exports/expired_export.xlsx'), $twoDaysAgo);
        touch(Storage::disk('local')->path('imports/orphaned_import.xlsx'), $twoDaysAgo);

        $this->artisan('exports:prune', ['--hours' => 24])
            ->assertSuccessful()
            ->expectsOutputToContain('Berhasil membersihkan 2 berkas kedaluwarsa');

        // Recent export should remain intact
        Storage::disk('public')->assertExists('exports/recent_export.xlsx');

        // Expired export and orphaned import should be deleted
        Storage::disk('public')->assertMissing('exports/expired_export.xlsx');
        Storage::disk('local')->assertMissing('imports/orphaned_import.xlsx');
    }

    public function test_it_supports_dry_run_mode(): void
    {
        Storage::disk('public')->put('exports/expired_file.pdf', 'pdf content');
        $twoDaysAgo = now()->subHours(48)->timestamp;
        touch(Storage::disk('public')->path('exports/expired_file.pdf'), $twoDaysAgo);

        $this->artisan('exports:prune', ['--hours' => 24, '--dry-run' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('[DRY RUN] Akan menghapus [public]: exports/expired_file.pdf')
            ->expectsOutputToContain('Total 1 berkas kedaluwarsa terdeteksi');

        // In dry run, file should not actually be deleted
        Storage::disk('public')->assertExists('exports/expired_file.pdf');
    }

    public function test_it_reports_when_no_expired_files_found(): void
    {
        Storage::disk('public')->put('exports/fresh_export.xlsx', 'fresh content');
        $now = now()->timestamp;
        touch(Storage::disk('public')->path('exports/fresh_export.xlsx'), $now);

        $this->artisan('exports:prune', ['--hours' => 24])
            ->assertSuccessful()
            ->expectsOutputToContain('Tidak ada berkas ekspor/impor kedaluwarsa yang perlu dibersihkan.');

        Storage::disk('public')->assertExists('exports/fresh_export.xlsx');
    }
}
