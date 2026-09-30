<?php

declare(strict_types=1);

namespace Tests\Feature\Transaction;

use App\Enums\FeatureEnum;
use App\Enums\InventoryMovementType;
use App\Enums\PaymentTermEnum;
use App\Enums\PlanEnum;
use App\Enums\ProductTypeEnum;
use App\Enums\SalesChannelEnum;
use App\Enums\SubscriptionStatus;
use App\Enums\TransactionPaymentStatus;
use App\Enums\TransactionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryItem;
use App\Models\Master\Customer;
use App\Models\Master\PaymentMethod;
use App\Models\Master\Product;
use App\Models\Master\ProductItem;
use App\Models\Outlet;
use App\Models\Sales\Transaction;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\App\Inventory\InventoryCostingService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SalesTransactionControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

    protected Outlet $outlet;

    protected Customer $customer;

    protected Product $product;

    protected ProductItem $productItem;

    protected InventoryItem $inventoryItem;

    protected PaymentMethod $paymentMethod;

    protected string $appDomain;

    protected function setUp(): void
    {
        parent::setUp();

        config(['inertia.testing.page_paths' => [
            resource_path('js/Pages'),
            resource_path('js/Pages/App'),
        ]]);

        $this->seed(DatabaseSeeder::class);
        $this->appDomain = config('domain.app', 'app.sollu.test');

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            [
                'name' => 'Retail',
                'sort_order' => 1,
                'is_visible' => true,
                'features' => [FeatureEnum::INVOICE_DEBT->value],
            ]
        );

        $this->business = Business::create([
            'name' => 'Test Sales Merchant',
            'owner_name' => 'Merchant Owner',
            'email' => 'sales_test_'.uniqid().'@test.com',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
            'settings' => [
                'active_features' => [FeatureEnum::INVOICE_DEBT->value],
            ],
        ]);

        $plan = SubscriptionPlan::where('code', PlanEnum::PRO->value)->first()
            ?? SubscriptionPlan::where('code', PlanEnum::BASIC->value)->first()
            ?? SubscriptionPlan::first();

        if ($plan) {
            Subscription::create([
                'business_id' => $this->business->id,
                'plan_id' => $plan->id,
                'status' => SubscriptionStatus::Active,
                'billing_cycle' => 'monthly',
                'started_at' => now()->subDay(),
                'expired_at' => now()->addMonth(),
            ]);
        }
        $this->business->clearMemoizedFeatures();

        $this->outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Sales Test Outlet',
        ]);

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Sales Person',
            'email' => 'salesperson_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->user->outlets()->attach($this->outlet->id);

        setPermissionsTeamId($this->business->id);

        // Grant permissions
        $permissions = [
            'transaction.view',
            'transaction.create',
            'transaction.issue_invoice',
            'transaction.record_payment',
            'transaction.edit_due_date',
            'transaction.cancel',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'business']);
        }

        $this->user->givePermissionTo($permissions);

        $this->customer = Customer::create([
            'business_id' => $this->business->id,
            'name' => 'Customer B2B PT Maju',
            'phone' => '0812987654',
            'email' => 'maju@pt.com',
        ]);

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Kopi Robusta Super 1kg',
            'product_type' => ProductTypeEnum::BASIC,
            'is_active' => true,
        ]);

        $this->productItem = ProductItem::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'name' => 'Kopi Robusta Super 1kg',
            'sku' => 'KRS-1000',
            'item_type' => 'variant_sku',
            'track_inventory' => true,
            'is_active' => true,
        ]);

        $this->inventoryItem = InventoryItem::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'product_item_id' => $this->productItem->id,
            'is_active' => true,
        ]);

        $this->paymentMethod = PaymentMethod::create([
            'business_id' => $this->business->id,
            'name' => 'Transfer Kas',
            'code' => 'transfer_kas',
            'type' => 'bank_transfer',
            'is_active' => true,
        ]);

        // Stock incoming 50 pcs
        app(InventoryCostingService::class)->recordIncomingStock(
            $this->business,
            $this->outlet,
            $this->inventoryItem,
            50.0,
            45000.0,
            InventoryMovementType::InitialStock,
            null,
            'Stok Masuk',
            $this->user
        );
    }

    public function test_it_stores_draft_sales_transaction(): void
    {
        $payload = [
            'outlet_id' => $this->outlet->id,
            'channel' => SalesChannelEnum::Wholesale->value,
            'transaction_date' => now()->toDateTimeString(),
            'payment_term' => PaymentTermEnum::Credit->value,
            'customer_id' => $this->customer->id,
            'due_date' => now()->addDays(30)->toDateString(),
            'notes' => 'Catatan Penjualan B2B',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_item_id' => $this->productItem->id,
                    'inventory_item_id' => $this->inventoryItem->id,
                    'qty' => 10,
                    'price' => 80000,
                    'discount_amount' => 50000,
                ],
            ],
            'discount_type' => 'manual',
            'discount_value' => 20000,
            'shipping_fee' => 15000,
            'service_charge_amount' => 5000,
            'issue_now' => false,
        ];

        $response = $this->actingAs($this->user, 'business')
            ->postJson("http://{$this->appDomain}/transactions/sales", $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('message', 'Draf penjualan berhasil disimpan.');
        $this->assertDatabaseHas('transactions', [
            'outlet_id' => $this->outlet->id,
            'channel' => SalesChannelEnum::Wholesale->value,
            'status' => TransactionStatus::Draft->value,
            'payment_status' => TransactionPaymentStatus::Draft->value,
        ]);
    }

    public function test_it_stores_and_immediately_issues_invoice_when_issue_now_is_true(): void
    {
        $payload = [
            'outlet_id' => $this->outlet->id,
            'channel' => SalesChannelEnum::Wholesale->value,
            'transaction_date' => now()->toDateTimeString(),
            'payment_term' => PaymentTermEnum::Credit->value,
            'customer_id' => $this->customer->id,
            'due_date' => now()->addDays(14)->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_item_id' => $this->productItem->id,
                    'inventory_item_id' => $this->inventoryItem->id,
                    'qty' => 5,
                    'price' => 80000,
                    'discount_amount' => 0,
                ],
            ],
            'issue_now' => true,
        ];

        $response = $this->actingAs($this->user, 'business')
            ->postJson("http://{$this->appDomain}/transactions/sales", $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('message', 'Faktur penjualan berhasil diterbitkan.');
        $this->assertDatabaseHas('transactions', [
            'outlet_id' => $this->outlet->id,
            'status' => TransactionStatus::Unpaid->value,
        ]);
    }

    public function test_it_stores_and_issues_invoice_with_decoupled_product_item_selection(): void
    {
        // Frontend only provides product_id & product_item_id without inventory_item_id
        $payload = [
            'outlet_id' => $this->outlet->id,
            'channel' => SalesChannelEnum::Wholesale->value,
            'transaction_date' => now()->toDateTimeString(),
            'payment_term' => PaymentTermEnum::Credit->value,
            'customer_id' => $this->customer->id,
            'due_date' => now()->addDays(14)->toDateString(),
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_item_id' => $this->productItem->id,
                    'qty' => 5,
                    'price' => 80000,
                    'discount_amount' => 0,
                ],
            ],
            'issue_now' => true,
        ];

        $response = $this->actingAs($this->user, 'business')
            ->postJson("http://{$this->appDomain}/transactions/sales", $payload);

        $response->assertStatus(201);
        $response->assertJsonPath('message', 'Faktur penjualan berhasil diterbitkan.');

        // Verify transaction item automatically resolved inventory_item_id and item details
        $this->assertDatabaseHas('transaction_items', [
            'product_id' => $this->product->id,
            'product_item_id' => $this->productItem->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'product_name' => 'Kopi Robusta Super 1kg',
            'sku' => 'KRS-1000',
        ]);
    }

    public function test_it_issues_an_existing_draft_invoice(): void
    {
        $transaction = Transaction::create([
            'outlet_id' => $this->outlet->id,
            'channel' => SalesChannelEnum::Wholesale->value,
            'transaction_number' => 'TRX/202609/0099',
            'transaction_date' => now(),
            'total' => 100000,
            'balance_due' => 100000,
            'status' => TransactionStatus::Draft->value,
            'payment_status' => TransactionPaymentStatus::Draft->value,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $transaction->items()->create([
            'product_id' => $this->product->id,
            'product_name' => 'Kopi Robusta Super',
            'inventory_item_id' => $this->inventoryItem->id,
            'qty' => 2,
            'price' => 50000,
            'subtotal' => 100000,
        ]);

        $transaction->invoice()->create([
            'invoice_number' => 'INV/202609/0099',
            'invoice_date' => now(),
            'payment_term' => PaymentTermEnum::Credit->value,
            'status' => TransactionStatus::Draft->value,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->postJson("http://{$this->appDomain}/transactions/sales/{$transaction->id}/issue");

        $response->assertOk();
        $response->assertJsonPath('message', 'Faktur berhasil diterbitkan.');
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => TransactionStatus::Unpaid->value,
        ]);
    }

    public function test_it_records_payment_for_unpaid_transaction(): void
    {
        $transaction = Transaction::create([
            'outlet_id' => $this->outlet->id,
            'channel' => SalesChannelEnum::Wholesale->value,
            'transaction_number' => 'TRX/202609/0100',
            'transaction_date' => now(),
            'total' => 500000,
            'total_paid' => 0,
            'balance_due' => 500000,
            'status' => TransactionStatus::Unpaid->value,
            'payment_status' => TransactionPaymentStatus::Unpaid->value,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $transaction->invoice()->create([
            'invoice_number' => 'INV/202609/0100',
            'invoice_date' => now(),
            'payment_term' => PaymentTermEnum::Credit->value,
            'status' => TransactionStatus::Unpaid->value,
            'created_by' => $this->user->id,
        ]);

        $payload = [
            'payment_method_id' => $this->paymentMethod->id,
            'amount' => 500000,
            'payment_date' => now()->toDateTimeString(),
            'notes' => 'Pelunasan Transfer',
        ];

        $response = $this->actingAs($this->user, 'business')
            ->postJson("http://{$this->appDomain}/transactions/sales/{$transaction->id}/payment", $payload);

        $response->assertOk();
        $response->assertJsonPath('message', 'Pembayaran berhasil dicatat.');
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => TransactionStatus::Paid->value,
            'payment_status' => TransactionPaymentStatus::Paid->value,
            'total_paid' => 500000,
            'balance_due' => 0,
        ]);
    }

    public function test_it_updates_invoice_due_date(): void
    {
        $transaction = Transaction::create([
            'outlet_id' => $this->outlet->id,
            'channel' => SalesChannelEnum::Wholesale->value,
            'transaction_number' => 'TRX/202609/0101',
            'transaction_date' => now(),
            'total' => 300000,
            'balance_due' => 300000,
            'status' => TransactionStatus::Unpaid->value,
            'payment_status' => TransactionPaymentStatus::Unpaid->value,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $transaction->invoice()->create([
            'invoice_number' => 'INV/202609/0101',
            'invoice_date' => now(),
            'due_date' => now()->addDays(7)->toDateString(),
            'payment_term' => PaymentTermEnum::Credit->value,
            'status' => TransactionStatus::Unpaid->value,
            'created_by' => $this->user->id,
        ]);

        $newDueDate = now()->addDays(30)->toDateString();

        $response = $this->actingAs($this->user, 'business')
            ->putJson("http://{$this->appDomain}/transactions/sales/{$transaction->id}/due-date", [
                'due_date' => $newDueDate,
                'reason' => 'Perpanjangan kesepakatan tempo',
            ]);

        $response->assertOk();
        $response->assertJsonPath('message', 'Tanggal jatuh tempo berhasil diperbarui.');
        $this->assertEquals($newDueDate, $transaction->invoice->fresh()->due_date->format('Y-m-d'));
    }

    public function test_it_cancels_transaction(): void
    {
        $transaction = Transaction::create([
            'outlet_id' => $this->outlet->id,
            'channel' => SalesChannelEnum::Wholesale->value,
            'transaction_number' => 'TRX/202609/0102',
            'transaction_date' => now(),
            'total' => 200000,
            'balance_due' => 200000,
            'status' => TransactionStatus::Unpaid->value,
            'payment_status' => TransactionPaymentStatus::Unpaid->value,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $transaction->items()->create([
            'product_id' => $this->product->id,
            'product_name' => 'Kopi Robusta Super',
            'inventory_item_id' => $this->inventoryItem->id,
            'qty' => 2,
            'price' => 100000,
            'subtotal' => 200000,
            'unit_cogs' => 45000,
            'cogs_amount' => 90000,
        ]);

        $transaction->invoice()->create([
            'invoice_number' => 'INV/202609/0102',
            'invoice_date' => now(),
            'payment_term' => PaymentTermEnum::Credit->value,
            'status' => TransactionStatus::Unpaid->value,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->postJson("http://{$this->appDomain}/transactions/sales/{$transaction->id}/cancel", [
                'reason' => 'Pesanan dibatalkan pelanggan',
            ]);

        $response->assertOk();
        $this->assertDatabaseHas('transactions', [
            'id' => $transaction->id,
            'status' => TransactionStatus::Cancel->value,
        ]);
    }

    public function test_it_renders_inertia_index_page(): void
    {
        Transaction::create([
            'outlet_id' => $this->outlet->id,
            'channel' => SalesChannelEnum::Wholesale->value,
            'transaction_number' => 'TRX/202609/0200',
            'transaction_date' => now(),
            'total' => 150000,
            'balance_due' => 150000,
            'status' => TransactionStatus::Unpaid->value,
            'payment_status' => TransactionPaymentStatus::Unpaid->value,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/transactions/sales");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Transaction/Sales/Index')
            ->has('transactions.data', 1)
            ->has('filters')
            ->has('outlets')
        );
    }

    public function test_it_filters_sales_transactions_by_status_and_channel_and_search(): void
    {
        // 1. Transaction 1: wholesale, paid
        Transaction::create([
            'outlet_id' => $this->outlet->id,
            'customer_id' => $this->customer->id,
            'channel' => SalesChannelEnum::Wholesale->value,
            'transaction_number' => 'TRX/202609/MATCH',
            'transaction_date' => now(),
            'total' => 500000,
            'balance_due' => 0,
            'status' => TransactionStatus::Paid->value,
            'payment_status' => TransactionPaymentStatus::Paid->value,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        // 2. Transaction 2: direct, draft
        Transaction::create([
            'outlet_id' => $this->outlet->id,
            'channel' => SalesChannelEnum::Direct->value,
            'transaction_number' => 'TRX/202609/OTHER',
            'transaction_date' => now(),
            'total' => 200000,
            'balance_due' => 200000,
            'status' => TransactionStatus::Draft->value,
            'payment_status' => TransactionPaymentStatus::Draft->value,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        // Test filter by channel
        $response = $this->actingAs($this->user, 'business')
            ->getJson("http://{$this->appDomain}/transactions/sales?channel=wholesale");
        $response->assertOk();
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.transaction_number', 'TRX/202609/MATCH');

        // Test filter by status
        $response = $this->actingAs($this->user, 'business')
            ->getJson("http://{$this->appDomain}/transactions/sales?status=paid");
        $response->assertOk();
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.transaction_number', 'TRX/202609/MATCH');

        // Test filter by search
        $response = $this->actingAs($this->user, 'business')
            ->getJson("http://{$this->appDomain}/transactions/sales?search=MATCH");
        $response->assertOk();
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.transaction_number', 'TRX/202609/MATCH');

        // Test filter by search with customer name
        $response = $this->actingAs($this->user, 'business')
            ->getJson("http://{$this->appDomain}/transactions/sales?search=Maju");
        $response->assertOk();
        $response->assertJsonPath('total', 1);
        $response->assertJsonPath('data.0.transaction_number', 'TRX/202609/MATCH');
    }

    public function test_it_resolves_date_presets_in_index(): void
    {
        // Past transaction
        Transaction::create([
            'outlet_id' => $this->outlet->id,
            'channel' => SalesChannelEnum::Wholesale->value,
            'transaction_number' => 'TRX/PAST/001',
            'transaction_date' => now()->subMonths(2),
            'total' => 100000,
            'balance_due' => 100000,
            'status' => TransactionStatus::Unpaid->value,
            'payment_status' => TransactionPaymentStatus::Unpaid->value,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        // Current transaction
        Transaction::create([
            'outlet_id' => $this->outlet->id,
            'channel' => SalesChannelEnum::Wholesale->value,
            'transaction_number' => 'TRX/CURRENT/001',
            'transaction_date' => now(),
            'total' => 100000,
            'balance_due' => 100000,
            'status' => TransactionStatus::Unpaid->value,
            'payment_status' => TransactionPaymentStatus::Unpaid->value,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/transactions/sales?preset=this_month");

        $response->assertOk();
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Transaction/Sales/Index')
            ->has('transactions.data', 1)
            ->where('transactions.data.0.transaction_number', 'TRX/CURRENT/001')
            ->where('filters.preset', 'this_month')
        );
    }

    public function test_it_returns_json_index_when_wants_json(): void
    {
        Transaction::create([
            'outlet_id' => $this->outlet->id,
            'channel' => SalesChannelEnum::Wholesale->value,
            'transaction_number' => 'TRX/202609/0201',
            'transaction_date' => now(),
            'total' => 150000,
            'balance_due' => 150000,
            'status' => TransactionStatus::Unpaid->value,
            'payment_status' => TransactionPaymentStatus::Unpaid->value,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->getJson("http://{$this->appDomain}/transactions/sales");

        $response->assertOk();
        $response->assertJsonPath('total', 1);
        $response->assertJsonStructure([
            'data' => [
                '*' => ['id', 'transaction_number', 'total', 'customer', 'outlet'],
            ],
        ]);
    }

    public function test_it_shows_transaction_details_json(): void
    {
        $transaction = Transaction::create([
            'outlet_id' => $this->outlet->id,
            'channel' => SalesChannelEnum::Wholesale->value,
            'transaction_number' => 'TRX/202609/0202',
            'transaction_date' => now(),
            'total' => 150000,
            'balance_due' => 150000,
            'status' => TransactionStatus::Unpaid->value,
            'payment_status' => TransactionPaymentStatus::Unpaid->value,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->getJson("http://{$this->appDomain}/transactions/sales/{$transaction->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', $transaction->id);
    }

    public function test_channel_validation_strictly_allows_only_wholesale_and_direct(): void
    {
        $payload = [
            'outlet_id' => $this->outlet->id,
            'channel' => 'dine_in', // Invalid channel for B2B V1
            'transaction_date' => now()->toDateTimeString(),
            'payment_term' => PaymentTermEnum::Cash->value,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'qty' => 1,
                    'price' => 50000,
                ],
            ],
        ];

        $response = $this->actingAs($this->user, 'business')
            ->postJson("http://{$this->appDomain}/transactions/sales", $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['channel']);
    }

    public function test_it_creates_transaction_with_service_item_without_stock_movement(): void
    {
        $payload = [
            'outlet_id' => $this->outlet->id,
            'channel' => SalesChannelEnum::Direct->value,
            'transaction_date' => now()->toDateTimeString(),
            'payment_term' => PaymentTermEnum::Cash->value,
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_item_id' => null,
                    'inventory_item_id' => null, // Service / Jasa
                    'qty' => 1,
                    'price' => 150000,
                ],
            ],
            'issue_now' => true,
        ];

        $response = $this->actingAs($this->user, 'business')
            ->postJson("http://{$this->appDomain}/transactions/sales", $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('transactions', [
            'outlet_id' => $this->outlet->id,
            'status' => TransactionStatus::Unpaid->value,
        ]);
    }

    public function test_tenant_isolation_prevents_access_to_other_business_transactions(): void
    {
        $otherBusiness = Business::create([
            'name' => 'Other Merchant',
            'owner_name' => 'Other Owner',
            'email' => 'other_'.uniqid().'@test.com',
            'phone' => '081299999',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $this->business->business_type_id,
        ]);

        $otherOutlet = Outlet::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Other Outlet',
        ]);

        $otherTransaction = Transaction::create([
            'outlet_id' => $otherOutlet->id,
            'channel' => SalesChannelEnum::Wholesale->value,
            'transaction_number' => 'TRX/OTHER/0001',
            'transaction_date' => now(),
            'total' => 100000,
            'balance_due' => 100000,
            'status' => TransactionStatus::Unpaid->value,
            'payment_status' => TransactionPaymentStatus::Unpaid->value,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->getJson("http://{$this->appDomain}/transactions/sales/{$otherTransaction->id}");

        $response->assertStatus(403);
    }

    public function test_it_downloads_invoice_pdf(): void
    {
        $transaction = Transaction::create([
            'outlet_id' => $this->outlet->id,
            'channel' => SalesChannelEnum::Wholesale->value,
            'transaction_number' => 'TRX/202609/0300',
            'transaction_date' => now(),
            'total' => 200000,
            'balance_due' => 200000,
            'status' => TransactionStatus::Unpaid->value,
            'payment_status' => TransactionPaymentStatus::Unpaid->value,
            'created_by' => $this->user->id,
            'updated_by' => $this->user->id,
        ]);

        $transaction->invoice()->create([
            'invoice_number' => 'INV/202609/0300',
            'invoice_date' => now(),
            'payment_term' => PaymentTermEnum::Credit->value,
            'status' => TransactionStatus::Unpaid->value,
            'created_by' => $this->user->id,
        ]);

        $transaction->items()->create([
            'product_id' => $this->product->id,
            'product_name' => 'Kopi Robusta',
            'qty' => 2,
            'price' => 100000,
            'subtotal' => 200000,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/transactions/sales/{$transaction->id}/pdf");

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_it_stores_transaction_with_tax_amount_and_promo_code(): void
    {
        $payload = [
            'outlet_id' => $this->outlet->id,
            'channel' => SalesChannelEnum::Wholesale->value,
            'transaction_date' => now()->toDateTimeString(),
            'payment_term' => PaymentTermEnum::Credit->value,
            'customer_id' => $this->customer->id,
            'due_date' => now()->addDays(14)->toDateString(),
            'notes' => 'Catatan Faktur Pajak & Promo',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_item_id' => $this->productItem->id,
                    'inventory_item_id' => $this->inventoryItem->id,
                    'qty' => 2,
                    'price' => 100000,
                    'discount_amount' => 0,
                ],
            ],
            'discount_type' => 'manual',
            'discount_value' => 20000,
            'tax_amount' => 19800, // 11% of 180,000
            'promo_code' => 'TESTCODE',
            'shipping_fee' => 10000,
            'issue_now' => false,
        ];

        $response = $this->actingAs($this->user, 'business')
            ->postJson("http://{$this->appDomain}/transactions/sales", $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('transactions', [
            'outlet_id' => $this->outlet->id,
            'tax_amount' => 19800,
            'discount_amount' => 20000,
            'total' => 209800, // (200,000 - 20,000) + 19,800 + 10,000 = 209,800
        ]);
    }

    public function test_it_returns_outlet_sales_settings_with_fallback_14_days(): void
    {
        $response = $this->actingAs($this->user, 'business')
            ->getJson("http://{$this->appDomain}/api/internal/outlets/sales-settings?outlet_id={$this->outlet->id}");

        $response->assertOk();
        $response->assertJsonPath('data.default_due_days_invoice', 14);
        $this->assertNotEmpty($response->json('data.default_terms_and_conditions_invoice'));
    }
}
