<?php

declare(strict_types=1);

namespace Tests\Feature\Promotion;

use App\Enums\FeatureEnum;
use App\Enums\PlanEnum;
use App\Enums\ProductTypeEnum;
use App\Enums\PromotionApplicationMode;
use App\Enums\PromotionDiscountType;
use App\Enums\PromotionStatus;
use App\Enums\PromotionTargetScope;
use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Master\Product;
use App\Models\Master\ProductItem;
use App\Models\Outlet;
use App\Models\Promotion\Promotion;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class PromoInternalApiTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

    protected Outlet $outlet;

    protected Product $product;

    protected ProductItem $productItem;

    protected string $appDomain;

    protected function setUp(): void
    {
        parent::setUp();

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
            'name' => 'Promo Test Merchant',
            'owner_name' => 'Owner',
            'email' => 'promo_test_'.uniqid().'@test.com',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
            'settings' => [
                'active_features' => [FeatureEnum::INVOICE_DEBT->value],
            ],
        ]);

        $plan = SubscriptionPlan::where('code', PlanEnum::PRO->value)->first()
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
            'name' => 'Promo Test Outlet',
        ]);

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Promo Staff',
            'email' => 'promostaff_'.uniqid().'@test.com',
            'password' => bcrypt('password'),
        ]);

        $this->user->outlets()->attach($this->outlet->id);

        setPermissionsTeamId($this->business->id);
        Permission::firstOrCreate(['name' => 'transaction.view', 'guard_name' => 'business']);
        Permission::firstOrCreate(['name' => 'transaction.create', 'guard_name' => 'business']);
        $this->user->givePermissionTo(['transaction.view', 'transaction.create']);

        $this->product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Product Promo Test',
            'product_type' => ProductTypeEnum::BASIC,
            'is_active' => true,
        ]);

        $this->productItem = ProductItem::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'name' => 'Product Promo Item 1',
            'sku' => 'PRM-001',
            'item_type' => 'variant_sku',
            'track_inventory' => true,
            'is_active' => true,
        ]);
    }

    public function test_it_returns_available_promotions_for_outlet(): void
    {
        // 1. Create transaction promotion
        Promotion::create([
            'business_id' => $this->business->id,
            'name' => 'Promo Diskon Transaksi 10%',
            'application_mode' => PromotionApplicationMode::Automatic->value,
            'target_scope' => PromotionTargetScope::Transaction->value,
            'discount_type' => PromotionDiscountType::Percentage->value,
            'discount_value' => 10,
            'min_subtotal' => 100000,
            'min_quantity' => 1,
            'applies_to_all_outlets' => true,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'status' => PromotionStatus::Active->value,
            'created_by' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->getJson("http://{$this->appDomain}/api/internal/promos/available?outlet_id={$this->outlet->id}&target_scope=transaction");

        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'discount_type',
                    'discount_value',
                    'min_subtotal',
                ],
            ],
        ]);
        $this->assertCount(1, $response->json('data'));
    }

    public function test_it_evaluates_promotions_in_real_time_via_api(): void
    {
        Promotion::create([
            'business_id' => $this->business->id,
            'name' => 'Auto Promo 15%',
            'application_mode' => PromotionApplicationMode::Automatic->value,
            'target_scope' => PromotionTargetScope::Transaction->value,
            'discount_type' => PromotionDiscountType::Percentage->value,
            'discount_value' => 15,
            'min_subtotal' => 100000,
            'min_quantity' => 1,
            'applies_to_all_outlets' => true,
            'start_date' => now()->subDay()->toDateString(),
            'end_date' => now()->addDays(10)->toDateString(),
            'status' => PromotionStatus::Active->value,
            'created_by' => $this->user->id,
        ]);

        $payload = [
            'outlet_id' => $this->outlet->id,
            'channel' => 'wholesale',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'product_item_id' => $this->productItem->id,
                    'price' => 100000,
                    'qty' => 2,
                    'discount_amount' => 0,
                ],
            ],
        ];

        $response = $this->actingAs($this->user, 'business')
            ->postJson("http://{$this->appDomain}/api/internal/promos/evaluate", $payload);

        $response->assertOk();
        $response->assertJsonPath('data.original_subtotal', 200000);
        $response->assertJsonPath('data.total_discount', 30000); // 15% of 200,000 = 30,000
        $response->assertJsonPath('data.final_subtotal', 170000);
    }
}
