<?php

namespace Tests\Feature\Inventory;

use App\Constants\AuthorizationMessage;
use App\Constants\FlashDataVariable;
use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Enums\PlanEnum;
use App\Enums\SubscriptionStatus;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Inventory\InventoryBalance;
use App\Models\Inventory\InventoryItem;
use App\Models\Master\Product;
use App\Models\Master\ProductItem;
use App\Models\Outlet;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Uom;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockTrackingToggleTest extends TestCase
{
    use RefreshDatabase;

    protected Business $business;

    protected Outlet $outlet;

    protected User $user;

    protected User $unauthorizedUser;

    protected Uom $uom;

    protected Product $product;

    protected ProductItem $productItem;

    protected InventoryItem $inventoryItem;

    protected InventoryBalance $balance;

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
                'features' => [
                    FeatureEnum::INVENTORY_MANAGEMENT->value,
                ],
            ]
        );

        $this->business = Business::create([
            'name' => 'Tracking Toggle Test Business',
            'owner_name' => 'Owner',
            'email' => 'track_'.uniqid().'@test.com',
            'phone' => '08123456789',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
            'settings' => [
                'active_features' => [
                    FeatureEnum::INVENTORY_MANAGEMENT->value,
                ],
            ],
        ]);

        $proPlan = SubscriptionPlan::where('code', PlanEnum::PRO->value)->first();
        Subscription::create([
            'business_id' => $this->business->id,
            'plan_id' => $proPlan->id,
            'status' => SubscriptionStatus::Active,
            'billing_cycle' => 'monthly',
            'started_at' => now()->subDay(),
            'expired_at' => now()->addMonth(),
        ]);
        $this->business->clearMemoizedFeatures();

        $this->outlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Outlet Tracking',
            'is_active' => true,
            'is_stock_frozen' => false,
        ]);

        $this->uom = Uom::where('code', 'pcs')->first() ?? Uom::first();

        // Create standard non-variant product
        $this->product = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Kopi Arabika',
            'product_type' => 'basic',
            'track_inventory' => false,
            'has_variants' => false,
            'is_active' => true,
        ]);

        $this->productItem = ProductItem::create([
            'business_id' => $this->business->id,
            'product_id' => $this->product->id,
            'name' => 'Kopi Arabika',
            'sku' => 'SKU-ARABIKA-001',
            'item_type' => 'variant_sku',
            'track_inventory' => false,
            'price' => 15000,
            'is_active' => true,
        ]);

        $this->inventoryItem = InventoryItem::create([
            'business_id' => $this->business->id,
            'product_item_id' => $this->productItem->id,
            'name' => 'Kopi Arabika',
            'uom_id' => $this->uom->id,
            'is_active' => false, // Initially disabled
        ]);

        $this->balance = InventoryBalance::create([
            'business_id' => $this->business->id,
            'outlet_id' => $this->outlet->id,
            'inventory_item_id' => $this->inventoryItem->id,
            'current_stock' => 0,
        ]);

        $this->user = User::factory()->create([
            'business_id' => $this->business->id,
            'is_root_user' => false,
        ]);

        setPermissionsTeamId($this->business->id);
        $this->user->givePermissionTo(PermissionEnum::INVENTORY_UPDATE->value);

        $this->unauthorizedUser = User::factory()->create([
            'business_id' => $this->business->id,
            'is_root_user' => false,
        ]);
    }

    public function test_user_with_inventory_update_can_toggle_tracking_on_and_off(): void
    {
        // 1. Toggle ON
        $responseOn = $this->actingAs($this->user, 'business')
            ->patch("http://{$this->appDomain}/inventories/stocks/{$this->inventoryItem->id}/toggle-tracking", [
                'is_active' => true,
            ]);

        $responseOn->assertRedirect();
        $this->assertTrue((bool) $this->inventoryItem->fresh()->is_active);
        $this->assertTrue((bool) $this->productItem->fresh()->track_inventory);
        $this->assertTrue((bool) $this->product->fresh()->track_inventory);

        // 2. Toggle OFF
        $responseOff = $this->actingAs($this->user, 'business')
            ->patch("http://{$this->appDomain}/inventories/stocks/{$this->inventoryItem->id}/toggle-tracking", [
                'is_active' => false,
            ]);

        $responseOff->assertRedirect();
        $this->assertFalse((bool) $this->inventoryItem->fresh()->is_active);
        $this->assertFalse((bool) $this->productItem->fresh()->track_inventory);
        $this->assertFalse((bool) $this->product->fresh()->track_inventory);
    }

    public function test_toggle_tracking_via_inventory_balance_id(): void
    {
        $response = $this->actingAs($this->user, 'business')
            ->patch("http://{$this->appDomain}/inventories/stocks/{$this->balance->id}/toggle-tracking", [
                'is_active' => true,
            ]);

        $response->assertRedirect();
        $this->assertTrue((bool) $this->inventoryItem->fresh()->is_active);
        $this->assertTrue((bool) $this->productItem->fresh()->track_inventory);
    }

    public function test_user_without_inventory_update_is_forbidden_403(): void
    {
        // 1. Web request: redirects with failed flash message
        $response = $this->actingAs($this->unauthorizedUser, 'business')
            ->patch("http://{$this->appDomain}/inventories/stocks/{$this->inventoryItem->id}/toggle-tracking", [
                'is_active' => true,
            ]);

        $response->assertSessionHas(FlashDataVariable::FAILED->value, AuthorizationMessage::CANT_ACCESS_PAGE);

        // 2. JSON request: returns HTTP 403 Forbidden
        $jsonResponse = $this->actingAs($this->unauthorizedUser, 'business')
            ->patchJson("http://{$this->appDomain}/inventories/stocks/{$this->inventoryItem->id}/toggle-tracking", [
                'is_active' => true,
            ]);

        $jsonResponse->assertForbidden();
    }
}
