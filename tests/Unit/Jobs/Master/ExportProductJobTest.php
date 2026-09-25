<?php

namespace Tests\Unit\Jobs\Master;

use App\Jobs\Master\ExportProductJob;
use App\Models\User;
use Tests\TestCase;

class ExportProductJobTest extends TestCase
{
    public function test_it_returns_correct_module_name(): void
    {
        $user = new User;
        $user->id = '00000000-0000-0000-0000-000000000001';
        $job = new ExportProductJob($user, '00000000-0000-0000-0000-000000000002');

        $this->assertEquals('Produk', $job->getModuleName());
    }

    public function test_it_generates_valid_filename_pattern(): void
    {
        $user = new User;
        $user->id = '00000000-0000-0000-0000-000000000001';
        $job = new ExportProductJob($user, '00000000-0000-0000-0000-000000000002');

        $fileName = $job->getFileName();

        $this->assertStringStartsWith('produk_export_', $fileName);
        $this->assertStringEndsWith('.xlsx', $fileName);
    }
}
