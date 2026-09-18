<?php

namespace Tests\Feature\Cockpit;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\CockpitUser;
use App\Models\Feature;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class BusinessTypeTest extends TestCase
{
    use RefreshDatabase;

    protected CockpitUser $admin;

    protected string $cockpitHost;

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
            'email' => 'admin_test@sollu.test',
            'password' => bcrypt('password'),
            'status' => 'active',
        ]);
    }

    public function test_guest_cannot_access_cockpit_business_types(): void
    {
        $response = $this->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->get("http://{$this->cockpitHost}/business-types");

        $response->assertStatus(302);
        $response->assertRedirect(route('cockpit.login'));
    }

    public function test_admin_can_view_business_types_index(): void
    {
        BusinessType::create([
            'code' => 'retail',
            'name' => 'Minimarket & Retail',
            'sort_order' => 1,
            'is_visible' => true,
            'features' => ['pos_cashier'],
        ]);

        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->get("http://{$this->cockpitHost}/business-types");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Cockpit/BusinessType/Index')
            ->has('businessTypes', 1)
            ->has('allFeatures')
        );
    }

    public function test_admin_can_sort_business_types_by_name_and_sort_order(): void
    {
        BusinessType::create([
            'code' => 'retail',
            'name' => 'Minimarket & Retail',
            'sort_order' => 1,
            'is_visible' => true,
            'features' => ['pos_cashier'],
        ]);

        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->get("http://{$this->cockpitHost}/business-types?sort=name&direction=desc");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Cockpit/BusinessType/Index')
            ->where('params.sort', 'name')
            ->where('params.direction', 'desc')
            ->has('businessTypes')
        );

        $responseAsc = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->get("http://{$this->cockpitHost}/business-types?sort=sort_order&direction=asc");

        $responseAsc->assertStatus(200);
        $responseAsc->assertInertia(fn (Assert $page) => $page
            ->component('Cockpit/BusinessType/Index')
            ->where('params.sort', 'sort_order')
            ->where('params.direction', 'asc')
            ->has('businessTypes')
        );
    }

    public function test_admin_can_view_single_business_type_json(): void
    {
        $type = BusinessType::create([
            'code' => 'coffee_shop',
            'name' => 'Coffee Shop',
            'sort_order' => 2,
            'is_visible' => true,
            'features' => ['pos_cashier', 'recipe_management'],
        ]);

        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->get("http://{$this->cockpitHost}/business-types/{$type->id}");

        $response->assertStatus(200);
        $response->assertJson([
            'id' => $type->id,
            'code' => 'coffee_shop',
            'name' => 'Coffee Shop',
            'features' => ['pos_cashier', 'recipe_management'],
        ]);
        $response->assertJsonStructure(['all_features']);
    }

    public function test_admin_can_create_business_type(): void
    {
        $payload = [
            'code' => 'barbershop',
            'name' => 'Barbershop & Salon',
            'sort_order' => 10,
            'is_visible' => true,
            'features' => ['pos_cashier', 'shift_management'],
        ];

        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->post("http://{$this->cockpitHost}/business-types", $payload);

        $response->assertRedirect();
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::CREATE_SUCCESS);

        $this->assertDatabaseHas('business_types', [
            'code' => 'barbershop',
            'name' => 'Barbershop & Salon',
            'sort_order' => 10,
            'is_visible' => true,
        ]);
    }

    public function test_admin_can_update_business_type(): void
    {
        $type = BusinessType::create([
            'code' => 'bakery',
            'name' => 'Bakery Toko Roti',
            'sort_order' => 5,
            'is_visible' => true,
        ]);

        $payload = [
            'code' => 'bakery_pastry',
            'name' => 'Bakery & Pastry Premium',
            'sort_order' => 3,
            'is_visible' => false,
        ];

        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->put("http://{$this->cockpitHost}/business-types/{$type->id}", $payload);

        $response->assertRedirect();
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::UPDATE_SUCCESS);

        $type->refresh();
        $this->assertSame('bakery_pastry', $type->code);
        $this->assertSame('Bakery & Pastry Premium', $type->name);
        $this->assertSame(3, $type->sort_order);
        $this->assertFalse($type->is_visible);
    }

    public function test_admin_can_toggle_business_type_visibility(): void
    {
        $type = BusinessType::create([
            'code' => 'pharmacy',
            'name' => 'Apotek',
            'sort_order' => 8,
            'is_visible' => true,
        ]);

        // Hide
        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->post("http://{$this->cockpitHost}/business-types/{$type->id}/toggle-visibility");

        $response->assertRedirect();
        $type->refresh();
        $this->assertFalse($type->is_visible);

        // Show back
        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->post("http://{$this->cockpitHost}/business-types/{$type->id}/toggle-visibility");

        $response->assertRedirect();
        $type->refresh();
        $this->assertTrue($type->is_visible);
    }

    public function test_admin_can_update_business_type_features_in_separate_endpoint(): void
    {
        $type = BusinessType::create([
            'code' => 'restaurant',
            'name' => 'Restoran',
            'sort_order' => 1,
            'is_visible' => true,
            'features' => ['pos_cashier'],
        ]);

        $features = Feature::take(3)->pluck('code')->all();

        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->put("http://{$this->cockpitHost}/business-types/{$type->id}/features", [
                'features' => $features,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value);

        $type->refresh();
        $this->assertCount(3, $type->features);
        $this->assertEqualsCanonicalizing($features, $type->features);
    }

    public function test_admin_cannot_delete_business_type_with_associated_businesses(): void
    {
        $type = BusinessType::create([
            'code' => 'minimarket',
            'name' => 'Minimarket',
            'sort_order' => 1,
            'is_visible' => true,
        ]);

        Business::create([
            'name' => 'Toko Kelontong Sejahtera',
            'owner_name' => 'Budi Santoso',
            'email' => 'budi@sejahtera.test',
            'phone' => '081122334455',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
        ]);

        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->delete("http://{$this->cockpitHost}/business-types/{$type->id}");

        $response->assertRedirect();
        $response->assertSessionHas(FlashDataVariable::FAILED->value);
        $this->assertDatabaseHas('business_types', ['id' => $type->id]);
    }

    public function test_admin_can_delete_unused_business_type(): void
    {
        $type = BusinessType::create([
            'code' => 'obsolete_type',
            'name' => 'Obsolete Type',
            'sort_order' => 99,
            'is_visible' => false,
        ]);

        $response = $this->actingAs($this->admin, 'cockpit')
            ->withServerVariables(['HTTP_HOST' => $this->cockpitHost])
            ->delete("http://{$this->cockpitHost}/business-types/{$type->id}");

        $response->assertRedirect();
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::PURGE_SUCCESS);
        $this->assertDatabaseMissing('business_types', ['id' => $type->id]);
    }
}
