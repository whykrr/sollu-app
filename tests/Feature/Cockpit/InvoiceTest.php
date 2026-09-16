<?php

namespace Tests\Feature\Cockpit;

use App\Constants\FlashDataVariable;
use App\Enums\BusinessStatus;
use App\Enums\PaymentManualValidationStatus;
use App\Enums\SubscriptionInvoice\Status as InvoiceStatusEnum;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\CockpitUser;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\PaymentManualValidation;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Notifications\InvoicePaymentRejectedNotification;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected CockpitUser $admin;

    protected string $cockpitHost;

    protected BusinessType $businessType;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.testing.page_paths' => [
            resource_path('js/Pages'),
        ]]);
        $this->seed(DatabaseSeeder::class);

        $this->cockpitHost = config('domain.cockpit', 'cockpit.sollu.test');

        $this->admin = CockpitUser::create([
            'name' => 'Cockpit Admin',
            'email' => 'admin_invoice_test@sollu.test',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);

        $this->businessType = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true]
        );
    }

    protected function createBusiness(array $attributes = []): Business
    {
        return Business::create(array_merge([
            'name' => 'Bisnis Sample',
            'owner_name' => 'Budi Owner',
            'email' => 'budi'.uniqid().'@sample.test',
            'phone' => '081234567890',
            'status' => BusinessStatus::Active,
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $this->businessType->id,
        ], $attributes));
    }

    public function test_guest_cannot_access_cockpit_invoices(): void
    {
        $response = $this->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->get("http://{$this->cockpitHost}/invoices");

        $response->assertStatus(302);
        $response->assertRedirect(route('cockpit.login'));
    }

    public function test_admin_can_view_invoices_index_with_metrics_and_filters(): void
    {
        $business = $this->createBusiness([
            'name' => 'Kedai Kopi Selera',
        ]);

        $invoice = Invoice::create([
            'business_id' => $business->id,
            'invoice_number' => 'INV-202609-0001',
            'status' => InvoiceStatusEnum::Unpaid,
            'subtotal' => 150000.0,
            'tax_amount' => 0.0,
            'total_amount' => 150000.0,
            'due_date' => now()->addDays(3),
        ]);

        PaymentManualValidation::create([
            'invoice_id' => $invoice->id,
            'validation_status' => PaymentManualValidationStatus::Pending,
            'payment_proof_url' => 'proofs/test_proof.jpg',
        ]);

        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->get("http://{$this->cockpitHost}/invoices");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Cockpit/Invoice/Index')
            ->has('invoices.data', 1)
            ->has('metrics')
            ->has('filters')
            ->where('metrics.total_invoices', 1)
            ->where('metrics.pending_validation', 1)
            ->where('invoices.data.0.invoice_number', 'INV-202609-0001')
            ->where('invoices.data.0.status', 'pending_review')
        );
    }

    public function test_admin_can_filter_invoices_by_search(): void
    {
        $business1 = $this->createBusiness(['name' => 'Alpha Merchant']);
        $business2 = $this->createBusiness(['name' => 'Beta Merchant']);

        Invoice::create([
            'business_id' => $business1->id,
            'invoice_number' => 'INV-ALPHA-01',
            'status' => InvoiceStatusEnum::Paid,
            'subtotal' => 100000.0,
            'tax_amount' => 0.0,
            'total_amount' => 100000.0,
        ]);

        Invoice::create([
            'business_id' => $business2->id,
            'invoice_number' => 'INV-BETA-02',
            'status' => InvoiceStatusEnum::Paid,
            'subtotal' => 200000.0,
            'tax_amount' => 0.0,
            'total_amount' => 200000.0,
        ]);

        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->get("http://{$this->cockpitHost}/invoices?search=Alpha");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Cockpit/Invoice/Index')
            ->has('invoices.data', 1)
            ->where('invoices.data.0.invoice_number', 'INV-ALPHA-01')
        );
    }

    public function test_admin_can_filter_invoices_by_status(): void
    {
        $business = $this->createBusiness();

        $invPending = Invoice::create([
            'business_id' => $business->id,
            'invoice_number' => 'INV-PENDING-01',
            'status' => InvoiceStatusEnum::Unpaid,
            'subtotal' => 100000.0,
            'tax_amount' => 0.0,
            'total_amount' => 100000.0,
        ]);
        PaymentManualValidation::create([
            'invoice_id' => $invPending->id,
            'validation_status' => PaymentManualValidationStatus::Pending,
            'payment_proof_url' => 'proofs/sample_pending.png',
        ]);

        $invPaid = Invoice::create([
            'business_id' => $business->id,
            'invoice_number' => 'INV-PAID-02',
            'status' => InvoiceStatusEnum::Paid,
            'subtotal' => 100000.0,
            'tax_amount' => 0.0,
            'total_amount' => 100000.0,
        ]);

        $responsePending = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->get("http://{$this->cockpitHost}/invoices?status=pending");

        $responsePending->assertStatus(200);
        $responsePending->assertInertia(fn (Assert $page) => $page
            ->component('Cockpit/Invoice/Index')
            ->has('invoices.data', 1)
            ->where('invoices.data.0.invoice_number', 'INV-PENDING-01')
        );

        $responsePaid = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->get("http://{$this->cockpitHost}/invoices?status=paid");

        $responsePaid->assertStatus(200);
        $responsePaid->assertInertia(fn (Assert $page) => $page
            ->component('Cockpit/Invoice/Index')
            ->has('invoices.data', 1)
            ->where('invoices.data.0.invoice_number', 'INV-PAID-02')
        );
    }

    public function test_admin_can_view_invoice_detail_json_on_demand(): void
    {
        $business = $this->createBusiness([
            'name' => 'Toko Barokah',
            'owner_name' => 'Ahmad',
            'email' => 'ahmad@barokah.test',
        ]);

        $invoice = Invoice::create([
            'business_id' => $business->id,
            'invoice_number' => 'INV-DETAIL-TEST',
            'status' => InvoiceStatusEnum::Unpaid,
            'subtotal' => 300000.0,
            'tax_amount' => 0.0,
            'total_amount' => 300000.0,
            'due_date' => now()->addDays(5),
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'item_type' => 'subscription',
            'description' => 'Langganan Paket Pro (2 Outlet)',
            'quantity' => 2,
            'unit_price' => 150000.0,
            'subtotal' => 300000.0,
            'metadata' => [
                'outlet_name' => 'Outlet Utama',
            ],
        ]);

        PaymentManualValidation::create([
            'invoice_id' => $invoice->id,
            'validation_status' => PaymentManualValidationStatus::Pending,
            'payment_proof_url' => 'proofs/sample.png',
        ]);

        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->get("http://{$this->cockpitHost}/invoices/{$invoice->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $invoice->id,
            'invoice_number' => 'INV-DETAIL-TEST',
            'merchant' => 'Toko Barokah',
            'status' => 'pending_review',
            'subtotal' => 300000.0,
            'total_amount' => 300000.0,
            'outlet_name' => 'Outlet Utama',
            'items' => [
                [
                    'description' => 'Langganan Paket Pro (2 Outlet)',
                    'quantity' => 2,
                    'unit_price' => 150000.0,
                    'subtotal' => 300000.0,
                ],
            ],
        ]);
    }

    public function test_admin_can_approve_invoice_manual_payment(): void
    {
        $plan = SubscriptionPlan::create([
            'code' => 'plan_app_test',
            'name' => 'Plan Approval Test',
            'price_per_outlet' => 100000.0,
            'yearly_discount_percent' => 0,
            'is_active' => true,
            'is_public' => true,
        ]);

        $business = $this->createBusiness();

        $invoice = Invoice::create([
            'business_id' => $business->id,
            'invoice_number' => 'INV-APPROVE-TEST',
            'status' => InvoiceStatusEnum::Unpaid,
            'subtotal' => 100000.0,
            'tax_amount' => 0.0,
            'total_amount' => 100000.0,
        ]);

        InvoiceItem::create([
            'invoice_id' => $invoice->id,
            'item_type' => 'subscription',
            'description' => 'Langganan Plan Approval Test',
            'quantity' => 1,
            'unit_price' => 100000.0,
            'subtotal' => 100000.0,
            'metadata' => [
                'plan_id' => $plan->id,
                'billing_cycle' => 'monthly',
                'active_outlets' => 1,
            ],
        ]);

        $validation = PaymentManualValidation::create([
            'invoice_id' => $invoice->id,
            'validation_status' => PaymentManualValidationStatus::Pending,
            'payment_proof_url' => 'proofs/sample.png',
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'payment_method' => 'manual_transfer',
            'payment_reference' => 'PAY-REF-001',
            'amount' => 100000.0,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->post("http://{$this->cockpitHost}/invoices/{$invoice->id}/approve");

        $response->assertRedirect();
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value);

        $invoice->refresh();
        $this->assertEquals(InvoiceStatusEnum::Paid, $invoice->status);
        $this->assertNotNull($invoice->paid_at);

        $validation->refresh();
        $this->assertEquals(PaymentManualValidationStatus::Approved, $validation->validation_status);
        $this->assertEquals($this->admin->id, $validation->reviewed_by);
        $this->assertNotNull($validation->reviewed_at);
    }

    public function test_admin_can_reject_invoice_manual_payment(): void
    {
        Notification::fake();

        $business = $this->createBusiness();
        $user = User::create([
            'business_id' => $business->id,
            'name' => 'Owner Bisnis',
            'email' => 'owner@bisnis.test',
            'password' => bcrypt('secret123'),
        ]);

        $invoice = Invoice::create([
            'business_id' => $business->id,
            'invoice_number' => 'INV-REJECT-TEST',
            'status' => InvoiceStatusEnum::Unpaid,
            'subtotal' => 100000.0,
            'tax_amount' => 0.0,
            'total_amount' => 100000.0,
        ]);

        $validation = PaymentManualValidation::create([
            'invoice_id' => $invoice->id,
            'validation_status' => PaymentManualValidationStatus::Pending,
            'payment_proof_url' => 'proofs/sample.png',
        ]);

        Payment::create([
            'invoice_id' => $invoice->id,
            'payment_method' => 'manual_transfer',
            'payment_reference' => 'PAY-REF-002',
            'amount' => 100000.0,
            'status' => 'pending',
        ]);

        $reason = 'Foto bukti transfer tidak terbaca (buram).';

        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->post("http://{$this->cockpitHost}/invoices/{$invoice->id}/reject", [
                'reason' => $reason,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value);

        $validation->refresh();
        $this->assertEquals(PaymentManualValidationStatus::Rejected, $validation->validation_status);
        $this->assertEquals($reason, $validation->rejection_reason);
        $this->assertEquals($this->admin->id, $validation->reviewed_by);

        Notification::assertSentTo($user, InvoicePaymentRejectedNotification::class);
    }
}
