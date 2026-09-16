<?php

namespace Tests\Feature\Discord;

use App\Events\Invoice\PaymentProofUploaded;
use App\Events\User\BusinessRegistered;
use App\Listeners\Discord\SendNewMerchantDiscordNotification;
use App\Listeners\Discord\SendPaymentValidationDiscordNotification;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\CockpitUser;
use App\Models\Invoice;
use App\Models\PaymentManualValidation;
use App\Services\Discord\DiscordWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Mockery;
use Tests\TestCase;

class DiscordNotificationEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_new_merchant_discord_notification_listener_sends_formatted_payload(): void
    {
        $type = BusinessType::create([
            'name' => 'F&B',
            'code' => 'fnb',
            'is_visible' => true,
        ]);

        $business = Business::create([
            'name' => 'Kedai Kopi Bahagia',
            'owner_name' => 'Budi Santoso',
            'email' => 'budi@kopi.test',
            'phone' => '081234567890',
            'status' => 'active',
            'business_type_id' => $type->id,
            'trial_end_at' => now()->addDays(15),
        ]);

        $outlet = $business->outlets()->create([
            'name' => 'Outlet Senopati',
            'is_main_outlet' => true,
        ]);

        $user = $business->users()->create([
            'name' => 'Budi Santoso',
            'email' => 'budi@kopi.test',
            'phone' => '081234567890',
            'password' => 'password123',
            'is_root_user' => true,
        ]);

        $called = false;
        $discordMock = Mockery::mock(DiscordWebhookService::class);
        $discordMock->shouldReceive('send')
            ->once()
            ->withArgs(function ($channelKey, $embed, $components) use ($business, &$called) {
                $hasName = collect($embed['fields'])->contains(fn ($f) => $f['name'] === 'Nama Bisnis' && str_contains($f['value'], $business->name));
                $hasButton = ! empty($components[0]['components'][0]['url']) && str_contains($components[0]['components'][0]['url'], $business->id);
                $called = ($channelKey === 'merchant_registration'
                    && $embed['title'] === '🏪 Merchant Baru Mendaftar!'
                    && $embed['color'] === hexdec('10B981')
                    && $hasName
                    && $hasButton);

                return $called;
            })
            ->andReturn(true);

        $listener = new SendNewMerchantDiscordNotification($discordMock);
        $listener->handle(new BusinessRegistered($business, $user, $outlet));

        $this->assertTrue($called);
    }

    public function test_send_payment_validation_discord_notification_listener_sends_payload_with_attachment(): void
    {
        Storage::fake();

        $type = BusinessType::create([
            'name' => 'Retail',
            'code' => 'retail',
            'is_visible' => true,
        ]);

        $business = Business::create([
            'name' => 'Toko Buku Maju',
            'owner_name' => 'Dewi Lestari',
            'email' => 'dewi@buku.test',
            'phone' => '08987654321',
            'status' => 'active',
            'business_type_id' => $type->id,
            'trial_end_at' => now()->addDays(15),
        ]);

        $invoice = Invoice::create([
            'business_id' => $business->id,
            'invoice_number' => 'INV-2026-09-9999',
            'total_amount' => 150000,
            'status' => 'open',
        ]);

        $invoice->items()->create([
            'item_type' => 'subscription',
            'description' => 'Paket Basic (1 Bulan)',
            'amount' => 150000,
            'subtotal' => 150000,
        ]);

        $filePath = 'invoices/payment_proof/test_proof.jpg';
        Storage::put($filePath, 'fake-binary-image-data');

        $validation = PaymentManualValidation::create([
            'invoice_id' => $invoice->id,
            'payment_proof_url' => $filePath,
            'validation_status' => 'pending',
        ]);

        $called = false;
        $discordMock = Mockery::mock(DiscordWebhookService::class);
        $discordMock->shouldReceive('send')
            ->once()
            ->withArgs(function ($channelKey, $embed, $components, $attachment) use ($invoice, &$called) {
                $hasInvoice = collect($embed['fields'])->contains(fn ($f) => $f['name'] === 'No. Invoice' && str_contains($f['value'], $invoice->invoice_number));
                $hasButton = ! empty($components[0]['components'][0]['url']) && str_contains($components[0]['components'][0]['url'], $invoice->invoice_number);
                $called = ($channelKey === 'payment_validation'
                    && $embed['title'] === '💳 Permintaan Validasi Pembayaran Manual'
                    && $embed['color'] === hexdec('F59E0B')
                    && $hasInvoice
                    && ! empty($attachment['contents'])
                    && $hasButton);

                return $called;
            })
            ->andReturn(true);

        $listener = new SendPaymentValidationDiscordNotification($discordMock);
        $listener->handle(new PaymentProofUploaded($invoice, $validation));

        $this->assertTrue($called);
    }

    public function test_upload_proof_dispatches_payment_proof_uploaded_event(): void
    {
        Storage::fake('local');
        Event::fake([PaymentProofUploaded::class]);

        $this->seed(\Database\Seeders\Production\RolePermissionSeeder::class);

        $type = BusinessType::create([
            'name' => 'F&B',
            'code' => 'fnb',
            'is_visible' => true,
        ]);

        $business = Business::create([
            'name' => 'Resto Sedap',
            'owner_name' => 'Siti Rahma',
            'email' => 'siti@resto.test',
            'phone' => '081233344455',
            'status' => 'active',
            'business_type_id' => $type->id,
            'trial_end_at' => now()->addDays(15),
        ]);

        $user = $business->users()->create([
            'name' => 'Siti Rahma',
            'email' => 'siti@resto.test',
            'password' => 'password123',
            'is_root_user' => true,
        ]);

        app(\App\Services\App\Role\RoleProvisioningService::class)->provision($business);
        setPermissionsTeamId($business->id);
        $user->assignRole(\App\Enums\RoleEnum::OWNER->value);

        $invoice = Invoice::create([
            'business_id' => $business->id,
            'invoice_number' => 'INV-2026-TEST-001',
            'total_amount' => 200000,
            'status' => 'open',
        ]);

        $file = UploadedFile::fake()->image('bukti_transfer.png');

        $response = $this->actingAs($user, 'business')
            ->post(route('settings.billing.invoices.upload-proof', $invoice->invoice_number), [
                'payment_proof' => $file,
            ]);

        $response->assertRedirect(route('settings.billing.index', ['open_invoice' => $invoice->invoice_number]));

        Event::assertDispatched(PaymentProofUploaded::class, function ($event) use ($invoice) {
            return $event->invoice->id === $invoice->id
                && ($event->validation->validation_status === \App\Enums\PaymentManualValidationStatus::Pending || $event->validation->validation_status?->value === 'pending' || $event->validation->validation_status === 'pending');
        });
    }

    public function test_cockpit_invoices_index_filters_by_open_invoice(): void
    {
        $cockpitUser = CockpitUser::create([
            'name' => 'Admin Cockpit',
            'email' => 'admin@sollu.test',
            'password' => 'secret123',
        ]);

        $type = BusinessType::create([
            'name' => 'Retail',
            'code' => 'retail',
            'is_visible' => true,
        ]);

        $business = Business::create([
            'name' => 'Bisnis Alpha',
            'owner_name' => 'Alpha Man',
            'email' => 'alpha@test.com',
            'phone' => '081111111',
            'status' => 'active',
            'business_type_id' => $type->id,
            'trial_end_at' => now()->addDays(15),
        ]);

        $inv1 = Invoice::create([
            'business_id' => $business->id,
            'invoice_number' => 'INV-ALPHA-001',
            'total_amount' => 100000,
            'status' => 'open',
        ]);

        $inv2 = Invoice::create([
            'business_id' => $business->id,
            'invoice_number' => 'INV-BETA-002',
            'total_amount' => 200000,
            'status' => 'open',
        ]);

        $response = $this->actingAs($cockpitUser, 'cockpit')
            ->get(route('cockpit.invoices.index', ['open_invoice' => $inv1->invoice_number]));

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Cockpit/Invoice/Index')
            ->has('invoices.data', 1)
            ->where('invoices.data.0.invoice_number', $inv1->invoice_number)
            ->where('filters.open_invoice', $inv1->invoice_number)
        );
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
