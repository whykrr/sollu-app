<?php

namespace Tests\Feature\Invoice;

use App\Enums\SubscriptionInvoice\Status;
use App\Events\Invoice\InvoicePaid;
use App\Listeners\Invoice\SendSubscriptionInvoiceNotification;
use App\Mail\SubscriptionInvoice;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\CockpitUser;
use App\Models\Invoice;
use App\Models\PaymentManualValidation;
use App\Notifications\SubscriptionInvoicePaidNotification;
use App\Services\Cockpit\Invoice\ApproveInvoiceValidationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SendSubscriptionInvoiceEmailTest extends TestCase
{
    use RefreshDatabase;

    protected BusinessType $businessType;

    protected Business $business;

    protected Invoice $invoice;

    protected function setUp(): void
    {
        parent::setUp();

        $this->businessType = BusinessType::create([
            'name' => 'Food & Beverage',
            'code' => 'fnb',
            'is_visible' => true,
        ]);

        $this->business = Business::create([
            'name' => 'Kopi Mantap',
            'owner_name' => 'Budi Sudarsono',
            'email' => 'budi@kopimantap.test',
            'phone' => '081298765432',
            'status' => 'active',
            'business_type_id' => $this->businessType->id,
            'trial_end_at' => now()->addDays(14),
        ]);

        $this->invoice = Invoice::create([
            'business_id' => $this->business->id,
            'invoice_number' => 'INV-2026-TEST-999',
            'status' => Status::Open,
            'subtotal' => 199000,
            'tax_amount' => 0,
            'total_amount' => 199000,
            'due_date' => now()->addDays(3),
        ]);

        $this->invoice->items()->create([
            'item_type' => 'recurring_plan',
            'description' => 'Paket Basic (1 Bulan)',
            'quantity' => 1,
            'unit_price' => 199000,
            'subtotal' => 199000,
        ]);

        $this->invoice->payments()->create([
            'payment_method' => 'manual',
            'payment_reference' => 'INV-2026-TEST-999-MANUAL',
            'amount' => 199000,
            'status' => 'pending',
        ]);
    }

    public function test_invoice_paid_event_triggers_send_subscription_invoice_notification_listener(): void
    {
        Event::fake([InvoicePaid::class]);

        $completeService = app(\App\Services\App\Invoice\CompleteInvoiceService::class);
        $completeService->execute($this->invoice);

        Event::assertDispatched(InvoicePaid::class, function ($event) {
            return $event->invoice->id === $this->invoice->id;
        });
    }

    public function test_listener_sends_notification_to_business_owner(): void
    {
        Notification::fake();

        $user = $this->business->users()->create([
            'name' => 'Budi Sudarsono',
            'email' => 'budi@kopimantap.test',
            'password' => 'password123',
            'is_root_user' => true,
        ]);

        $listener = new SendSubscriptionInvoiceNotification;
        $listener->handle(new InvoicePaid($this->invoice));

        Notification::assertSentTo($user, SubscriptionInvoicePaidNotification::class, function ($notification) {
            return $notification->invoice->id === $this->invoice->id;
        });
    }

    public function test_listener_sends_notification_to_business_email_if_no_user_exists(): void
    {
        Notification::fake();

        $listener = new SendSubscriptionInvoiceNotification;
        $listener->handle(new InvoicePaid($this->invoice));

        Notification::assertSentOnDemand(
            SubscriptionInvoicePaidNotification::class,
            function ($notification, $channels, $notifiable) {
                return in_array('mail', $channels)
                    && $notifiable->routes['mail'] === $this->business->email;
            }
        );
    }

    public function test_subscription_invoice_mailable_renders_and_has_pdf_attachment(): void
    {
        $this->invoice->update([
            'status' => Status::Paid,
            'paid_at' => now(),
        ]);

        $payment = $this->invoice->payments()->first();
        $mailable = new SubscriptionInvoice($this->invoice, $payment);

        $mailable->assertHasSubject("Invoice Pembayaran Langganan #{$this->invoice->invoice_number} - Sollu App");
        $mailable->assertSeeInHtml($this->invoice->invoice_number);
        $mailable->assertSeeInHtml($this->business->name);
        $mailable->assertSeeInHtml('Rp 199.000');

        $attachments = $mailable->attachments();
        $this->assertNotEmpty($attachments);
        $this->assertInstanceOf(Attachment::class, $attachments[0]);
    }

    public function test_cockpit_approval_triggers_complete_invoice_and_dispatches_event(): void
    {
        Event::fake([InvoicePaid::class]);

        $cockpitUser = CockpitUser::create([
            'name' => 'Super Admin',
            'email' => 'admin@sollu.test',
            'password' => 'secret123',
        ]);

        $this->actingAs($cockpitUser, 'cockpit');

        PaymentManualValidation::create([
            'invoice_id' => $this->invoice->id,
            'payment_proof_url' => 'invoices/payment_proof/dummy.jpg',
            'validation_status' => 'pending',
        ]);

        $approveService = app(ApproveInvoiceValidationService::class);
        $approveService->execute($this->invoice);

        $this->invoice->refresh();
        $this->assertEquals(Status::Paid, $this->invoice->status);
        $this->assertNotNull($this->invoice->paid_at);

        Event::assertDispatched(InvoicePaid::class, function ($event) {
            return $event->invoice->id === $this->invoice->id;
        });
    }
}
