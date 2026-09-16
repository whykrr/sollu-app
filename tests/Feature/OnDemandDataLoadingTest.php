<?php

namespace Tests\Feature;

use App\Enums\FeatureEnum;
use App\Enums\PlanEnum;
use App\Models\Inventory\Supplier;
use App\Models\Master\ModifierGroup;
use App\Models\Promo;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class OnDemandDataLoadingTest extends TestCase
{
    use RefreshDatabase;

    protected string $appDomain;

    protected function setUp(): void
    {
        parent::setUp();
        config(['inertia.testing.page_paths' => [resource_path('js/Pages/App')]]);
        $this->seed(DatabaseSeeder::class);
        $this->appDomain = config('domain.app', 'app.sollu.test');
    }

    protected function subscribeBusinessToPlan(User $user, PlanEnum $planEnum = PlanEnum::PRO): void
    {
        setPermissionsTeamId($user->business_id);
        $plan = SubscriptionPlan::where('code', $planEnum->value)->first();
        Subscription::create([
            'business_id' => $user->business_id,
            'plan_id' => $plan->id,
            'status' => \App\Enums\SubscriptionStatus::Active,
            'billing_cycle' => 'monthly',
            'started_at' => Carbon::now()->subDays(1),
            'expired_at' => Carbon::now()->addDays(29),
        ]);

        $business = $user->business;
        $settings = $business->settings ?? [];
        $settings['active_features'] = array_map(fn (FeatureEnum $case) => $case->value, FeatureEnum::cases());
        $business->settings = $settings;
        $business->save();

        $user->refresh();
        $user->load('business');
        $user->business->unsetRelation('subscriptions');
    }

    public function test_products_index_does_not_overfetch_secondary_lookup_props(): void
    {
        $user = User::first();
        $this->subscribeBusinessToPlan($user);

        $response = $this->actingAs($user, 'business')->get("http://{$this->appDomain}/master/products");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Master/Product/Index')
            ->has('products')
            ->has('categories')
            ->missing('rawCategories')
            ->missing('outlets')
            ->missing('modifierGroups')
            ->missing('inventoryItems')
            ->missing('baseProducts')
            ->missing('uoms')
        );
    }

    public function test_products_form_options_returns_lookup_data_on_demand(): void
    {
        $user = User::first();
        $this->subscribeBusinessToPlan($user);

        $response = $this->actingAs($user, 'business')->get("http://{$this->appDomain}/master/products/form-options");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'categories',
            'outlets',
            'modifierGroups',
            'inventoryItems',
            'baseProducts',
            'uoms',
        ]);
    }

    public function test_stock_adjustments_index_does_not_overfetch_items(): void
    {
        $user = User::first();
        $this->subscribeBusinessToPlan($user);

        $response = $this->actingAs($user, 'business')->get("http://{$this->appDomain}/inventories/adjustments");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Inventory/Adjustment/Index')
            ->has('adjustments')
            ->missing('items')
        );
    }

    public function test_modifiers_index_uses_options_count_and_show_returns_options_on_demand(): void
    {
        $user = User::first();
        $this->subscribeBusinessToPlan($user);

        $modifier = ModifierGroup::create([
            'business_id' => $user->business_id,
            'name' => 'Topping',
            'selection_type' => 'single',
            'is_required' => false,
        ]);
        $modifier->options()->create([
            'name' => 'Keju',
            'additional_price' => 5000,
        ]);

        $indexResponse = $this->actingAs($user, 'business')->get("http://{$this->appDomain}/master/modifiers");
        $indexResponse->assertStatus(200);
        $indexResponse->assertInertia(fn (Assert $page) => $page
            ->component('Master/Product/Modifier/Index')
            ->has('modifiers.data.0.options_count')
        );

        $showResponse = $this->actingAs($user, 'business')->get("http://{$this->appDomain}/master/modifiers/{$modifier->id}");
        $showResponse->assertStatus(200);
        $showResponse->assertJsonFragment([
            'name' => 'Topping',
        ]);
        $showResponse->assertJsonStructure([
            'id',
            'name',
            'options' => [
                '*' => ['id', 'name', 'additional_price'],
            ],
        ]);
    }

    public function test_promotions_show_endpoint_returns_outlets_and_items_on_demand(): void
    {
        $user = User::first();
        $this->subscribeBusinessToPlan($user);

        $promo = Promo::create([
            'business_id' => $user->business_id,
            'created_by' => $user->id,
            'name' => 'Promo Merdeka',
            'target_type' => 'bill',
            'promo_type' => 'percentage',
            'discount_value' => 10,
            'start_date' => now()->toDateString(),
            'end_date' => now()->addDays(7)->toDateString(),
            'applies_to_all_outlets' => true,
        ]);

        $response = $this->actingAs($user, 'business')->get("http://{$this->appDomain}/promotions/{$promo->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'name',
            'outlets',
            'inventory_items',
        ]);
    }

    public function test_suppliers_show_endpoint_returns_inventory_items_on_demand(): void
    {
        $user = User::first();
        $this->subscribeBusinessToPlan($user);

        $supplier = Supplier::create([
            'business_id' => $user->business_id,
            'name' => 'PT Sumber Rezeki',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user, 'business')->get("http://{$this->appDomain}/inventories/suppliers/{$supplier->id}");

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'id',
            'name',
            'inventory_items',
        ]);
    }
}
