<?php

namespace Tests\Unit\Services\App\Invoice;

use App\Enums\PaymentManualValidationStatus;
use App\Enums\SubscriptionInvoice\Status;
use App\Events\Invoice\PaymentProofUploaded;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Invoice;
use App\Models\PaymentManualValidation;
use App\Services\App\Invoice\UploadPaymentProofService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UploadPaymentProofServiceTest extends TestCase
{
    use RefreshDatabase;

    protected UploadPaymentProofService $service;

    protected Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Event::fake([PaymentProofUploaded::class]);

        $this->seed(\Database\Seeders\DatabaseSeeder::class);

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );

        $business = Business::create([
            'name' => 'Test Business',
            'owner_name' => 'Owner',
            'email' => 'owner_'.uniqid().'@test.com',
            'phone' => '08123456789',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $this->invoice = Invoice::create([
            'business_id' => $business->id,
            'invoice_number' => 'INV-TEST-'.uniqid(),
            'total_amount' => 150000,
            'subtotal' => 150000,
            'status' => Status::Unpaid,
            'billing_date' => now(),
            'due_date' => now()->addDays(3),
        ]);

        $this->service = new UploadPaymentProofService;
    }

    public function test_it_uploads_payment_proof_and_optimizes_image(): void
    {
        $file = UploadedFile::fake()->image('proof.jpg', 1800, 1200);

        $validation = $this->service->execute($this->invoice, $file);

        $this->assertInstanceOf(PaymentManualValidation::class, $validation);
        $this->assertSame($this->invoice->id, $validation->invoice_id);
        $this->assertSame(PaymentManualValidationStatus::Pending, $validation->validation_status);
        $this->assertNotNull($validation->payment_proof_url);
        $this->assertStringStartsWith('invoices/payment_proof/', $validation->payment_proof_url);
        $this->assertStringEndsWith('.webp', $validation->payment_proof_url);

        Storage::disk(config('filesystems.default'))->assertExists($validation->payment_proof_url);

        Event::assertDispatched(PaymentProofUploaded::class);
    }

    public function test_it_deletes_old_payment_proof_when_reuploaded(): void
    {
        $file1 = UploadedFile::fake()->image('proof1.jpg', 800, 600);
        $validation1 = $this->service->execute($this->invoice, $file1);
        $oldPath = $validation1->payment_proof_url;

        Storage::disk(config('filesystems.default'))->assertExists($oldPath);

        $file2 = UploadedFile::fake()->image('proof2.jpg', 800, 600);
        $validation2 = $this->service->execute($this->invoice, $file2);

        $this->assertNotSame($oldPath, $validation2->payment_proof_url);
        Storage::disk(config('filesystems.default'))->assertMissing($oldPath);
        Storage::disk(config('filesystems.default'))->assertExists($validation2->payment_proof_url);
    }
}
