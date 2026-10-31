<?php

namespace Tests\Feature\App\Settings;

use App\Enums\FeatureEnum;
use App\Enums\PaymentMethodType;
use App\Enums\PaymentReferencePolicy;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Master\PaymentMethod;
use App\Models\Outlet;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PaymentMethodTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

    protected Outlet $outlet;

    protected string $appDomain;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.testing.page_paths' => [resource_path('js/Pages/App')]]);
        $this->seed(DatabaseSeeder::class);
        $this->appDomain = config('domain.app', 'app.sollu.test');

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true, 'features' => array_column(FeatureEnum::cases(), 'value')]
        );

        $this->business = Business::create([
            'name' => 'Payment Test Business',
            'owner_name' => 'Payment Owner',
            'email' => 'payment_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
            'settings' => ['active_features' => array_column(FeatureEnum::cases(), 'value')],
        ]);

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Payment Admin',
            'email' => 'admin_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
            'is_root_user' => true,
        ]);

        $this->outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Main Outlet',
            'is_active' => true,
        ]);
    }

    public function test_can_view_payment_methods_page(): void
    {
        $response = $this->actingAs($this->user)
            ->get("http://{$this->appDomain}/settings/payment-methods");

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Settings/PaymentMethod/Index')
            ->has('paymentMethods')
            ->has('types')
            ->has('referencePolicies')
        );
    }

    public function test_can_store_payment_method_with_qr_image_and_operational_attributes(): void
    {
        Storage::fake('public');

        $qrFile = UploadedFile::fake()->image('qris_sample.png', 300, 300);

        $payload = [
            'name' => 'QRIS BCA Dynamic',
            'type' => PaymentMethodType::QRIS->value,
            'provider' => 'BCA',
            'reference_policy' => PaymentReferencePolicy::MANDATORY->value,
            'open_cash_drawer' => false,
            'instructions' => 'Tunjukkan QR code kepada pelanggan.',
            'qr_image' => $qrFile,
            'is_active' => true,
            'outlet_ids' => [$this->outlet->id],
        ];

        $response = $this->actingAs($this->user)
            ->post("http://{$this->appDomain}/settings/payment-methods", $payload);

        $response->assertRedirect();

        $pm = PaymentMethod::where('name', 'QRIS BCA Dynamic')->first();
        $this->assertNotNull($pm);
        $this->assertEquals(PaymentMethodType::QRIS->value, $pm->type);
        $this->assertEquals('BCA', $pm->provider);
        $this->assertEquals(PaymentReferencePolicy::MANDATORY, $pm->reference_policy);
        $this->assertFalse($pm->open_cash_drawer);
        $this->assertFalse($pm->is_system);
        $this->assertTrue($pm->is_active);
        $this->assertNotNull($pm->qr_image_path);

        Storage::disk('public')->assertExists($pm->qr_image_path);
    }

    public function test_can_toggle_global_active_status(): void
    {
        $pm = PaymentMethod::create([
            'business_id' => $this->business->id,
            'name' => 'Bank Mandiri Transfer',
            'type' => PaymentMethodType::BANK_TRANSFER->value,
            'reference_policy' => PaymentReferencePolicy::OPTIONAL,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->patch("http://{$this->appDomain}/settings/payment-methods/{$pm->id}/toggle-active");

        $response->assertRedirect();

        $pm->refresh();
        $this->assertFalse($pm->is_active);

        $this->actingAs($this->user)
            ->patch("http://{$this->appDomain}/settings/payment-methods/{$pm->id}/toggle-active");

        $pm->refresh();
        $this->assertTrue($pm->is_active);
    }

    public function test_system_payment_method_cannot_be_deleted(): void
    {
        $systemCash = PaymentMethod::create([
            'business_id' => $this->business->id,
            'name' => 'Tunai',
            'type' => PaymentMethodType::CASH->value,
            'reference_policy' => PaymentReferencePolicy::HIDDEN,
            'open_cash_drawer' => true,
            'is_system' => true,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)
            ->delete("http://{$this->appDomain}/settings/payment-methods/{$systemCash->id}");

        $response->assertRedirect();
        $response->assertSessionHas('failed', 'Metode pembayaran sistem tidak dapat dihapus.');

        $this->assertDatabaseHas('payment_methods', ['id' => $systemCash->id]);
    }

    public function test_payment_methods_are_ordered_by_industry_standard(): void
    {
        PaymentMethod::create([
            'business_id' => $this->business->id,
            'name' => 'Bank BCA',
            'type' => PaymentMethodType::BANK_TRANSFER->value,
            'reference_policy' => PaymentReferencePolicy::OPTIONAL,
        ]);

        PaymentMethod::create([
            'business_id' => $this->business->id,
            'name' => 'GoPay',
            'type' => PaymentMethodType::EWALLET->value,
            'reference_policy' => PaymentReferencePolicy::OPTIONAL,
        ]);

        PaymentMethod::create([
            'business_id' => $this->business->id,
            'name' => 'Tunai',
            'type' => PaymentMethodType::CASH->value,
            'reference_policy' => PaymentReferencePolicy::HIDDEN,
            'is_system' => true,
        ]);

        PaymentMethod::create([
            'business_id' => $this->business->id,
            'name' => 'QRIS Dinamis',
            'type' => PaymentMethodType::QRIS->value,
            'reference_policy' => PaymentReferencePolicy::MANDATORY,
        ]);

        $ordered = PaymentMethod::where('business_id', $this->business->id)
            ->orderedByIndustryStandard()
            ->pluck('type')
            ->toArray();

        $this->assertEquals(['cash', 'qris', 'ewallet', 'bank_transfer'], $ordered);
    }

    public function test_system_payment_method_type_cannot_be_changed(): void
    {
        $systemCash = PaymentMethod::create([
            'business_id' => $this->business->id,
            'name' => 'Tunai',
            'type' => PaymentMethodType::CASH->value,
            'reference_policy' => PaymentReferencePolicy::HIDDEN,
            'open_cash_drawer' => true,
            'is_system' => true,
            'is_active' => true,
        ]);

        $payload = [
            'name' => 'Tunai Diubah',
            'type' => PaymentMethodType::EDC->value,
            'reference_policy' => PaymentReferencePolicy::OPTIONAL->value,
        ];

        $response = $this->actingAs($this->user)
            ->put("http://{$this->appDomain}/settings/payment-methods/{$systemCash->id}", $payload);

        $response->assertRedirect();
        $response->assertSessionHas('failed', 'Tipe metode pembayaran sistem tidak dapat diubah.');

        $systemCash->refresh();
        $this->assertEquals(PaymentMethodType::CASH->value, $systemCash->type);
    }

    public function test_can_update_payment_method_with_new_operational_attributes(): void
    {
        $pm = PaymentMethod::create([
            'business_id' => $this->business->id,
            'name' => 'EDC Mandiri',
            'type' => PaymentMethodType::EDC->value,
            'provider' => 'Mandiri',
            'reference_policy' => PaymentReferencePolicy::MANDATORY,
            'open_cash_drawer' => false,
            'is_active' => true,
        ]);

        $payload = [
            'name' => 'EDC Mandiri Plus',
            'type' => PaymentMethodType::EDC->value,
            'provider' => 'Bank Mandiri',
            'reference_policy' => PaymentReferencePolicy::MANDATORY->value,
            'open_cash_drawer' => true,
            'instructions' => 'Pastikan resi tercetak.',
            'is_active' => true,
        ];

        $response = $this->actingAs($this->user)
            ->put("http://{$this->appDomain}/settings/payment-methods/{$pm->id}", $payload);

        $response->assertRedirect();

        $pm->refresh();
        $this->assertEquals('EDC Mandiri Plus', $pm->name);
        $this->assertEquals('Bank Mandiri', $pm->provider);
        $this->assertTrue($pm->open_cash_drawer);
        $this->assertEquals('Pastikan resi tercetak.', $pm->instructions);
    }
}
