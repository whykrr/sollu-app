<?php

declare(strict_types=1);

namespace Tests\Feature\API;

use App\Enums\DeviceTypeEnum;
use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Enums\PlanEnum;
use App\Enums\ProductTypeEnum;
use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryCostLayer;
use App\Models\Inventory\InventoryItem;
use App\Models\Master\PaymentMethod;
use App\Models\Master\Product;
use App\Models\Outlet;
use App\Models\OutletDevice;
use App\Models\Sales\Shift;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Uom;
use App\Models\User;
use App\Services\Pos\PosDeviceAuthCacheService;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PosBatchSyncQueryBenchmarkTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;

    protected Outlet $outlet;

    protected OutletDevice $device;

    protected User $cashier;

    protected Shift $shift;

    protected PaymentMethod $cashMethod;

    protected InventoryItem $inventoryItem;

    protected Product $product;

    protected PosDeviceAuthCacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
        $this->cacheService = app(PosDeviceAuthCacheService::class);

        $type = BusinessType::firstOrCreate(
            ['code' => 'retail'],
            ['name' => 'Retail', 'sort_order' => 1, 'is_visible' => true, 'features' => array_column(FeatureEnum::cases(), 'value')]
        );

        $this->business = Business::create([
            'name' => 'POS Merchant Test',
            'owner_name' => 'Merchant Owner',
            'email' => 'pos_bench_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
            'settings' => [
                'active_features' => array_column(FeatureEnum::cases(), 'value'),
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
            'name' => 'POS Outlet Utama',
            'address' => 'Jl. Merdeka No. 10',
            'phone' => '08123456789',
            'is_active' => true,
        ]);

        $this->device = OutletDevice::create([
            'outlet_id' => $this->outlet->id,
            'device_name' => 'POS Terminal 01',
            'device_type' => DeviceTypeEnum::POS_TERMINAL->value,
            'client_device_uuid' => 'pos-dev-001',
            'hardware_fingerprint' => 'pos-sig-001',
            'is_active' => true,
        ]);
        $this->cacheService->putDevice($this->device);

        setPermissionsTeamId($this->business->id);

        $this->cashier = User::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'name' => 'Kasir Utama',
            'email' => 'kasir_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
            'pin' => Hash::make('112233'),
        ]);
        $this->outlet->users()->attach($this->cashier->id);
        $this->cashier->givePermissionTo(PermissionEnum::TRANSACTION_CREATE->value);
        $this->cashier->givePermissionTo(PermissionEnum::TRANSACTION_VIEW->value);

        $this->shift = Shift::create([
            'outlet_id' => $this->outlet->id,
            'user_id' => $this->cashier->id,
            'shift_number' => 'SH-001',
            'start_time' => now()->subHours(2),
            'opening_balance' => 100000,
            'status' => 'open',
        ]);

        $this->cashMethod = PaymentMethod::create([
            'business_id' => $this->business->id,
            'name' => 'Tunai / Cash',
            'type' => 'cash',
            'is_active' => true,
        ]);
        $this->cashMethod->outlets()->attach($this->outlet->id);

        $uom = Uom::where('code', 'Pcs')->first()
            ?? Uom::first()
            ?? Uom::create(['code' => 'Pcs', 'name' => 'Pieces']);

        $this->inventoryItem = InventoryItem::create([
            'business_id' => $this->business->id,
            'name' => 'Kopi Arabika 250g',
            'sku' => 'KOP-001',
            'uom_id' => $uom->id,
            'cost_method' => 'fifo',
            'average_cost' => 20000,
            'last_cost' => 20000,
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Kopi Arabika 250g',
            'code' => 'KOP-001',
            'product_type' => ProductTypeEnum::BASIC->value,
            'track_inventory' => true,
            'is_show' => true,
            'sellable' => true,
        ]);
        $this->product->outlets()->attach($this->outlet->id, ['is_enabled' => true, 'is_available' => true]);

        InventoryBalance::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'current_stock' => 500.0,
            'allocated_stock' => 0.0,
            'total_value' => 10000000,
        ]);

        InventoryCostLayer::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'purchase_price' => 20000,
            'qty_purchased' => 500.0,
            'qty_remaining' => 500.0,
        ]);
    }

    protected function generateBatchPayload(int $count, string $batchPrefix): array
    {
        $transactions = [];
        for ($i = 1; $i <= $count; $i++) {
            $offlineId = (string) Str::uuid();
            $transactions[] = [
                'offline_id' => $offlineId,
                'transaction_number' => sprintf('POS/%s/%s/%04d', $batchPrefix, now()->format('Ymd'), $i),
                'shift_id' => $this->shift->id,
                'cashier_id' => $this->cashier->id,
                'subtotal' => 25000,
                'total' => 25000,
                'payment_status' => 'paid',
                'status' => 'completed',
                'items' => [
                    [
                        'product_id' => $this->product->id,
                        'inventory_item_id' => $this->inventoryItem->id,
                        'track_inventory' => true,
                        'product_name' => 'Kopi Arabika 250g',
                        'price' => 25000,
                        'qty' => 1,
                        'subtotal' => 25000,
                    ],
                ],
                'payments' => [
                    [
                        'payment_method_id' => $this->cashMethod->id,
                        'amount' => 25000,
                        'change_amount' => 0,
                    ],
                ],
            ];
        }

        return ['transactions' => $transactions];
    }

    public function test_benchmark_batch_sync_queries_1_vs_10_vs_25(): void
    {
        Sanctum::actingAs($this->device, ['pos:access']);

        $runBenchmark = function (int $txCount, string $prefix) {
            $payload = $this->generateBatchPayload($txCount, $prefix);

            DB::flushQueryLog();
            DB::enableQueryLog();

            $response = $this->withHeaders([
                'X-DEVICE-UUID' => 'pos-dev-001',
                'X-HARDWARE-SIGNATURE' => 'pos-sig-001',
            ])->postJson('http://api.sollu.test/pos/transactions/batch', $payload);

            $queries = DB::getQueryLog();
            DB::disableQueryLog();

            if (! in_array($response->status(), [200, 201], true)) {
                dump(['status' => $response->status(), 'content' => $response->json() ?? $response->getContent()]);
            }
            $this->assertContains($response->status(), [200, 201]);

            $selectQueries = array_filter($queries, fn ($q) => stripos(trim($q['query']), 'select') === 0);
            $insertQueries = array_filter($queries, fn ($q) => stripos(trim($q['query']), 'insert') === 0);
            $updateQueries = array_filter($queries, fn ($q) => stripos(trim($q['query']), 'update') === 0);

            return [
                'tx_count' => $txCount,
                'total_queries' => count($queries),
                'select_queries' => count($selectQueries),
                'insert_queries' => count($insertQueries),
                'update_queries' => count($updateQueries),
                'queries' => $queries,
            ];
        };

        // 1 Transaction
        $bench1 = $runBenchmark(1, 'RUN1');

        // 10 Transactions
        $bench10 = $runBenchmark(10, 'RUN10');

        // 25 Transactions
        $bench25 = $runBenchmark(25, 'RUN25');

        // Dump queries of bench1
        $queryList = array_map(fn ($q) => $q['query'], $bench1['queries']);
        dump(['bench1_queries' => $queryList]);

        $this->assertTrue(true);
    }
}
