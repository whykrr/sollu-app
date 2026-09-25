<?php

namespace Tests\Feature\Master;

use App\Constants\FlashDataVariable;
use App\Constants\ResourceMessage;
use App\Enums\FeatureEnum;
use App\Enums\PermissionEnum;
use App\Enums\ProductTypeEnum;
use App\Jobs\Master\ExportProductJob;
use App\Models\Business;
use App\Models\BusinessType;
use App\Models\Master\Product;
use App\Models\Master\ProductCategory;
use App\Models\Outlet;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ServiceProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Business $business;

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
            ['code' => 'service_business'],
            ['name' => 'Service Business', 'sort_order' => 1, 'is_visible' => true, 'features' => [FeatureEnum::SERVICE_CATALOG->value]]
        );

        $this->business = Business::create([
            'name' => 'Barber & Spa Salon',
            'owner_name' => 'Salon Owner',
            'email' => 'salon_'.uniqid().'@test.test',
            'phone' => '081234567890',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $type->id,
            'settings' => [
                'active_features' => [FeatureEnum::SERVICE_CATALOG->value],
            ],
        ]);

        $this->user = User::create([
            'business_id' => $this->business->id,
            'name' => 'Service Manager',
            'email' => 'manager_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        setPermissionsTeamId($this->business->id);

        foreach ([
            PermissionEnum::SERVICE_VIEW->value,
            PermissionEnum::SERVICE_CREATE->value,
            PermissionEnum::SERVICE_UPDATE->value,
            PermissionEnum::SERVICE_DELETE->value,
            PermissionEnum::SERVICE_EXPORT->value,
            PermissionEnum::SERVICE_IMPORT->value,
        ] as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'business']);
            $this->user->givePermissionTo($perm);
        }
    }

    public function test_guest_cannot_access_services(): void
    {
        $response = $this->get("http://{$this->appDomain}/master/services");

        $response->assertStatus(302);
        $response->assertRedirect(route('login'));
    }

    public function test_user_without_permission_cannot_view_services(): void
    {
        $unauthorizedUser = User::create([
            'business_id' => $this->business->id,
            'name' => 'No Perm Staff',
            'email' => 'noperm_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($unauthorizedUser, 'business')
            ->get("http://{$this->appDomain}/master/services");

        $response->assertStatus(403);
    }

    public function test_tenant_without_service_catalog_feature_cannot_access_services(): void
    {
        $typeNoService = BusinessType::firstOrCreate(
            ['code' => 'no_service'],
            ['name' => 'No Service Plan', 'sort_order' => 2, 'is_visible' => true, 'features' => []]
        );

        $businessNoFeature = Business::create([
            'name' => 'No Service Business',
            'owner_name' => 'Owner',
            'email' => 'nofeature_'.uniqid().'@test.test',
            'phone' => '081234567891',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $typeNoService->id,
            'settings' => [
                'active_features' => ['product_catalog'],
            ],
        ]);

        $userNoFeature = User::create([
            'business_id' => $businessNoFeature->id,
            'name' => 'User No Feature',
            'email' => 'user_nofeature_'.uniqid().'@test.test',
            'password' => bcrypt('password'),
        ]);

        setPermissionsTeamId($businessNoFeature->id);
        Permission::firstOrCreate(['name' => PermissionEnum::SERVICE_VIEW->value, 'guard_name' => 'business']);
        $userNoFeature->givePermissionTo(PermissionEnum::SERVICE_VIEW->value);

        $response = $this->actingAs($userNoFeature, 'business')
            ->get("http://{$this->appDomain}/master/services");

        $response->assertStatus(302);
        $response->assertSessionHas('feature_locked');

        $jsonResponse = $this->actingAs($userNoFeature, 'business')
            ->getJson("http://{$this->appDomain}/master/services");

        $jsonResponse->assertStatus(403)
            ->assertJsonPath('is_feature_locked', true)
            ->assertJsonPath('feature', FeatureEnum::SERVICE_CATALOG->value);
    }

    public function test_authorized_user_can_view_services_page_scoped_to_services(): void
    {
        $service = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Cuci Rambut Relaksasi',
            'code' => 'SRV-001',
            'product_type' => ProductTypeEnum::SERVICE,
            'is_show' => true,
            'sellable' => true,
        ]);

        $basicGood = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Shampoo Botol 250ml',
            'code' => 'PRD-001',
            'product_type' => ProductTypeEnum::BASIC,
            'is_show' => true,
            'sellable' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/master/services");

        $response->assertStatus(200);
        $response->assertInertia(
            fn (Assert $page) => $page
                ->component('Master/Service/Index')
                ->has('services.data', 1)
                ->where('services.data.0.id', $service->id)
                ->where('services.data.0.name', 'Cuci Rambut Relaksasi')
        );
    }

    public function test_form_options_returns_categories_and_active_outlets(): void
    {
        $category = ProductCategory::create([
            'business_id' => $this->business->id,
            'name' => 'Perawatan Rambut',
            'is_active' => true,
        ]);

        $activeOutlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Cabang Sudirman',
            'is_active' => true,
        ]);

        $inactiveOutlet = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Cabang Nonaktif',
            'is_active' => false,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->getJson("http://{$this->appDomain}/master/services/form-options");

        $response->assertStatus(200)
            ->assertJsonStructure(['categories', 'outlets'])
            ->assertJsonFragment(['name' => 'Perawatan Rambut'])
            ->assertJsonFragment(['name' => 'Cabang Sudirman'])
            ->assertJsonMissing(['name' => 'Cabang Nonaktif']);
    }

    public function test_user_can_view_service_detail_via_show_endpoint(): void
    {
        $service = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Pijat Refleksi',
            'code' => 'SRV-002',
            'product_type' => ProductTypeEnum::SERVICE,
            'is_show' => true,
            'sellable' => true,
        ]);
        $service->prices()->create([
            'outlet_id' => null,
            'amount' => 50000,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->getJson("http://{$this->appDomain}/master/services/{$service->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $service->id)
            ->assertJsonPath('data.name', 'Pijat Refleksi');
    }

    public function test_show_endpoint_fails_if_product_is_not_service(): void
    {
        $good = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Pomade Rambut',
            'code' => 'PRD-002',
            'product_type' => ProductTypeEnum::BASIC,
            'is_show' => true,
            'sellable' => true,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->getJson("http://{$this->appDomain}/master/services/{$good->id}");

        $response->assertStatus(404);
    }

    public function test_multi_tenant_isolation_cannot_view_other_tenant_service(): void
    {
        $otherBusiness = Business::create([
            'name' => 'Other Merchant',
            'owner_name' => 'Other Owner',
            'email' => 'other_'.uniqid().'@test.test',
            'phone' => '089876543210',
            'status' => 'active',
            'trial_end_at' => now()->addDays(14),
            'business_type_id' => $this->business->business_type_id,
        ]);

        $otherService = Product::create([
            'business_id' => $otherBusiness->id,
            'name' => 'Layanan Rahasia',
            'code' => 'SRV-SECRET',
            'product_type' => ProductTypeEnum::SERVICE,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->getJson("http://{$this->appDomain}/master/services/{$otherService->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_create_service_with_prices_and_outlets(): void
    {
        $category = ProductCategory::create([
            'business_id' => $this->business->id,
            'name' => 'Hair Styling',
            'is_active' => true,
        ]);

        $outlet1 = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Cabang Pusat',
            'is_active' => true,
        ]);

        $outlet2 = Outlet::create([
            'business_id' => $this->business->id,
            'name' => 'Cabang Mall',
            'is_active' => true,
        ]);

        $payload = [
            'name' => 'Coloring & Bleaching Premium',
            'code' => 'SRV-CLR',
            'description' => 'Pewarnaan rambut lengkap dengan vitamin rambut',
            'product_category_id' => $category->id,
            'base_price' => 250000,
            'is_show' => true,
            'sellable' => true,
            'outlets' => [
                ['outlet_id' => $outlet1->id, 'is_enabled' => true, 'is_available' => true],
                ['outlet_id' => $outlet2->id, 'is_enabled' => true, 'is_available' => false],
            ],
            'outlet_prices' => [
                ['outlet_id' => $outlet2->id, 'amount' => 275000],
            ],
        ];

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/master/services", $payload);

        $response->assertRedirect(route('master.services.index'));
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::CREATE_SUCCESS);

        $this->assertDatabaseHas('products', [
            'business_id' => $this->business->id,
            'name' => 'Coloring & Bleaching Premium',
            'code' => 'SRV-CLR',
            'product_type' => 'service',
            'track_inventory' => false,
            'has_variant' => false,
        ]);

        $created = Product::where('code', 'SRV-CLR')->first();
        $this->assertNotNull($created);
        $this->assertEquals(250000, $created->prices()->whereNull('outlet_id')->first()->amount);
        $this->assertEquals(275000, $created->prices()->where('outlet_id', $outlet2->id)->first()->amount);
        $this->assertCount(2, $created->outlets);
    }

    public function test_validation_errors_when_creating_service_without_required_fields(): void
    {
        $payload = [
            'name' => '',
            'base_price' => -5000,
        ];

        $response = $this->actingAs($this->user, 'business')
            ->post("http://{$this->appDomain}/master/services", $payload);

        $response->assertSessionHasErrors(['name', 'base_price']);
    }

    public function test_user_can_update_service_details_and_prices(): void
    {
        $service = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Creambath Tradisional',
            'code' => 'SRV-CRM',
            'product_type' => ProductTypeEnum::SERVICE,
            'is_show' => true,
            'sellable' => true,
        ]);
        $service->prices()->create([
            'outlet_id' => null,
            'amount' => 60000,
        ]);

        $updatePayload = [
            'name' => 'Creambath Tradisional Plus Pijat Bahu',
            'code' => 'SRV-CRM-PLUS',
            'description' => 'Layanan perawatan rambut dan pijat bahu relaksasi',
            'base_price' => 75000,
            'is_show' => true,
            'sellable' => true,
        ];

        $response = $this->actingAs($this->user, 'business')
            ->put("http://{$this->appDomain}/master/services/{$service->id}", $updatePayload);

        $response->assertRedirect(route('master.services.index'));
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::UPDATE_SUCCESS);

        $service->refresh();
        $this->assertEquals('Creambath Tradisional Plus Pijat Bahu', $service->name);
        $this->assertEquals('SRV-CRM-PLUS', $service->code);
        $this->assertEquals(75000, $service->prices()->whereNull('outlet_id')->first()->amount);
    }

    public function test_user_can_soft_delete_service(): void
    {
        $service = Product::create([
            'business_id' => $this->business->id,
            'name' => 'Layanan Akan Dihapus',
            'code' => 'SRV-DEL',
            'product_type' => ProductTypeEnum::SERVICE,
        ]);

        $response = $this->actingAs($this->user, 'business')
            ->delete("http://{$this->appDomain}/master/services/{$service->id}");

        $response->assertStatus(302);
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::DELETE_SUCCESS);

        $this->assertSoftDeleted('products', [
            'id' => $service->id,
        ]);
    }

    public function test_export_services_dispatches_export_job(): void
    {
        Queue::fake();

        $response = $this->actingAs($this->user, 'business')
            ->get("http://{$this->appDomain}/master/services/export");

        $response->assertStatus(302);
        $response->assertSessionHas(FlashDataVariable::SUCCESS->value, ResourceMessage::EXPORT_PROCESSING);

        Queue::assertPushed(ExportProductJob::class, function ($job) {
            return $job->businessId === $this->business->id;
        });
    }
}
