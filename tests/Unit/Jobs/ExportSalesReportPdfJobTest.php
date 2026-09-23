<?php

namespace Tests\Unit\Jobs;

use App\Jobs\Reports\Pdf\ExportSalesReportPdfJob;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Outlet;
use App\Models\User;
use App\Notifications\DocumentExportCompleted;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExportSalesReportPdfJobTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

    protected Outlet $outlet;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);

        Storage::fake('public');
        Notification::fake();

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $this->business = Business::create([
            'name' => 'PDF Report Business',
            'owner_name' => 'Owner',
            'email' => 'owner_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Owner Admin',
            'email' => 'admin_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
            'is_root_user' => true,
        ]);

        $this->outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Utama',
            'is_main_outlet' => true,
        ]);

        $this->user->outlets()->attach($this->outlet->id);
    }

    public function test_it_generates_sales_report_pdf_and_stores_on_public_disk(): void
    {
        $job = new ExportSalesReportPdfJob(
            $this->user,
            [$this->outlet->id],
            now()->subDays(7),
            now()
        );

        $job->handle();

        Notification::assertSentTo(
            $this->user,
            DocumentExportCompleted::class,
            function (DocumentExportCompleted $notification) {
                $fileName = $notification->meta['file_name'] ?? null;
                $this->assertNotNull($fileName);
                $this->assertStringStartsWith('sales_report_', $fileName);
                $this->assertStringEndsWith('.pdf', $fileName);

                // File must exist on public disk
                Storage::disk('public')->assertExists('exports/'.$fileName);

                return true;
            }
        );
    }

    public function test_it_downloads_and_deletes_export_file(): void
    {
        $fileName = 'test_download_export.xlsx';
        Storage::disk('public')->put('exports/'.$fileName, 'excel export binary content');

        Storage::disk('public')->assertExists('exports/'.$fileName);

        $response = $this->actingAs($this->user, 'business')
            ->get(route('exports.download', ['file' => $fileName]));

        $response->assertOk();
        $response->assertDownload($fileName);

        // File should be deleted from storage immediately upon download
        Storage::disk('public')->assertMissing('exports/'.$fileName);
    }
}
