<?php

declare(strict_types=1);

namespace Tests\Feature\API;

use App\Enums\DeviceTypeEnum;
use App\Enums\FeatureEnum;
use App\Enums\InventoryCostingMethod;
use App\Enums\InventoryMovementType;
use App\Enums\PermissionEnum;
use App\Enums\PlanEnum;
use App\Enums\ProductTypeEnum;
use App\Enums\SalesChannelEnum;
use App\Enums\SubscriptionStatus;
use App\Enums\TransactionPaymentStatus;
use App\Enums\TransactionStatus;
use App\Enums\TransactionTypeEnum;
use App\Models\Audit\ActivityLog;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryCostLayer;
use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryMovement;
use App\Models\Master\ModifierGroup;
use App\Models\Master\ModifierOption;
use App\Models\Master\ModifierRecipeItem;
use App\Models\Master\PaymentMethod;
use App\Models\Master\Product;
use App\Models\Master\ProductItem;
use App\Models\Master\ProductRecipeItem;
use App\Models\Master\RecipeVersion;
use App\Models\Outlet;
use App\Models\OutletDevice;
use App\Models\OutletSetting;
use App\Models\Sales\Shift;
use App\Models\Sales\Transaction;
use App\Models\Sales\TransactionItem;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Uom;
use App\Models\User;
use App\Services\Pos\PosDeviceAuthCacheService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PosEmpiricalAdversarialChallengeTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;

    protected Outlet $outlet;

    protected OutletDevice $device;

    protected User $cashier;

    protected User $supervisor;

    protected Shift $shift;

    protected PaymentMethod $cashMethod;

    protected PaymentMethod $qrisMethod;

    protected PaymentMethod $debitMethod;

    protected Uom $uomPcs;

    protected Uom $uomGram;

    protected Uom $uomMl;

    protected PosDeviceAuthCacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->cacheService = app(PosDeviceAuthCacheService::class);

        $type = BusinessType::firstOrCreate(
            ['code' => 'food_beverage'],
            [
                'name' => 'F&B Retail',
                'sort_order' => 1,
                'is_visible' => true,
                'features' => array_column(FeatureEnum::cases(), 'value'),
            ]
        );

        $this->business = Business::create([
            'name' => 'Adversarial Test Enterprise',
            'owner_name' => 'Security Auditor',
            'email' => 'adv_enterprise_'.uniqid().'@test.test',
            'phone' => '081234567899',
            'status' => 'active',
            'trial_end_at' => now()->addDays(30),
            'business_type_id' => $type->id,
            'settings' => [
                'active_features' => array_column(FeatureEnum::cases(), 'value'),
                'costing_method' => InventoryCostingMethod::FIFO->value,
            ],
        ]);

        $proPlan = SubscriptionPlan::where('code', PlanEnum::PRO->value)->first();
        if ($proPlan) {
            Subscription::create([
                'business_id' => $this->business->id,
                'plan_id' => $proPlan->id,
                'status' => SubscriptionStatus::Active,
                'billing_cycle' => 'monthly',
                'started_at' => now()->subDay(),
                'expired_at' => now()->addMonth(),
            ]);
        }
        $this->business->clearMemoizedFeatures();

        $this->outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Flagship POS',
            'address' => 'Jl. Sudirman 100',
            'phone' => '081299998888',
            'is_active' => true,
        ]);

        OutletSetting::create([
            'outlet_id' => $this->outlet->id,
            'category' => 'pos',
            'key' => 'enable_supervisor_pin',
            'value' => 'true',
        ]);

        $this->device = OutletDevice::create([
            'outlet_id' => $this->outlet->id,
            'device_name' => 'POS Terminal Alpha',
            'device_type' => DeviceTypeEnum::POS_TERMINAL->value,
            'client_device_uuid' => 'pos-dev-alpha',
            'hardware_fingerprint' => 'pos-sig-alpha',
            'is_active' => true,
        ]);
        $this->cacheService->putDevice($this->device);

        setPermissionsTeamId($this->business->id);

        $this->cashier = User::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'name' => 'Kasir Alpha',
            'email' => 'cashier_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
            'pin' => Hash::make('123123'),
        ]);
        $this->outlet->users()->attach($this->cashier->id);
        $this->cashier->givePermissionTo(PermissionEnum::TRANSACTION_CREATE->value);
        $this->cashier->givePermissionTo(PermissionEnum::TRANSACTION_VIEW->value);

        $this->supervisor = User::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'name' => 'Supervisor Alpha',
            'email' => 'supervisor_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
            'pin' => Hash::make('987654'),
        ]);
        $this->outlet->users()->attach($this->supervisor->id);
        $this->supervisor->givePermissionTo(PermissionEnum::TRANSACTION_CREATE->value);
        $this->supervisor->givePermissionTo(PermissionEnum::TRANSACTION_VIEW->value);
        $this->supervisor->givePermissionTo(PermissionEnum::TRANSACTION_VALIDATION_SUPERVISION->value);

        $this->shift = Shift::create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->cashier->id,
            'shift_number' => 'SH-ADV-01',
            'start_time' => now()->subHours(3),
            'opening_balance' => 500000,
            'status' => 'open',
        ]);

        $this->cashMethod = PaymentMethod::create([
            'business_id' => $this->business->id,
            'name' => 'Tunai Cash',
            'type' => 'cash',
            'is_active' => true,
        ]);
        $this->cashMethod->outlets()->attach($this->outlet->id);

        $this->qrisMethod = PaymentMethod::create([
            'business_id' => $this->business->id,
            'name' => 'QRIS Dinamis',
            'type' => 'digital',
            'is_active' => true,
        ]);
        $this->qrisMethod->outlets()->attach($this->outlet->id);

        $this->debitMethod = PaymentMethod::create([
            'business_id' => $this->business->id,
            'name' => 'EDC Debit Mandiri',
            'type' => 'card',
            'is_active' => true,
        ]);
        $this->debitMethod->outlets()->attach($this->outlet->id);

        $this->uomPcs = Uom::whereRaw('LOWER(code) = ?', ['pcs'])->first() ?? Uom::first() ?? Uom::create(['code' => 'Pcs', 'name' => 'Pieces']);
        $this->uomGram = Uom::whereRaw('LOWER(code) = ?', ['g'])->first() ?? Uom::create(['code' => 'g', 'name' => 'Gram']);
        $this->uomMl = Uom::whereRaw('LOWER(code) = ?', ['ml'])->first() ?? Uom::create(['code' => 'ml', 'name' => 'Mililiter']);
    }

    /**
     * TIER 5 - Challenge 1: Anti-DLQ Force-Accept Negative Stock & Cascade Variance Auditing
     */
    public function test_adversarial_anti_dlq_negative_stock_cascade_and_audit_logging(): void
    {
        Sanctum::actingAs($this->device, ['pos:access']);

        // Siapkan item dengan stok tepat 0.0
        $invItem = InventoryItem::create([
            'business_id' => $this->business->id,
            'name' => 'Croissant Coklat Defisit',
            'sku' => 'CRO-001',
            'uom_id' => $this->uomPcs->id,
            'cost_method' => 'fifo',
            'average_cost' => 15000,
            'last_cost' => 15000,
            'is_active' => true,
        ]);

        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Croissant Coklat Defisit',
            'code' => 'CRO-001',
            'product_type' => ProductTypeEnum::BASIC->value,
            'track_inventory' => true,
            'is_show' => true,
            'sellable' => true,
        ]);
        $product->outlets()->attach($this->outlet->id, ['is_enabled' => true, 'is_available' => true]);

        InventoryBalance::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $invItem->id,
            'current_stock' => 0.0,
            'allocated_stock' => 0.0,
            'total_value' => 0.0,
        ]);

        // 1. Transaksi Offline 1: Permintaan 5 pcs saat stok 0
        $txId1 = (string) Str::uuid();
        $payload1 = [
            'offline_id' => $txId1,
            'transaction_number' => 'POS/OFFLINE/001',
            'is_offline' => true,
            'shift_id' => $this->shift->id,
            'cashier_id' => $this->cashier->id,
            'subtotal' => 100000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'service_charge_amount' => 0,
            'total' => 100000,
            'payment_status' => 'paid',
            'status' => 'completed',
            'items' => [
                [
                    'product_id' => $product->id,
                    'inventory_item_id' => $invItem->id,
                    'track_inventory' => true,
                    'product_name' => 'Croissant Coklat Defisit',
                    'price' => 20000,
                    'qty' => 5,
                    'discount_amount' => 0,
                    'subtotal' => 100000,
                ],
            ],
            'payments' => [
                [
                    'payment_method_id' => $this->cashMethod->id,
                    'amount' => 100000,
                    'change_amount' => 0,
                ],
            ],
        ];

        $response1 = $this->withHeaders([
            'X-DEVICE-UUID' => 'pos-dev-alpha',
            'X-HARDWARE-SIGNATURE' => 'pos-sig-alpha',
        ])->postJson('http://api.sollu.test/pos/transactions', $payload1);

        $response1->assertStatus(200);

        // Verifikasi saldo menurun ke -5.0
        $balance1 = InventoryBalance::where('outlet_id', $this->outlet->id)
            ->where('inventory_item_id', $invItem->id)
            ->first();
        $this->assertEquals(-5.0, (float) $balance1->current_stock);

        // Verifikasi audit log variance tercatat
        $auditLog1 = ActivityLog::where('outlet_id', $this->outlet->id)
            ->where('action', 'offline_sale_negative_stock')
            ->where('subject_id', $invItem->id)
            ->latest()
            ->first();

        $this->assertNotNull($auditLog1, 'Audit log offline_sale_negative_stock must be created');
        $this->assertEquals(-5.0, (float) $auditLog1->properties['variance']);
        $this->assertEquals(0.0, (float) $auditLog1->properties['stock_before']);
        $this->assertEquals(-5.0, (float) $auditLog1->properties['stock_after']);

        // 2. Transaksi Offline 2: Penjualan cascading berikutnya sebanyak 3 pcs
        $txId2 = (string) Str::uuid();
        $payload2 = [
            'offline_id' => $txId2,
            'transaction_number' => 'POS/OFFLINE/002',
            'is_offline' => true,
            'shift_id' => $this->shift->id,
            'cashier_id' => $this->cashier->id,
            'subtotal' => 60000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'service_charge_amount' => 0,
            'total' => 60000,
            'payment_status' => 'paid',
            'status' => 'completed',
            'items' => [
                [
                    'product_id' => $product->id,
                    'inventory_item_id' => $invItem->id,
                    'track_inventory' => true,
                    'product_name' => 'Croissant Coklat Defisit',
                    'price' => 20000,
                    'qty' => 3,
                    'discount_amount' => 0,
                    'subtotal' => 60000,
                ],
            ],
            'payments' => [
                [
                    'payment_method_id' => $this->cashMethod->id,
                    'amount' => 60000,
                    'change_amount' => 0,
                ],
            ],
        ];

        $response2 = $this->withHeaders([
            'X-DEVICE-UUID' => 'pos-dev-alpha',
            'X-HARDWARE-SIGNATURE' => 'pos-sig-alpha',
        ])->postJson('http://api.sollu.test/pos/transactions', $payload2);

        $response2->assertStatus(200);

        // Verifikasi saldo menurun lagi menjadi -8.0
        $balance2 = InventoryBalance::where('outlet_id', $this->outlet->id)
            ->where('inventory_item_id', $invItem->id)
            ->first();
        $this->assertEquals(-8.0, (float) $balance2->current_stock);

        // Verifikasi audit log kedua
        $auditLogs = ActivityLog::where('outlet_id', $this->outlet->id)
            ->where('action', 'offline_sale_negative_stock')
            ->where('subject_id', $invItem->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $this->assertCount(2, $auditLogs);
        $firstLog = $auditLogs->first(fn ($l) => (float) $l->properties['stock_before'] === 0.0);
        $secondLog = $auditLogs->first(fn ($l) => (float) $l->properties['stock_before'] === -5.0);

        $this->assertNotNull($firstLog);
        $this->assertEquals(-5.0, (float) $firstLog->properties['variance']);
        $this->assertEquals(0.0, (float) $firstLog->properties['stock_before']);
        $this->assertEquals(-5.0, (float) $firstLog->properties['stock_after']);

        $this->assertNotNull($secondLog);
        $this->assertEquals(-8.0, (float) $secondLog->properties['variance']);
        $this->assertEquals(-5.0, (float) $secondLog->properties['stock_before']);
        $this->assertEquals(-8.0, (float) $secondLog->properties['stock_after']);

        // 3. Kontras Adversarial: Transaksi Online murni (bukan offline) harus tetap dicegat
        $onlinePayload = [
            // tanpa offline_id
            'is_offline' => false,
            'shift_id' => $this->shift->id,
            'cashier_id' => $this->cashier->id,
            'subtotal' => 20000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'service_charge_amount' => 0,
            'total' => 20000,
            'payment_status' => 'paid',
            'status' => 'completed',
            'items' => [
                [
                    'product_id' => $product->id,
                    'inventory_item_id' => $invItem->id,
                    'track_inventory' => true,
                    'product_name' => 'Croissant Coklat Defisit',
                    'price' => 20000,
                    'qty' => 1,
                    'discount_amount' => 0,
                    'subtotal' => 20000,
                ],
            ],
            'payments' => [
                [
                    'payment_method_id' => $this->cashMethod->id,
                    'amount' => 20000,
                    'change_amount' => 0,
                ],
            ],
        ];

        // Harus melempar exception atau HTTP error karena stok negatif dan bukan transaksi offline
        try {
            $onlineResponse = $this->withHeaders([
                'X-DEVICE-UUID' => 'pos-dev-alpha',
                'X-HARDWARE-SIGNATURE' => 'pos-sig-alpha',
            ])->postJson('http://api.sollu.test/pos/transactions', $onlinePayload);

            $this->assertContains($onlineResponse->status(), [400, 422, 500]);
        } catch (\InvalidArgumentException $e) {
            $this->assertStringContainsString('tidak mencukupi', $e->getMessage());
        }
    }

    /**
     * TIER 5 - Challenge 2: Batch Sync In-Memory Preloading, Replay Resistance & Atomicity
     */
    public function test_adversarial_batch_sync_atomicity_and_replay_idempotency(): void
    {
        Sanctum::actingAs($this->device, ['pos:access']);

        $invItem = InventoryItem::create([
            'business_id' => $this->business->id,
            'name' => 'Biji Kopi Gayo 500g',
            'sku' => 'GYO-001',
            'uom_id' => $this->uomPcs->id,
            'cost_method' => 'fifo',
            'average_cost' => 50000,
            'last_cost' => 50000,
            'is_active' => true,
        ]);

        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Biji Kopi Gayo 500g',
            'code' => 'GYO-001',
            'product_type' => ProductTypeEnum::BASIC->value,
            'track_inventory' => true,
            'is_show' => true,
            'sellable' => true,
        ]);
        $product->outlets()->attach($this->outlet->id, ['is_enabled' => true, 'is_available' => true]);

        InventoryBalance::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $invItem->id,
            'current_stock' => 100.0,
            'allocated_stock' => 0.0,
            'total_value' => 5000000,
        ]);

        InventoryCostLayer::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $invItem->id,
            'purchase_price' => 50000,
            'qty_purchased' => 100.0,
            'qty_remaining' => 100.0,
        ]);

        $offlineId1 = (string) Str::uuid();
        $offlineId2 = (string) Str::uuid();

        // Batch berisi 3 item transaksi: Tx 1, Tx 2 (duplikat persis dari Tx 1 dalam batch yang sama), dan Tx 3
        $batchPayload = [
            'transactions' => [
                [
                    'offline_id' => $offlineId1,
                    'transaction_number' => 'POS/BATCH/001',
                    'shift_id' => $this->shift->id,
                    'cashier_id' => $this->cashier->id,
                    'subtotal' => 75000,
                    'total' => 75000,
                    'payment_status' => 'paid',
                    'status' => 'completed',
                    'items' => [
                        [
                            'product_id' => $product->id,
                            'inventory_item_id' => $invItem->id,
                            'track_inventory' => true,
                            'product_name' => 'Biji Kopi Gayo 500g',
                            'price' => 75000,
                            'qty' => 1,
                            'subtotal' => 75000,
                        ],
                    ],
                    'payments' => [
                        [
                            'payment_method_id' => $this->cashMethod->id,
                            'amount' => 75000,
                            'change_amount' => 0,
                        ],
                    ],
                ],
                // Duplikat intra-batch Tx 1: Harus diabaikan secara anggun tanpa double deduction
                [
                    'offline_id' => $offlineId1,
                    'transaction_number' => 'POS/BATCH/001',
                    'shift_id' => $this->shift->id,
                    'cashier_id' => $this->cashier->id,
                    'subtotal' => 75000,
                    'total' => 75000,
                    'payment_status' => 'paid',
                    'status' => 'completed',
                    'items' => [
                        [
                            'product_id' => $product->id,
                            'inventory_item_id' => $invItem->id,
                            'track_inventory' => true,
                            'product_name' => 'Biji Kopi Gayo 500g',
                            'price' => 75000,
                            'qty' => 1,
                            'subtotal' => 75000,
                        ],
                    ],
                    'payments' => [
                        [
                            'payment_method_id' => $this->cashMethod->id,
                            'amount' => 75000,
                            'change_amount' => 0,
                        ],
                    ],
                ],
                // Tx 3: Transaksi unik kedua
                [
                    'offline_id' => $offlineId2,
                    'transaction_number' => 'POS/BATCH/002',
                    'shift_id' => $this->shift->id,
                    'cashier_id' => $this->cashier->id,
                    'subtotal' => 150000,
                    'total' => 150000,
                    'payment_status' => 'paid',
                    'status' => 'completed',
                    'items' => [
                        [
                            'product_id' => $product->id,
                            'inventory_item_id' => $invItem->id,
                            'track_inventory' => true,
                            'product_name' => 'Biji Kopi Gayo 500g',
                            'price' => 75000,
                            'qty' => 2,
                            'subtotal' => 150000,
                        ],
                    ],
                    'payments' => [
                        [
                            'payment_method_id' => $this->cashMethod->id,
                            'amount' => 150000,
                            'change_amount' => 0,
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->withHeaders([
            'X-DEVICE-UUID' => 'pos-dev-alpha',
            'X-HARDWARE-SIGNATURE' => 'pos-sig-alpha',
        ])->postJson('http://api.sollu.test/pos/transactions/batch', $batchPayload);

        $response->assertStatus(200);

        // Transaksi di DB harus tepat 2 (bukan 3)
        $txCount = Transaction::whereIn('id', [$offlineId1, $offlineId2])->count();
        $this->assertEquals(2, $txCount);

        // Stok awal 100, Tx 1 potong 1, Tx 3 potong 2 => Total terpotong 3, sisa 97.0
        $balance = InventoryBalance::where('outlet_id', $this->outlet->id)
            ->where('inventory_item_id', $invItem->id)
            ->first();
        $this->assertEquals(97.0, (float) $balance->current_stock);

        // 4. Replay Attack: Kirim ulang batch yang sama persis
        // Menguji idempotensi dan mendeteksi bug Eloquent Collection merge() pada TransactionService.php:986
        $replayResponse = $this->withHeaders([
            'X-DEVICE-UUID' => 'pos-dev-alpha',
            'X-HARDWARE-SIGNATURE' => 'pos-sig-alpha',
        ])->postJson('http://api.sollu.test/pos/transactions/batch', $batchPayload);

        // Terbukti replay batch berhasil secara idempoten (HTTP 200) tanpa melempar UniqueConstraintViolationException
        $this->assertEquals(200, $replayResponse->status());
        $this->assertCount(2, $replayResponse->json('data'));

        // Transaksi di DB tetap tepat 2 (tidak ada duplikasi)
        $txCountAfterReplay = Transaction::whereIn('id', [$offlineId1, $offlineId2])->count();
        $this->assertEquals(2, $txCountAfterReplay);

        // Saldo stok tetap 97.0 (tidak terpotong ganda)
        $balanceAfterReplay = InventoryBalance::where('outlet_id', $this->outlet->id)
            ->where('inventory_item_id', $invItem->id)
            ->first();
        $this->assertEquals(97.0, (float) $balanceAfterReplay->current_stock);
    }

    /**
     * TIER 5 - Proof of Root Cause: Eloquent Collection merge() stripping dictionary keys
     */
    public function test_adversarial_eloquent_collection_merge_wipes_keyby_keys_proof(): void
    {
        $tx1 = Transaction::create([
            'outlet_id' => $this->outlet->id,
            'channel' => SalesChannelEnum::POS,
            'type' => TransactionTypeEnum::WalkIn,
            'transaction_number' => 'POS/PROOF/'.uniqid(),
            'transaction_date' => now(),
            'subtotal' => 10000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'service_charge_amount' => 0,
            'total' => 10000,
            'total_paid' => 10000,
            'balance_due' => 0,
            'payment_status' => 'paid',
            'status' => 'completed',
            'created_by' => $this->cashier->id,
            'updated_by' => $this->cashier->id,
        ]);

        $existingQuery = Transaction::where('outlet_id', $this->outlet->id)->where('id', $tx1->id)->get();
        $this->assertInstanceOf(Collection::class, $existingQuery);

        // Bukti akar masalah di TransactionService.php:986-987:
        $keyedById = $existingQuery->keyBy('id');
        $this->assertTrue($keyedById->has($tx1->id));

        $keyedByTxNum = $existingQuery->keyBy('transaction_number');
        $this->assertTrue($keyedByTxNum->has($tx1->transaction_number));

        // Saat merge() dipanggil pada Eloquent Collection:
        $merged = $keyedById->merge($keyedByTxNum);

        // Eloquent\Collection::merge() memanggil array_values(), sehingga key string terhapus menjadi integer index 0!
        $this->assertEquals([0], $merged->keys()->all());
        $this->assertFalse($merged->has($tx1->id), 'Eloquent Collection merge stripped the UUID key');
        $this->assertFalse($merged->has($tx1->transaction_number), 'Eloquent Collection merge stripped the transaction_number key');
    }

    /**
     * TIER 5 - Challenge 3: Recipe BOM & Modifier Options Multi-Ingredient Deduction & COGS Accuracy
     */
    public function test_adversarial_recipe_bom_and_modifier_ingredient_deductions_and_costing(): void
    {
        Sanctum::actingAs($this->device, ['pos:access']);

        // 1. Bahan Baku Resep: Biji Kopi (Gram), Susu Segar (ml), Sirup Karamel (ml)
        $beansInv = InventoryItem::create([
            'business_id' => $this->business->id,
            'name' => 'Biji Kopi House Blend',
            'sku' => 'RAW-BEAN-'.uniqid(),
            'uom_id' => $this->uomGram->id,
            'cost_method' => 'fifo',
            'average_cost' => 200, // Rp 200 / gram
            'last_cost' => 200,
            'is_active' => true,
        ]);
        $beansProductItem = ProductItem::create([
            'business_id' => $this->business->id,
            'item_type' => 'raw_material',
            'product_id' => null,
            'name' => 'Biji Kopi House Blend',
            'sku' => 'RAW-BEAN-'.uniqid(),
            'buy_price' => 200,
            'track_inventory' => true,
        ]);
        $beansInv->update(['product_item_id' => $beansProductItem->id]);

        InventoryBalance::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $beansInv->id,
            'current_stock' => 500.0,
            'allocated_stock' => 0.0,
            'total_value' => 100000,
            'average_cost' => 200,
        ]);
        InventoryCostLayer::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $beansInv->id,
            'purchase_price' => 200,
            'qty_purchased' => 500.0,
            'qty_remaining' => 500.0,
        ]);

        $milkInv = InventoryItem::create([
            'business_id' => $this->business->id,
            'name' => 'Susu Segar Pasteurisasi',
            'sku' => 'RAW-MILK-'.uniqid(),
            'uom_id' => $this->uomMl->id,
            'cost_method' => 'fifo',
            'average_cost' => 20, // Rp 20 / ml
            'last_cost' => 20,
            'is_active' => true,
        ]);
        $milkProductItem = ProductItem::create([
            'business_id' => $this->business->id,
            'item_type' => 'raw_material',
            'product_id' => null,
            'name' => 'Susu Segar Pasteurisasi',
            'sku' => 'RAW-MILK-'.uniqid(),
            'buy_price' => 20,
            'track_inventory' => true,
        ]);
        $milkInv->update(['product_item_id' => $milkProductItem->id]);

        InventoryBalance::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $milkInv->id,
            'current_stock' => 2000.0,
            'allocated_stock' => 0.0,
            'total_value' => 40000,
            'average_cost' => 20,
        ]);
        InventoryCostLayer::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $milkInv->id,
            'purchase_price' => 20,
            'qty_purchased' => 2000.0,
            'qty_remaining' => 2000.0,
        ]);

        $syrupInv = InventoryItem::create([
            'business_id' => $this->business->id,
            'name' => 'Sirup Karamel Premium',
            'sku' => 'RAW-SYRUP-'.uniqid(),
            'uom_id' => $this->uomMl->id,
            'cost_method' => 'fifo',
            'average_cost' => 50, // Rp 50 / ml
            'last_cost' => 50,
            'is_active' => true,
        ]);
        $syrupProductItem = ProductItem::create([
            'business_id' => $this->business->id,
            'item_type' => 'raw_material',
            'product_id' => null,
            'name' => 'Sirup Karamel Premium',
            'sku' => 'RAW-SYRUP-'.uniqid(),
            'buy_price' => 50,
            'track_inventory' => true,
        ]);
        $syrupInv->update(['product_item_id' => $syrupProductItem->id]);

        InventoryBalance::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $syrupInv->id,
            'current_stock' => 500.0,
            'allocated_stock' => 0.0,
            'total_value' => 25000,
            'average_cost' => 50,
        ]);
        InventoryCostLayer::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $syrupInv->id,
            'purchase_price' => 50,
            'qty_purchased' => 500.0,
            'qty_remaining' => 500.0,
        ]);

        // 2. Produk Jadi F&B: "Caramel Macchiato" dengan Recipe Version
        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Caramel Macchiato',
            'code' => 'CM-001',
            'product_type' => ProductTypeEnum::BASIC->value,
            'track_inventory' => true,
            'is_show' => true,
            'sellable' => true,
        ]);
        $product->outlets()->attach($this->outlet->id, ['is_enabled' => true, 'is_available' => true]);

        // Recipe BOM: 18g Biji Kopi + 200ml Susu
        $recipe = RecipeVersion::create([
            'product_id' => $product->id,
            'version_number' => 1,
            'is_active' => true,
            'effective_from' => now()->subDay(),
        ]);
        ProductRecipeItem::create([
            'recipe_version_id' => $recipe->id,
            'product_item_id' => $beansProductItem->id,
            'qty' => 18.0,
            'uom' => 'g',
        ]);
        ProductRecipeItem::create([
            'recipe_version_id' => $recipe->id,
            'product_item_id' => $milkProductItem->id,
            'qty' => 200.0,
            'uom' => 'ml',
        ]);

        // 3. Modifiers dengan Recipe BOM masing-masing:
        // - "Extra Shot": 18g Biji Kopi
        // - "Extra Caramel": 30ml Sirup Karamel
        $modGroup = ModifierGroup::create([
            'business_id' => $this->business->id,
            'name' => 'Add-ons Minuman',
            'selection_type' => 'multi',
            'max_select' => 5,
            'is_required' => false,
        ]);

        $modExtraShot = ModifierOption::create([
            'modifier_group_id' => $modGroup->id,
            'name' => 'Extra Shot Espresso',
            'additional_price' => 5000,
        ]);
        ModifierRecipeItem::create([
            'modifier_option_id' => $modExtraShot->id,
            'product_item_id' => $beansProductItem->id,
            'qty' => 18.0,
            'uom' => 'g',
        ]);

        $modExtraCaramel = ModifierOption::create([
            'modifier_group_id' => $modGroup->id,
            'name' => 'Extra Caramel Syrup',
            'additional_price' => 4000,
        ]);
        ModifierRecipeItem::create([
            'modifier_option_id' => $modExtraCaramel->id,
            'product_item_id' => $syrupProductItem->id,
            'qty' => 30.0,
            'uom' => 'ml',
        ]);

        // 4. Jalankan Transaksi POS Offline: 2 Cup Caramel Macchiato dengan Extra Shot (qty 1) dan Extra Caramel (qty 1)
        $txId = (string) Str::uuid();
        $modifiersPayload = [
            [
                'modifier_option_id' => $modExtraShot->id,
                'modifier_name' => 'Extra Shot Espresso',
                'price' => 5000,
                'qty' => 1,
            ],
            [
                'modifier_option_id' => $modExtraCaramel->id,
                'modifier_name' => 'Extra Caramel Syrup',
                'price' => 4000,
                'qty' => 1,
            ],
        ];

        // Harga per cup: 35.000 + 5.000 + 4.000 = 44.000. Untuk 2 cup = 88.000.
        $payload = [
            'offline_id' => $txId,
            'transaction_number' => 'POS/RECIPE/001',
            'shift_id' => $this->shift->id,
            'cashier_id' => $this->cashier->id,
            'subtotal' => 88000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'service_charge_amount' => 0,
            'total' => 88000,
            'payment_status' => 'paid',
            'status' => 'completed',
            'items' => [
                [
                    'product_id' => $product->id,
                    'track_inventory' => true,
                    'product_name' => 'Caramel Macchiato',
                    'price' => 35000,
                    'qty' => 2,
                    'discount_amount' => 0,
                    'subtotal' => 88000,
                    'modifiers' => $modifiersPayload,
                ],
            ],
            'payments' => [
                [
                    'payment_method_id' => $this->cashMethod->id,
                    'amount' => 88000,
                    'change_amount' => 0,
                ],
            ],
        ];

        $response = $this->withHeaders([
            'X-DEVICE-UUID' => 'pos-dev-alpha',
            'X-HARDWARE-SIGNATURE' => 'pos-sig-alpha',
        ])->postJson('http://api.sollu.test/pos/transactions', $payload);

        $response->assertStatus(200);

        // 5. Verifikasi Deduksi Stok Bahan Baku
        // Biji Kopi: Resep (18g * 2 cup) + Modifier Extra Shot (18g * 2 cup * 1) = 36 + 36 = 72g terpotong
        // Sisa Kopi: 500 - 72 = 428g
        $beansBalance = InventoryBalance::where('outlet_id', $this->outlet->id)->where('inventory_item_id', $beansInv->id)->first();
        $this->assertEquals(428.0, (float) $beansBalance->current_stock);

        // Susu Segar: Resep (200ml * 2 cup) = 400ml terpotong
        // Sisa Susu: 2000 - 400 = 1600ml
        $milkBalance = InventoryBalance::where('outlet_id', $this->outlet->id)->where('inventory_item_id', $milkInv->id)->first();
        $this->assertEquals(1600.0, (float) $milkBalance->current_stock);

        // Sirup Karamel: Modifier Extra Caramel (30ml * 2 cup * 1) = 60ml terpotong
        // Sisa Sirup: 500 - 60 = 440ml
        $syrupBalance = InventoryBalance::where('outlet_id', $this->outlet->id)->where('inventory_item_id', $syrupInv->id)->first();
        $this->assertEquals(440.0, (float) $syrupBalance->current_stock);

        // 6. Verifikasi COGS Snapshot
        // Resep Base COGS per cup = (18g * 200) + (200ml * 20) = 3600 + 4000 = 7600
        // Modifier COGS per cup = (18g * 200) + (30ml * 50) = 3600 + 1500 = 5100
        // Total Unit COGS per cup = 7600 + 5100 = 12700
        // Total COGS untuk 2 cup = 12700 * 2 = 25400
        $txItem = TransactionItem::where('transaction_id', $txId)->first();
        $this->assertNotNull($txItem);
        $this->assertEquals(12700.0, (float) $txItem->unit_cogs);
        $this->assertEquals(25400.0, (float) $txItem->cogs_amount);
        $this->assertIsArray($txItem->modifiers);
        $this->assertCount(2, $txItem->modifiers);

        // 7. Verifikasi Buku Besar Mutasi Inventori
        $movements = InventoryMovement::where('reference_id', $txId)
            ->where('movement_type', InventoryMovementType::RecipeDeduction->value)
            ->get();
        $this->assertCount(4, $movements, 'Must create 4 recipe_deduction movements (beans base, milk base, beans mod, syrup mod)');
    }

    /**
     * TIER 5 - Challenge 4: Void Transaction RBAC Security Matrix & Symmetrical FIFO/Recipe Restoration
     */
    public function test_adversarial_void_security_matrix_and_symmetrical_fifo_and_bom_restoration(): void
    {
        Sanctum::actingAs($this->device, ['pos:access']);

        // Siapkan transaksi completed retail dengan FIFO Cost Layer
        $invItem = InventoryItem::create([
            'business_id' => $this->business->id,
            'name' => 'Thermos Stainless 500ml',
            'sku' => 'THM-001',
            'uom_id' => $this->uomPcs->id,
            'cost_method' => 'fifo',
            'average_cost' => 80000,
            'last_cost' => 80000,
            'is_active' => true,
        ]);

        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Thermos Stainless 500ml',
            'code' => 'THM-001',
            'product_type' => ProductTypeEnum::BASIC->value,
            'track_inventory' => true,
            'is_show' => true,
            'sellable' => true,
        ]);
        $product->outlets()->attach($this->outlet->id, ['is_enabled' => true, 'is_available' => true]);

        InventoryBalance::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $invItem->id,
            'current_stock' => 10.0,
            'allocated_stock' => 0.0,
            'total_value' => 800000,
            'average_cost' => 80000,
        ]);

        InventoryCostLayer::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $invItem->id,
            'purchase_price' => 80000,
            'qty_purchased' => 10.0,
            'qty_remaining' => 10.0,
        ]);

        // Jual 3 pcs: Stok 10 -> 7
        $txId = (string) Str::uuid();
        $salePayload = [
            'offline_id' => $txId,
            'transaction_number' => 'POS/VOID/TARGET',
            'shift_id' => $this->shift->id,
            'cashier_id' => $this->cashier->id,
            'subtotal' => 360000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'service_charge_amount' => 0,
            'total' => 360000,
            'payment_status' => 'paid',
            'status' => 'completed',
            'items' => [
                [
                    'product_id' => $product->id,
                    'inventory_item_id' => $invItem->id,
                    'track_inventory' => true,
                    'product_name' => 'Thermos Stainless 500ml',
                    'price' => 120000,
                    'qty' => 3,
                    'discount_amount' => 0,
                    'subtotal' => 360000,
                ],
            ],
            'payments' => [
                [
                    'payment_method_id' => $this->cashMethod->id,
                    'amount' => 360000,
                    'change_amount' => 0,
                ],
            ],
        ];

        $saleResponse = $this->withHeaders([
            'X-DEVICE-UUID' => 'pos-dev-alpha',
            'X-HARDWARE-SIGNATURE' => 'pos-sig-alpha',
        ])->postJson('http://api.sollu.test/pos/transactions', $salePayload);
        $saleResponse->assertStatus(200);

        $balanceBeforeVoid = InventoryBalance::where('outlet_id', $this->outlet->id)->where('inventory_item_id', $invItem->id)->first();
        $this->assertEquals(7.0, (float) $balanceBeforeVoid->current_stock);

        // Security Test 1: Cross-Outlet Device Mencoba Void
        $foreignOutlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Cabang Sebelah',
            'is_active' => true,
        ]);
        $foreignDevice = OutletDevice::create([
            'outlet_id' => $foreignOutlet->id,
            'device_name' => 'POS Cabang Sebelah',
            'device_type' => DeviceTypeEnum::POS_TERMINAL->value,
            'client_device_uuid' => 'pos-dev-foreign',
            'hardware_fingerprint' => 'pos-sig-foreign',
            'is_active' => true,
        ]);
        $this->cacheService->putDevice($foreignDevice);

        Sanctum::actingAs($foreignDevice, ['pos:access']);
        $crossOutletResponse = $this->withHeaders([
            'X-DEVICE-UUID' => 'pos-dev-foreign',
            'X-HARDWARE-SIGNATURE' => 'pos-sig-foreign',
        ])->postJson("http://api.sollu.test/pos/transactions/{$txId}/void", [
            'supervisor_id' => $this->supervisor->id,
            'supervisor_pin' => '987654',
            'reason' => 'Cross outlet exploit',
        ]);
        $this->assertContains($crossOutletResponse->status(), [403, 404]);

        // Security Test 2: Perangkat Benar, tapi Kasir Biasa (Tanpa Izin Supervisi) Mencoba Void
        Sanctum::actingAs($this->device, ['pos:access']);
        $cashierVoidResponse = $this->withHeaders([
            'X-DEVICE-UUID' => 'pos-dev-alpha',
            'X-HARDWARE-SIGNATURE' => 'pos-sig-alpha',
        ])->postJson("http://api.sollu.test/pos/transactions/{$txId}/void", [
            'supervisor_id' => $this->cashier->id,
            'supervisor_pin' => '123123',
            'reason' => 'Cashier self-void exploit',
        ]);
        $this->assertEquals(403, $cashierVoidResponse->status());

        // Security Test 3: Supervisor Benar, tapi PIN Salah
        $wrongPinResponse = $this->withHeaders([
            'X-DEVICE-UUID' => 'pos-dev-alpha',
            'X-HARDWARE-SIGNATURE' => 'pos-sig-alpha',
        ])->postJson("http://api.sollu.test/pos/transactions/{$txId}/void", [
            'supervisor_id' => $this->supervisor->id,
            'supervisor_pin' => '000000',
            'reason' => 'Wrong PIN attempt',
        ]);
        $this->assertEquals(422, $wrongPinResponse->status());

        // Security Test 4: Cross-Tenant Supervisor Attack (Supervisor dari Business lain)
        $competitorBiz = Business::create([
            'name' => 'Competitor Business Corp',
            'owner_name' => 'Foreign Spv',
            'email' => 'competitor_'.uniqid().'@test.test',
            'phone' => '08988887777',
            'status' => 'active',
            'business_type_id' => $this->business->business_type_id,
        ]);
        $foreignSupervisor = User::create([
            'business_id' => $competitorBiz->id,
            'name' => 'Competitor Spv',
            'email' => 'foreign_spv_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
            'pin' => Hash::make('987654'),
        ]);

        $crossTenantSpvResponse = $this->withHeaders([
            'X-DEVICE-UUID' => 'pos-dev-alpha',
            'X-HARDWARE-SIGNATURE' => 'pos-sig-alpha',
        ])->postJson("http://api.sollu.test/pos/transactions/{$txId}/void", [
            'supervisor_id' => $foreignSupervisor->id,
            'supervisor_pin' => '987654',
            'reason' => 'Cross tenant supervisor exploit',
        ]);
        $this->assertEquals(422, $crossTenantSpvResponse->status());

        // 5. Valid Void: Supervisor Sah dengan PIN Sah
        $validVoidResponse = $this->withHeaders([
            'X-DEVICE-UUID' => 'pos-dev-alpha',
            'X-HARDWARE-SIGNATURE' => 'pos-sig-alpha',
        ])->postJson("http://api.sollu.test/pos/transactions/{$txId}/void", [
            'supervisor_id' => $this->supervisor->id,
            'supervisor_pin' => '987654',
            'reason' => 'Pelanggan membatalkan pesanan',
            'notes' => 'Disetujui oleh Supervisor Alpha',
        ]);

        $validVoidResponse->assertStatus(200);
        $validVoidResponse->assertJsonPath('status', 'success');
        $validVoidResponse->assertJsonPath('data.status', 'void');

        // Verifikasi Status di DB
        $txInDb = Transaction::find($txId);
        $this->assertEquals(TransactionStatus::Void->value, $txInDb->status instanceof TransactionStatus ? $txInDb->status->value : (string) $txInDb->status);
        $this->assertNotNull($txInDb->voided_at);
        $this->assertEquals($this->supervisor->id, $txInDb->voided_by);
        $this->assertEquals('Pelanggan membatalkan pesanan', $txInDb->void_reason);

        // Verifikasi Restorasi Simetris FIFO: Stok harus kembali ke 10.0
        $balanceRestored = InventoryBalance::where('outlet_id', $this->outlet->id)->where('inventory_item_id', $invItem->id)->first();
        $this->assertEquals(10.0, (float) $balanceRestored->current_stock);

        // Verifikasi Audit Log Void Tercatat
        $voidAuditLog = ActivityLog::where('outlet_id', $this->outlet->id)
            ->where('action', 'transaction.voided')
            ->where('subject_id', $txId)
            ->first();
        $this->assertNotNull($voidAuditLog);

        // Security Test 5: Replay / Double Void Attack -> Harus Ditolak HTTP 422
        $doubleVoidResponse = $this->withHeaders([
            'X-DEVICE-UUID' => 'pos-dev-alpha',
            'X-HARDWARE-SIGNATURE' => 'pos-sig-alpha',
        ])->postJson("http://api.sollu.test/pos/transactions/{$txId}/void", [
            'supervisor_id' => $this->supervisor->id,
            'supervisor_pin' => '987654',
            'reason' => 'Second void attempt should fail',
        ]);
        $doubleVoidResponse->assertStatus(422);
    }

    /**
     * TIER 5 - Challenge 5: Multi-Tender Split Payments Reconciliation & Zero Data Loss
     */
    public function test_adversarial_multi_tender_split_payments_integrity(): void
    {
        Sanctum::actingAs($this->device, ['pos:access']);

        $offlineId = (string) Str::uuid();

        $serviceProduct = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Jasa Servis & Retail Custom',
            'code' => 'SRV-001',
            'product_type' => ProductTypeEnum::SERVICE->value,
            'track_inventory' => false,
            'is_show' => true,
            'sellable' => true,
        ]);
        $serviceProduct->outlets()->attach($this->outlet->id, ['is_enabled' => true, 'is_available' => true]);

        // Total Belanja Rp 250.000 dengan 3 metode pembayaran berbeda:
        // 1. Tunai Cash: Rp 100.000
        // 2. QRIS: Rp 100.000 (ref: QRIS-SPLIT-771)
        // 3. EDC Debit: Rp 50.000 (ref: EDC-MDR-992)
        $payload = [
            'offline_id' => $offlineId,
            'transaction_number' => 'POS/SPLIT/TRIPLE/001',
            'shift_id' => $this->shift->id,
            'cashier_id' => $this->cashier->id,
            'subtotal' => 250000,
            'discount_amount' => 0,
            'tax_amount' => 0,
            'service_charge_amount' => 0,
            'total' => 250000,
            'payment_status' => 'paid',
            'status' => 'completed',
            'items' => [
                [
                    'product_id' => $serviceProduct->id,
                    'product_name' => 'Jasa Servis & Retail Custom',
                    'price' => 250000,
                    'qty' => 1,
                    'discount_amount' => 0,
                    'subtotal' => 250000,
                    'track_inventory' => false,
                ],
            ],
            'payments' => [
                [
                    'payment_method_id' => $this->cashMethod->id,
                    'amount' => 100000,
                    'change_amount' => 0,
                    'payment_reference' => null,
                ],
                [
                    'payment_method_id' => $this->qrisMethod->id,
                    'amount' => 100000,
                    'change_amount' => 0,
                    'payment_reference' => 'QRIS-SPLIT-771',
                ],
                [
                    'payment_method_id' => $this->debitMethod->id,
                    'amount' => 50000,
                    'change_amount' => 0,
                    'payment_reference' => 'EDC-MDR-992',
                ],
            ],
        ];

        // 1. Test via Single Sync Endpoint
        $response = $this->withHeaders([
            'X-DEVICE-UUID' => 'pos-dev-alpha',
            'X-HARDWARE-SIGNATURE' => 'pos-sig-alpha',
        ])->postJson('http://api.sollu.test/pos/transactions', $payload);

        $response->assertStatus(200);

        // Verifikasi semua baris tender pembayaran tersimpan di database
        $transaction = Transaction::with('payments')->findOrFail($offlineId);
        $this->assertCount(3, $transaction->payments, 'Must persist all 3 split payment rows (never truncate to .first)');

        $totalTenderPaid = (float) $transaction->payments->sum('amount');
        $this->assertEquals(250000.0, $totalTenderPaid);
        $this->assertEquals('paid', $transaction->payment_status instanceof TransactionPaymentStatus ? $transaction->payment_status->value : (string) $transaction->payment_status);

        $paymentMethodsInDb = $transaction->payments->pluck('payment_method_id')->all();
        $this->assertContains($this->cashMethod->id, $paymentMethodsInDb);
        $this->assertContains($this->qrisMethod->id, $paymentMethodsInDb);
        $this->assertContains($this->debitMethod->id, $paymentMethodsInDb);

        // 2. Test via Batch Sync Endpoint
        $batchOfflineId = (string) Str::uuid();
        $batchPayload = [
            'transactions' => [
                array_merge($payload, [
                    'offline_id' => $batchOfflineId,
                    'transaction_number' => 'POS/SPLIT/BATCH/002',
                ]),
            ],
        ];

        $batchResponse = $this->withHeaders([
            'X-DEVICE-UUID' => 'pos-dev-alpha',
            'X-HARDWARE-SIGNATURE' => 'pos-sig-alpha',
        ])->postJson('http://api.sollu.test/pos/transactions/batch', $batchPayload);

        $batchResponse->assertStatus(200);

        $batchTx = Transaction::with('payments')->findOrFail($batchOfflineId);
        $this->assertCount(3, $batchTx->payments, 'Batch sync must also preserve all 3 tender rows without dropping');
        $this->assertEquals(250000.0, (float) $batchTx->payments->sum('amount'));
    }

    /**
     * TIER 5 - Challenge 6: Zero N+1 Queries Invariant in Batch Sync Preloading
     */
    public function test_adversarial_anti_n_plus_one_batch_sync_complexity_invariant(): void
    {
        Sanctum::actingAs($this->device, ['pos:access']);

        $invItem = InventoryItem::create([
            'business_id' => $this->business->id,
            'name' => 'Teh Melati Tubruk 100g',
            'sku' => 'TEH-001',
            'uom_id' => $this->uomPcs->id,
            'cost_method' => 'fifo',
            'average_cost' => 10000,
            'last_cost' => 10000,
            'is_active' => true,
        ]);

        $product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Teh Melati Tubruk 100g',
            'code' => 'TEH-001',
            'product_type' => ProductTypeEnum::BASIC->value,
            'track_inventory' => true,
            'is_show' => true,
            'sellable' => true,
        ]);
        $product->outlets()->attach($this->outlet->id, ['is_enabled' => true, 'is_available' => true]);

        InventoryBalance::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $invItem->id,
            'current_stock' => 1000.0,
            'allocated_stock' => 0.0,
            'total_value' => 10000000,
            'average_cost' => 10000,
        ]);

        InventoryCostLayer::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $invItem->id,
            'purchase_price' => 10000,
            'qty_purchased' => 1000.0,
            'qty_remaining' => 1000.0,
        ]);

        $buildBatch = function (int $count, string $prefix) use ($product, $invItem) {
            $txs = [];
            for ($i = 1; $i <= $count; $i++) {
                $txs[] = [
                    'offline_id' => (string) Str::uuid(),
                    'transaction_number' => sprintf('POS/BENCH/%s/%04d', $prefix, $i),
                    'shift_id' => $this->shift->id,
                    'cashier_id' => $this->cashier->id,
                    'subtotal' => 15000,
                    'total' => 15000,
                    'payment_status' => 'paid',
                    'status' => 'completed',
                    'items' => [
                        [
                            'product_id' => $product->id,
                            'inventory_item_id' => $invItem->id,
                            'track_inventory' => true,
                            'product_name' => 'Teh Melati Tubruk 100g',
                            'price' => 15000,
                            'qty' => 1,
                            'subtotal' => 15000,
                        ],
                    ],
                    'payments' => [
                        [
                            'payment_method_id' => $this->cashMethod->id,
                            'amount' => 15000,
                            'change_amount' => 0,
                        ],
                    ],
                ];
            }

            return ['transactions' => $txs];
        };

        // 1 Transaction
        DB::flushQueryLog();
        DB::enableQueryLog();
        $res1 = $this->withHeaders([
            'X-DEVICE-UUID' => 'pos-dev-alpha',
            'X-HARDWARE-SIGNATURE' => 'pos-sig-alpha',
        ])->postJson('http://api.sollu.test/pos/transactions/batch', $buildBatch(1, 'SCALE1'));
        $queries1 = DB::getQueryLog();
        DB::disableQueryLog();
        $res1->assertStatus(200);

        // 5 Transactions
        DB::flushQueryLog();
        DB::enableQueryLog();
        $res5 = $this->withHeaders([
            'X-DEVICE-UUID' => 'pos-dev-alpha',
            'X-HARDWARE-SIGNATURE' => 'pos-sig-alpha',
        ])->postJson('http://api.sollu.test/pos/transactions/batch', $buildBatch(5, 'SCALE5'));
        $queries5 = DB::getQueryLog();
        DB::disableQueryLog();
        $res5->assertStatus(200);

        // Analisa Kueri SELECT Master Data
        // Preloaded entities (shifts, payment_methods, inventory_balances bulk lock)
        // HARUS dieksekusi tepat 1 kali per batch (set-based bulk preload), BUKAN 5 kali lipat.
        $bulkPreloadFilter = function (array $qList) {
            return array_filter($qList, function ($q) {
                $sql = strtolower($q['query']);

                return str_starts_with($sql, 'select')
                    && (
                        str_contains($sql, 'from "shifts" where "id" in')
                        || str_contains($sql, 'from "payment_methods" where "id" in')
                        || str_contains($sql, 'from "inventory_balances" where "outlet_id"')
                    );
            });
        };

        $bulkPreloads1 = $bulkPreloadFilter($queries1);
        $bulkPreloads5 = $bulkPreloadFilter($queries5);

        // Kueri bulk preload untuk 5 transaksi persis sama dengan 1 transaksi (O(1))
        $this->assertCount(count($bulkPreloads1), $bulkPreloads5, 'Bulk preloading must execute exactly 1 set-based query per entity for the entire batch');
    }
}
